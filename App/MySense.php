<?php
// Abstração para lidar com operação de criação de schema, indexação e consulta de dados com Typesense
namespace App;

use DateTime;
use Http\Client\Exception;
use JsonException;
use stdClass;
use Typesense\Client;
use Typesense\Exceptions\ConfigError;
use Typesense\Exceptions\TypesenseClientError;


class MySense
{
    private Client $client;
    private array $errors;
    private Logs $Logs;
    private VoyageAI $VoyageAI;

    public function __construct()
    {
        $this->Logs = new Logs();
        $this->VoyageAI = new VoyageAI();
        $this->errors = [];
        try {
            $this->client = new Client(TS_CONFIG);
        } catch (ConfigError $e) {
            exit($e->getMessage());
        }
    }




    /**
     * Adiciona informações sobre o video na coleção de video_data
     **/
    public function addVideoInfo(array $videoInfoInput): bool
    {
        /*
         // Formato esperado
         $videoInfoInput = [
            "status_code" => 0,
            "title" => "The title",
            "video_id" => "56sdf8",
            "channel_name" => "Channel Name",
            "channel_id" => "XYZ.."
            "ex_date" => 1311
        ];
        */
        $video_id = $videoInfoInput['video_id'];
        if ($this->hasVideo($video_id)) {
            $this->errors[] = "Video $video_id ja cadastrado!";
            return false;
        }

        // data de extração deve ser no formato unix timestamp
        $ex_date = $videoInfoInput['ex_date'];
        if (!is_int($ex_date)) {
            // se não estiver no formato unix timestamp faz a conversão
            try {
                $data = new DateTime($ex_date);
                $ex_date = $data->getTimestamp();
            } catch (\Exception $e) {
                // se não conseguir converter, pega a data atual
                $data = new DateTime();
                $ex_date = $data->getTimestamp();
                $this->Logs->register($e->getMessage() . " - ex_date: $ex_date", true);
            }
        }

        $videoInfoInput['ex_date'] = $ex_date;

        try {
            $this->client->collections['video_data']->documents->create($videoInfoInput);
        } catch (Exception|TypesenseClientError $e) {
            $this->errors[] = $e->getMessage();
            $this->Logs->register($e->getMessage(), true);
            return false;
        }
        return true;

    }


    /**
     * Verifica se existe algum video na coleção video_data
    **/
    public function hasAnyVideo(string $channel_id)
    {
        $searchParameters = [
            'q' => '*',
            'query_by' => 'video_id',
            'filter_by' => "channel_id:=$channel_id",
            "per_page" => 1,

        ];
        try {
            $response = $this->client->collections['video_data']->documents->search($searchParameters);
            $found = $response['found'] ?? 0;
            $found = (int) $found;
            if($found > 0){
                return true;
            }
        }catch (Exception|TypesenseClientError $e) {
            $this->Logs->register($e->getMessage());
            exit("Ops: ".$e->getMessage());
        }
        return false;

    }

    /**
     * Adiciona trechos de legendas na coleção de captions_chunks
     * @param array $documents Trechos de legenda a serem adicionados
     * @param bool $isMulti (default false) True importar vários trechos de uma vez
     **/
    public function addSubtitleSnippet(array $documents, bool $isMulti = false, $addEmbedding = true): bool
    {
        if(!$this->isHealthOK()){
            $erros = json_encode($this->getErrors());
            $this->Logs->register($erros);
            return false;
        }

        /*
         // Formato esperado para documento único
         $one_document = [
            "sub" => "It was awesome",
            "ts" => "00:00:01",
            "video_data_id" => "0",
            "vector" => [0.1, 1.0]; // embedding optional
        */


        /*
         // Formato esperado para vários documentos
         $all_document = [$one_document, $one_document, $one_document];
        */


        $all_subtitle_chunks = [];
        try {
            if($isMulti){
                if($addEmbedding){
                    foreach($documents as $doc){
                        $all_subtitle_chunks[] = $doc['sub'];
                    }
                    $start = time();
                    $all_embeddings = $this->VoyageAI->createEmbeddings($all_subtitle_chunks, VOYAGE_EMBEDDING_MODEL);
                    $duration = time() - $start;
                    echo "Voyage levou $duration segundos<br>";
                    if(empty($all_embeddings)){
                        $this->Logs->register("Erro ao gerar embeddings", true);
                        return false;
                    }
                    $new_doc = [];
                    $idx = 0;
                    foreach($documents as $doc){
                        $doc["vector"] = $all_embeddings[$idx];
                        $idx++;
                        $new_doc[] = $doc;
                    }
                    $documents = $new_doc;
                }
                $this->client->collections['captions_chunks']->documents->import($documents, ['action' => 'create']);
            }else{
                if($addEmbedding){
                    //$embedding = $this->GoogleEmbedding->embed([$documents['sub']], 'RETRIEVAL_DOCUMENT');
                    $embedding = $this->VoyageAI->createEmbeddings([$documents['sub']], VOYAGE_EMBEDDING_MODEL);
                    if(empty($embedding)){
                        $this->Logs->register("Erro ao gerar embeddings", true);
                        return false;
                    }
                    $documents['vector'] = $embedding[0];
                }
                $this->client->collections['captions_chunks']->documents->create($documents);
            }
        } catch (Exception|\Exception|TypesenseClientError|JsonException $e) {
            $this->errors[] = $e->getMessage();
            echo "addSubtitleSnippet: ".$e->getMessage()."<br>\n";
            $this->Logs->register($e->getMessage(), true);
            return false;
        }
        return true;

    }


