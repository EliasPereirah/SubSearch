<?php

/*
 Se desejar este arquivo pode ser executado via cron de tempos em tempos
 Pode ser a cada 1 minuto
 No entanto, o processamento só irá acontecer de acordo o tempo definido na variável $execution_interval
 Ele serve para indexar qualquer vídeo que esteja na collection videos_data seja ou não do canal tertuliarium
*/

use App\CaptionsHandle;
use App\CronsInfo;
use App\Logs;
use App\MySense;

require_once __DIR__ . "/bootstrap.php";
set_time_limit(180); // 3 minutos
$CaptionHandle = new CaptionsHandle();
$Logs = new Logs();
$CronsInfo = new CronsInfo();
$MySense = new MySense();

if (!$MySense->isHealthOK()) {
    $erros = json_encode($MySense->getErrors());
    $Logs->register($erros . " - código estopado!", true);
    exit();
}

sleep(2);
$execution_interval = EXECUTION_INTERVAL;
// Verifica se faz mais de X minuto desde a última execução
if ($CronsInfo->checkLastExecution('extract_captions', $execution_interval)) {
    echo "Faz menos de $execution_interval minuto desde a última execução. Saindo<br>\n";
    exit;
} else {
    $CronsInfo->registerExecution('extract_captions');
    echo "Executando extract_captions.php<br>\n";
}


$total_process = 0; // Inicializa o contador de processamentos
$total_external_requests = 0; // Inicializa o contador de requisições HTTP

$start_time = time();
$execution_timeout = 45; //  tempo médio de execução em segundos (45 recomendado)
// irá finalizar antes se atingir o número máximo de processamento para legendas do YouTube

/*
 o script abaixo irá ficar em loop até que o tempo de execução tenha superado o tempo
 definido na variável $execution_timeout
 Evite ter o IP banido pelo YouTube devido a várias requisições em curto espaço de tempo
 Ainda não tenho uma medida de quantas requisições são OK para o YouTube
 # OBS: Pode ser que se você estiver usando uma IP NÃO doméstico (ex: VPS, Cloud)
 que a extração das legendas só funciona usando login (Verifique o arquivo config.php para setar cookies de login)
*/


while ((time() - $start_time) < $execution_timeout) {
    echo "Rodada: $total_process <br>\n";
    $total_process++;

    // Obtém id de vídeo que não foi indexado
    ["video_id" => $video_id, "id" => $id] = get_object_vars($CaptionHandle->getNotExtractedVideoID());
    if ($video_id) {
        try {
            // seta como processado antes de processar
            // Isso vai evitar que caso esteja usando crons ou o script seja chamado duas vezes antes do primeiro
            // terminar de ser processado, que aja legendas processada duas vezes
            // deixando duplicações na base de dados
            if ($CaptionHandle->setAsProcessed($video_id)) {
                $file_path = __DIR__ . "/captions_files/$video_id.ttml";
                if ($total_external_requests >= MAX_YT_HITS) {
                    echo "Finalizando devido ter chegado a $total_external_requests requisições HTTP no YouTube<br>\n";
                    break;
                }
                echo "Pegando legenda do YouTube: $video_id<br>\n";
                $total_external_requests++;
                $url = $CaptionHandle->getSubtitleURL($video_id);
                $caption = $CaptionHandle->httpRequest($url);
                $CaptionHandle->saveCaptionOnDisk($file_path, $caption);

                $status = $CaptionHandle->processCaption($video_id, $id, $caption);
                if ($status) {
                    echo "O video_id: $video_id foi processado com sucesso<br>\n";
                    if (!$MySense->isHealthOK()) {
                        $erros = json_encode($MySense->getErrors());
                        $Logs->register($erros . " - código estopado!", true);
                        exit();
                    }
                } else {
                    $CaptionHandle->setAsFailedProcessing($video_id);
                }

            } else {
                $msg_log = "Erro, não foi possível marcar video como processado - video_id: $video_id";
                $Logs->register($msg_log, true);
            }

        } catch (Exception $e) {
            $Logs->register($e->getMessage() . " - Não foi encontrado legenda para video_id: $video_id");
            echo $e->getMessage();
        }

    } else {
        echo "Nada para ser processado no momento! :)<br>\n";
        break; // evita que caso não tenha novas legendas a serem processadas, que o script continue o loop
    }
    echo "<hr>";
}
