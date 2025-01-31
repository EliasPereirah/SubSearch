<?php
/*
Adiciona videos de um determinado canal na collection videos_data
*/
require_once __DIR__ . "/bootstrap.php";
set_time_limit(1500); // 25 minutos -
$Database = new \App\Database();
$VideoInfo = new \App\VideoInfo();
$YouTubeApi = new \App\YouTubeApi();
$MySense = new \App\MySense();
$ChannelsManager = new \App\ChannelsManager();
$video_id = $_POST["video_id"] ?? '';
$max_videos = $_POST["max_videos"] ?? '';
$max_videos = (int) $max_videos;
if(empty($video_id)){
    exit("acesse html/dash/ para adicionar videos");
}
$channel_data = $YouTubeApi->getChannelInfoByVideoId($video_id);
$channel_name = $channel_data->channelTitle ?? '';
$channel_id = $channel_data->channelId ?? '';


$list_video = $YouTubeApi->listVideosFromChannel($channel_id, $max_videos);

foreach ($list_video as $video_info) {
    $video_id = $video_info->video_id ?? '';
    if ($VideoInfo->hasVideoAlready($video_id)) {
        echo "O video_id $video_id já está na base de dados<br>";
        continue;
    }
    $publishedAt = $video_info->publishedAt ?? '';
    $title = $video_info->title ?? '';
    if (strlen($title) == 0) {
        echo "Título para $video_id está vazio<br>";
        continue;
    }
    $description = $video_info->description ?? '';
    $url = "https://www.youtube.com/watch?v={$video_id}";
    if($VideoInfo->addInfo($title, $video_id, $publishedAt, $channel_name, $channel_id)){
        echo "Video: $video_id adicionado com sucesso<br>";
    }else{
        echo "Erro ao adicionar video: $video_id<br>";
    }
}

$ChannelsManager->generateChannelFile(); // atualiza lista de canais