    /**
     * Verifica se um vídeo existe na coleção de video_data
     **/
    public function hasVideo(string $videoID): bool
    {
        $searchParameters = [
            'q' => "*",
            'query_by' => 'video_id',
            'filter_by' => "video_id:=$videoID",
        ];
        // Nota: ID de videos do YouTube possui caracteres como "_" e "-" que, são removidos na tokenização do Typesense
        // Isso significa que se o video_id for "56sd-f8_" ele será procurado como "56sdf8"
        // Como é quase impossível a remoção desses caracteres resultar em IDs iguais para o projeto
        // Isso não deve ser um problema
        try {
            $data = $this->client->collections['video_data']->documents->search($searchParameters);
            $found_video_id = $data['hits'][0]['document']['video_id'] ?? null;
            return $found_video_id === $videoID;
        } catch (Exception|TypesenseClientError $e) {
            $this->Logs->register($e->getMessage(), true);
            exit($e->getMessage());
        }
    }


    public function getInfoByVideoID(string $videoID): array
    {
        $searchParameters = [
            'q' => "*",
            'query_by' => 'video_id',
            'filter_by' => "video_id:=$videoID",
        ];
        $info = [];
        try {
            $data = $this->client->collections['video_data']->documents->search($searchParameters);
            $info = $data['hits'][0]['document'] ?? [];
        } catch (Exception|TypesenseClientError $e) {
            $this->errors[] = $e->getMessage();
            $this->Logs->register($e->getMessage(), false);
        }
        return $info;
    }


    /**
     * Obtém ID do video e ID da coleção de video_data que ainda não foram extraídos
     * @return stdClass Retorna um stdClass com o ID do video e o ID da coleção,
     * ou um stdClass com id e video_id null se não houver resultado
     **/
    public function getNotExtractedVideoID(): stdClass
    {

        $max_attempts = MAX_ATTEMPTS;
        $searchParameters = [
            'q' => "*",
            'query_by' => 'video_id',
            'filter_by' => "status_code:=0 && attempts:<$max_attempts",
            'sort_by' => 'attempts:asc', // primeiro os que tiveram menos tentativas
            'limit' => 1
        ];
        $obj = new stdClass();
        $obj->id = null;
        $obj->video_id = null;
        try {
            $data = $this->client->collections['video_data']->documents->search($searchParameters);
            $info = $data['hits'][0]['document'] ?? [];
            $obj->id = $info['id'] ?? null;
            $obj->video_id = $info['video_id'] ?? null;
            return $obj;
        } catch (Exception|TypesenseClientError $e) {
            $this->errors[] = $e->getMessage();
            $this->Logs->register($e->getMessage(), true);
        }
        return $obj;
    }


    public function updateVideoInfoByVideoID(array $new_data, string $video_id): bool
    {
        $searchParameters = [
            'q' => '*',
            'filter_by' => "video_id:=$video_id",
        ];
        try {
            // Primeiro localiza o ID do documento
            $response = $this->client->collections['video_data']->documents->search($searchParameters);
            $doc_id = $response['hits'][0]['document']['id'] ?? false;
            if ($doc_id !== false) {
                // Obs: O primeiro ID(automático) no Typesense é zero,
                // portanto uma comparação if($doc_id == 0)... seria inadequada
                $this->client->collections['video_data']->documents[$doc_id]->update($new_data); // Então atualiza com base no ID
                return true;
            }
        } catch (Exception|TypesenseClientError $e) {
            $this->errors[] = $e->getMessage();
            $this->Logs->register($e->getMessage(), true);
        }
        return false;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }


    /**
     * **CUIDADO**! Essa função apaga todos dados de uma coleção e seu schema
     * @param string $collectionName Nome da coleção a ser excluída
     **/
    public function deleteCollection(string $collectionName): bool
    {
        try {
            $this->client->collections[$collectionName]->delete();
            return true;
        } catch (Exception|TypesenseClientError $e) {
            $this->Logs->register($e->getMessage(), true);
            $this->errors[] = $e->getMessage();
        }
        return false;
    }


    /**
     * Apenas para o primeiro uso, quando a collection ainda não foi definida
     * Cria o schema das collections video_data e captions_chunks
     * @param int $vec_dim Dimensão do embedding
     * @return true Retorna true em caso de sucesso e caso aja erro o código será estopado com exit
     **/
    public function defineCollectionsSchema(int $vec_dim = VECTOR_DIMENSION): bool
    {
        $schemaVideoData = [
            "name" => "video_data",
            "fields" => [
                ["name" => "status_code", "type" => "int32"],
                ["name" => "title", "type" => "string"],
                ["name" => "video_id", "type" => "string"],
                ["name" => "attempts", "type" => "int32"],
                ["name" => "channel_name","type" => "string"],
                ["name" => "channel_id","type" => "string"],
                ["name" => "ex_date", "type" => "int64"]
            ],

        ];

        $schemaCaptionsChunk = [
            "name" => "captions_chunks",
            "fields" => [
                ["name" => "sub", "type" => "string", "stem" => true],
                ["name" => "ts", "type" => "string"],
                ["name" => "vector", "type" => "float[]", "num_dim" => $vec_dim, "optional" => true],
                ["name" => "video_data_id", "type" => "string", "reference" => "video_data.id"]
            ]
        ];

        try {
            $this->client->collections->create($schemaVideoData); // Cria a collection video_data
            $this->client->collections->create($schemaCaptionsChunk); // Cria a collection captions_chunks
        } catch (TypesenseClientError|Exception $e) {
            $this->Logs->register($e->getMessage(), true);
            exit();
        }
        return true;
    }

    /**
     * Verifica se o Typesense está ok
     */
    public function isHealthOK():bool
    {
        try {
            return (bool) $this->client->health->retrieve()['ok'];
        }catch (Exception|TypesenseClientError $e){
            $this->errors[] = $e->getMessage();
            $this->Logs->register($e->getMessage());
        }
        return false;
    }


}