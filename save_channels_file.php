<?php
use App\ChannelsManager;
require_once __DIR__ . "/bootstrap.php";
echo "<pre>";
// Gera um arquivo JSON com lista de canais
$ChannelsManger = new ChannelsManager();
$re = $ChannelsManger->generateChannelFile();
if($re){
    echo "Gerado com sucesso";
}else{
    echo "Erro ao gerar arquivo";
}