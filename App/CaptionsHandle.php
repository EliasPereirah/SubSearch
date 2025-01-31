<?php
namespace App;
use Exception;

class CaptionsHandle {
    /**
     * Instância da classe Database.
     * @var Database
     */

    private Database $Database;
    private Logs $Logs;
    private MySense $MySense;
    private HttpRequest $HttpRequest;


    public function __construct() {
        $this->MySense = new MySense();
        $this->Database = new Database();
        $this->Logs = new Logs();
        $this->HttpRequest = new \App\HttpRequest();
        if(DO_YT_LOGIN){
            $this->HttpRequest->setCookie();
        }
        $this->HttpRequest->setUserAgent(USER_AGENT);
    }

    /**
     * Obtém o ID de vídeos cuja legenda ainda não foi extraída e cuja informações(video_id, title, etc) foram postadas
     * @return object Retorna um array com o ID do vídeo não extraído e o ID da tabela que será usado como foreign key
     */
    public function getNotExtractedVideoID(): object {
        return $this->MySense->getNotExtractedVideoID();
    }


    /**
     * Obtém URL da legenda relacionada ao vídeo
     * Será dessa URL que a legenda será extraída.
     * @param string $video_id ID do vídeo do qual se deseja obter a URL da legenda
     * @return string
     * @throws Exception Exceção vinda httpRequest
     */
    public function getSubtitleURL(string $video_id): string {
        $video_url = "https://www.youtube.com/watch?v={$video_id}";
        $cnt = $this->httpRequest($video_url);
        preg_match("/{\"captionTracks\":\[{\"baseUrl\":\"(.*?)\"/S", $cnt, $match);
        if (!empty($match[1])) {
            $caption_url = trim($match[1]);
            if(preg_match("/^\/api/", $caption_url)){
                // Pode ser que venha sem https://www.youtube.com no início
                $caption_url = "https://www.youtube.com{$caption_url}";
            }
            return json_decode('"' . $caption_url . '"');  // important
        }
        $error_msg = "Got no subtitle for: $video_id";
        if(str_contains($cnt, "LOGIN_REQUIRED")){
            $error_msg = "Got no subtitle for: $video_id - reason: LOGIN_REQUIRED";
        }
        $this->Logs->register($error_msg);
        echo $error_msg."<br>";
        return '';
    }


    /**
     * Faz uma requisição HTTP para a URL fornecida e retorna o conteúdo
     * @param string $url
     * @return string
     * @throws Exception caso a URL seja inválida
     */
    public function httpRequest(string $url): string {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->HttpRequest->get($url)->body;
        }

