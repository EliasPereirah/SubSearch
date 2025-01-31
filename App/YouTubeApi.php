<?php


namespace App;

use Google\Client as GClient;
use Google\Service\YouTube as YouTubeService;

class YouTubeApi {
    private \Google\Client $client;
    private \Google\Service\YouTube $service;
    private $all_videos_id = [];
    private array $videos_info = [];
    public function __construct() {
        $this->client = new GClient();
        $this->client->setApplicationName("subsearch");
        $this->client->setDeveloperKey(YOUTUBE_API_KEY);
        $this->service = new YouTubeService($this->client);

    }

    public function getChannelInfoByVideoId($video_id) {
        $queryParams = [
            'id' => $video_id
        ];
        $response = $this->service->videos->listVideos('snippet', $queryParams)->getItems();
        $channel_info = new \stdClass();
        if (!empty($response[0]->snippet)) {
            $channel_info->channelId = $response[0]->snippet['channelId'];
            $channel_info->channelTitle = $response[0]->snippet['channelTitle'];
        }
        return $channel_info;
    }


    public function listVideosFromChannel($channelId, $maxVideos = 1000, $nextPageToken = null):array {


        /*
        * Existe um bug na API do YouTube que não retorna em listSearch() os vídeos por ordem de publicação corretamente
        * Um workaround para isso é usar a listagem por playlist, no caso a playlist padrão criado pelo YouTube
        * O ID de tal playlist é formada da seguinte maneira
        * O próprio ID do canal substituíndo-se o segundo caractere pela letra U
        * Ex: O canal Tertuliarium tem o ID UCFllSbD0-d6gnhQa1htGnJA
        * Nesse caso o ID da playlist será: UUFllSbD0-d6gnhQa1htGnJA
        */
        $playlistID  =  substr_replace($channelId, "U", 1, 1);; // coloca U no lugar do segundo caractere

        $queryParams = [
            //'channelId' => $channelId,
            'playlistId' => $playlistID,
            'maxResults' => $maxVideos, // the limit of the api is 50 per call, bigger number will just return 50
            //'order' => 'date',
            'pageToken' => $nextPageToken,
            //'type' => 'video'
        ];


        $response = $this->service->playlistItems->listPlaylistItems('snippet', $queryParams); // voltar

        //$response = $this->service->search->listSearch('snippet', $queryParams);
        $nextPageToken = $response['nextPageToken'];
        //echo "nextPageToken: $nextPageToken<br>\n";
        //echo "MaxVideo: $maxVideos<br>\n";
       // echo "<hr>";
        foreach ($response as $item) {
            $thumbnail_url = $item->snippet->thumbnails->default->url ?? ''; // para identificar se é um evento futuro
            // se for um upcoming video ou live, na url da thumbnail deve ter a palavra live

            $obj = new \stdClass();
            $video_id = $item->snippet->resourceId->videoId ?? '';
            if(preg_match("/live/i", $thumbnail_url)){
               //  echo "O [$video_id] é uma live ou live futura";
                // lives só serão adicionadas depois de já terem sido transmitidas
                continue;
            }


            // echo "VideoID: {$video_id}<br>";
            if (!in_array($video_id, $this->all_videos_id)) {
                $this->all_videos_id[] = $video_id;
                $obj->video_id = $video_id;
                $obj->title = $item->snippet->title;
                $obj->publishedAt = $item->snippet->publishedAt;
                $obj->description = $item->snippet->description;
                $this->videos_info[] = $obj;
            }
            //echo "Title: {$item->snippet->title}<hr>";
        }

        if ($nextPageToken == null OR (count($this->all_videos_id) >= $maxVideos)) {
            //echo "<br>Chegou ao final<br>";
            return $this->videos_info;
        } else {
            $maxVideos -= 50;
            if($maxVideos < 0){
               return $this->videos_info;
            }
            $this->listVideosFromChannel($channelId, $maxVideos, $nextPageToken);
        }
        return $this->videos_info;
    }



    public function listCompletedLives($channelId, $maxResults = 50, $pageToken = null): array {

        $queryParams = [
            'channelId' => $channelId,
            'maxResults' => $maxResults,
            'pageToken' => $pageToken,
            'part' => 'snippet',
            'eventType' => 'completed',
            'type' => 'video', // Certifique-se de que está buscando vídeos
            'order' => 'date' // Ordena por data de publicação, para garantir que você pegue as lives mais recentes
        ];

        // Faz a chamada para o endpoint search.list
        $response = $this->service->search->listSearch('snippet', $queryParams);

        $completedLives = [];
        foreach ($response['items'] as $item) {
            $obj = new \stdClass();
            $obj->video_id = $item['id']['videoId'];
            $obj->title = $item['snippet']['title'];
            $obj->description = $item['snippet']['description'];
            $obj->published_at = $item['snippet']['publishedAt'];
            $completedLives[] = $obj;
        }

        // Verifica se há mais páginas de resultados
        if (isset($response['nextPageToken'])) {
            $completedLives = array_merge($completedLives, $this->listCompletedLives($channelId, $maxResults, $response['nextPageToken']));
        }

        return $completedLives;
    }



}
