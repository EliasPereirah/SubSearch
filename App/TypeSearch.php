<?php

namespace App;

use Exception;
use Typesense\Client;
use Typesense\Exceptions\ConfigError;
use Typesense\Exceptions\TypesenseClientError;

class TypeSearch
{
    private Client $client;
    private array $errors;
    private bool $can_do_cache = USE_CACHE;
    private Logger $Logger;
    private VoyageAI $VoyageAI;
    private SmartAction $SmartAction;

    public function __construct()
    {
        $this->SmartAction = new SmartAction();
        $this->Logger = new Logger();
        $this->VoyageAI = new VoyageAI();
        $this->errors = [];
        try {
            $this->client = new Client(TS_CONFIG);
        } catch (ConfigError $e) {
            $this->Logger->register($e->getMessage());
            exit($e->getMessage());
        }
    }

    /**
     * Faz uma busca na coleção de captions_chunks
     * @param string $query Palavra ou frase a ser buscada
     * @param bool $hybrid (default true) Se true busca também usando embeddings / vector search, do contrário apenas full text
     * @param string $channel_id (Opcional)Id do canal a ser filtrado
     * @param int $page (default página 1) offset - De onde busca começa
     * @param int $perPage (default 100) limit - Quantidade máxima de resultados a serem retornados
     * @throws Exception
     * @throws TypesenseClientError
     * @throws \Http\Client\Exception
     */
    public function search(string $query, bool $hybrid = HYBRID_SEARCH, string $channel_id = '', int $page = 1, int $perPage = 100): array
    {

        $searchParameters = [
            'q' => $query,
            'query_by' => 'sub',
            'exclude_fields' => "vector", // exclui o retorno dos embeddings do resultado
            "include_fields" => "\$video_data(*)", // inclui a collection video_data
            "page" => $page,
            "per_page" => $perPage,

        ];
        if ($channel_id) {
            $channel_id = $this->SmartAction->safeChannel($channel_id);
            $searchParameters['filter_by'] = "\$video_data(channel_id:=$channel_id)";
        }

        if ($hybrid) {
            try {
                $embedding = $this->VoyageAI->createEmbeddings($query, VOYAGE_EMBEDDING_MODEL,'query');
            } catch (Exception $exception) {
                // Houve erro na geração do embedding - retorna apenas resultado com fulltext
                $err_msg =  "Erro ao gerar embedding: {$exception->getMessage()}";
                $this->errors[] = $err_msg;
                $this->Logger->register($err_msg);
                $this->can_do_cache = false; // Não obteve resultados de vector então irá realizar cache
                return $this->client->collections['captions_chunks']->documents->search($searchParameters);
            }

            $embeddingQuery = json_encode($embedding[0]);
            $searchParameters['vector_query'] = "vector:($embeddingQuery, k: 100)";

            $searchParameters['collection'] = 'captions_chunks';
            $searchParameters = ["searches" => [$searchParameters]];
            return $this->client->multiSearch->perform($searchParameters);
        }
        return $this->client->collections['captions_chunks']->documents->search($searchParameters);

    }

    public function canDoCache(): bool
    {
        return $this->can_do_cache;
    }

}