        throw new Exception("BAD URL: [$url]");
    }


    /**
     * Verifica se o ID do vídeo passado é válido
     * @param string $video_id string que será analisada
     * @return string retorna o ID do vídeo caso seja válido, ou uma string vázia ('') caso contrário
     */
    public function validVideoID(string $video_id): string {
        $validVideoID = filter_var($video_id, FILTER_VALIDATE_REGEXP, [
            'options' => [
                'regexp' => '/^[a-zA-Z0-9_-]{11}$/'
            ]
        ]);
        return $validVideoID ?: '';

    }


    /**
     * Recebe a legenda via parâmetro ou pega via arquivo
     * Quebra a legenda em partes (chunks) e envia para a base de dados
     * @param string $video_id id do vídeo relacionada a legenda
     * @param int $fkey id da tabela video_info que servirá como foreign key
     * @param string $raw_caption a legenda ainda não processada no formato ttml do YouTube
     * @param int $lengthLimit o tamanho médio de caracteres que cada chunk da legenda deve ter (apenas aproximação)
     **/
    public function processCaption(string $video_id, $fkey, string $raw_caption, int $lengthLimit = CHUNK_LENGTH): bool {

        $legendaInputs = [];
        /// quebra a legenda em partes menores e posta na base de dados
        preg_match_all("/<text .*?>(.*?)<\/text>/", $raw_caption, $matches);
        if (!empty($matches[0])) {
            $total_paragraph = count($matches[0]);
            $big_phrase = '';
            $currentTime = '';
            $idx = 0;
           // echo "Total de parágrafos: $total_paragraph<br>";
            foreach ($matches[0] as $paragraph) {
                $idx++;
                preg_match("/start=\"(.*?)\"/", $paragraph, $beginMatch);
                if (isset($beginMatch[1])) {
                    $begin = $beginMatch[1];
                    if (strlen($big_phrase) == 0) {
                        $currentTime = (int) $begin;
                        $horas = gmdate('H', $currentTime);
                        $minutos = gmdate('i', $currentTime);
                        $segundos = gmdate('s', $currentTime);
                        $milissegundos = round(($currentTime - floor($currentTime)) * 1000);
                        $currentTime = sprintf("%02d:%02d:%02d.%03d", $horas, $minutos, $segundos, $milissegundos);
                    }
                    preg_match("/>(.*?)<\/text>/", $paragraph, $textMatch);
                    if (!empty($textMatch[1])) {
                        $text = $textMatch[1];
                        if (strlen($big_phrase) >= $lengthLimit) {
                            $big_phrase .= " " . trim($text);
                            $big_phrase = str_replace("<", " <", $big_phrase);

                            $big_phrase = html_entity_decode($big_phrase); // &quot vira "
                            $big_phrase = strip_tags($big_phrase);
                            $big_phrase = trim($big_phrase);
                            $legendaInputs[] = [
                                "sub" => $big_phrase,
                                "ts" => $currentTime,
                                "video_data_id" => (string) $fkey
                            ];
                            $big_phrase = '';

                        } else {
                            $big_phrase .= " " . trim($text);
                            $big_phrase = str_replace("<", " <", $big_phrase);/* put space between words before strip_tags remove tags
                             to no happens things like in that phrase "uma<br />vida"  became: "umavida", but instead "uma vida" */
                            $big_phrase = html_entity_decode($big_phrase); // &quot become " and others
                            $big_phrase = strip_tags($big_phrase);
                            $big_phrase = trim($big_phrase);
                            if ($idx == $total_paragraph) {
                                //echo "Last<br>";
                                if(strlen($big_phrase) > 45){
                                    // Se a última frase for muito pequena erá descartar
                                    // Typesense parece dar muito valor para frases curtas
                                    // O que acaba sendo ruim
                                    $legendaInputs[] = [
                                        "sub" => $big_phrase,
                                        "ts" => $currentTime,
                                        "video_data_id" => (string) $fkey
                                    ];
                                }

                            }
                        }
                    } else {
                        echo "Esse paragráfo ($idx) não possui texto: {$video_id} <br>\n";
                    }
                } else {
                    echo "Não foi encontrado o tempo de início desse paragráfo ($idx) de video_id {$video_id} <br>\n";
                }
            }
            if(!empty($legendaInputs)){
                if($this->MySense->addSubtitleSnippet($legendaInputs, true)){
                    return true;
                }else{
                    $error_text = json_encode($this->MySense->getErrors());
                    $this->Logs->register("Erro ao inserir legenda para video_id: {$video_id}. Error: $error_text");
                    return false;
                }
            }
        } else {
            $this->Logs->register("youtube_caption - A expressão regular não encontrou dados para {$video_id}");
            return false;
        }
        return true;
    }





    /**
     * Atualiza a tabela video_info com status_code 1 significado já processado
     * Mesmo que aja algum erro na extração de legenda será marcado como já processado
     * E não haverá nova tentativa de extração
     * @param string $video_id ID do vídeo que teve a legenda extraída
     * @return bool
     **/
    public function setAsProcessed(string $video_id): bool {
        $new_data = [
            'status_code' => 1
        ];
        return $this->MySense->updateVideoInfoByVideoID($new_data, $video_id);
    }

    /**
     * Marca video como não processado e incrementa o contador de tentativas de processamento
     * @param string $video_id ID do vídeo que não foi processado
     *
    **/
    public function setAsFailedProcessing(string $video_id):bool
    {

        $this->Logs->register("Falha ao processar $video_id", true);
        $data = $this->MySense->getInfoByVideoID($video_id);
        $attempts = $data['attempts'] ?? 0;
        $attempts++;
        $new_data = [
            "status_code" => 0,
            "attempts" => $attempts
        ];
        return $this->MySense->updateVideoInfoByVideoID($new_data, $video_id);
    }

    public function setAsNotProcessed(string $video_id):bool
    {

        $new_data = [
            "status_code" => 0
        ];
        return $this->MySense->updateVideoInfoByVideoID($new_data, $video_id);
    }




    public function saveCaptionOnDisk($file_path, $caption): false|int {
        $status = file_put_contents($file_path, $caption);
        if ($status === false) {
            $this->Logs->register("Erro ao salvar legenda: {$file_path}");
        }
        return $status;
    }


}
