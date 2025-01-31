<?php
require_once __DIR__ ."/../../bootstrap.php";
session_start();
$Admin = new \App\Admin();
$base_url = BASE_URL;

if(!$Admin->verifyLogin()){
    header("Location: $base_url/login");
    exit();
}

// se for o primeiro acesso
if($Admin->needChangeLoginData()){
    header("Location: $base_url/login/first_access.php");
    exit('Houve um erro ao redirecionar');
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?= BASE_URL ?>/favicon.png">
    <title>Dashboard</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/dash.css">
</head>
<body>
<div class="container">
    <h1>📺 Adicione Canais</h1>
    <p>Informe o ID de um vídeo do canal que deseja indexar.</p>
    <form id="meuFormulario" method="post">
        <div class="form-group">
            <label for="videoId">ID do Vídeo:</label>
            <input required type="text" id="videoId" name="video_id" placeholder="Insira o ID do vídeo aqui">
            <br><br>
            <label for="max_video">Qual o número de vídeos serão indexados?</label>
            <input required type="number" id="max_video" name="max_videos" placeholder="Max vídeos">
        </div>
        <button type="submit">Indexar Canal</button>
    </form><br>
    <div id="resultado">
        <?php
        $videoId = $_POST["video_id"] ?? null;
        if($videoId){
            require_once __DIR__ . "/../../extract_video_info.php";
        }
        ?>
    </div>
</div>
</body>
</html>