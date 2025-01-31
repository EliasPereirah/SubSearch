<?php
use Typesense\Client;
use Typesense\Exceptions\TypesenseClientError;
require_once __DIR__ . "/bootstrap.php";
$client = new Client(TS_CONFIG);
$MySense = new \App\MySense();
$VoyageAI = new \App\VoyageAI();
$CaptionsHandle = new \App\CaptionsHandle();

## Cuidado
// Se por algum motivo precisar, poderá deletar as collections video_data e captions_chunks
// descomentando as duas linhas abaixo
//$MySense->deleteCollection('video_data');
//$MySense->deleteCollection('captions_chunks');

$MySense->defineCollectionsSchema(VECTOR_DIMENSION);

$searchParameters = [
    'q' => '*',
    "filter_by" => "status_code:1",

];


$num_doc = $client->collections['video_data']->documents->search($searchParameters)['found'];
echo "Numero de vídeo indexados: $num_doc <br>\n";




$num_doc = $client->collections['video_data']->documents->search([
    'q' => '*',
    "filter_by" => "status_code:0",

])['found'];
echo "Numero de vídeo faltando indexar: $num_doc <br>";

