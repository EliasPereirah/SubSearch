<?php
namespace App;
use Typesense\Client;
use Typesense\Exceptions\TypesenseClientError;

class ChannelsManager
{
    private Client $client;
    private Logger $Logger;

    public function __construct()
    {
        $this->Logger = new Logger();
        $this->client = new Client(TS_CONFIG);
    }


    private function listChannels(): array
    {
        // não tem como listar mais de 250 de uma vez
        // será necessário fazer paginação
        $page = 1;
        $searchParameters = [
            'q' => "*",
            'include_fields' => 'channel_name,channel_id',
            'page' => $page,
            'limit' => 250
        ];
        $all_hits = [];
        $all_channels = [];
        try {
            $response = $this->client->collections['video_data']->documents->search($searchParameters);
            $hits = $response['hits'];
            while (count($hits) > 0) {
                $all_hits[] = $hits;
                $page++;
                $searchParameters['page'] = $page;
                $response = $this->client->collections['video_data']->documents->search($searchParameters);
                $hits = $response['hits'];
            }
            foreach ($all_hits as $the_hit) {
                foreach ($the_hit as $item) {
                    $channel_name = $item['document']['channel_name'];
                    $channel_id = $item['document']['channel_id'];
                    if (!in_array($channel_id, array_keys($all_channels))) {
                        $all_channels[$channel_id] = $channel_name;
                    }
                }
            }
            $collator = new \Collator('pt_BR');
            $collator->asort($all_channels);
            return $all_channels;
        } catch (\Http\Client\Exception|TypesenseClientError $exception) {
            $this->Logger->register($exception->getMessage());
        }
        return [];
    }


    /**
     * Gera um arquivo JSON com lista dos autores ordenado
    **/
    public function generateChannelFile():bool{
        $all_channels = $this->listChannels();
        $arr = [];
        $arr['info'] = ['criado_em' => date("Y-m-d H:i:s")];
        foreach ($all_channels AS $channel_id => $channel_name) {
            $arr['channels'][] = ['name' => $channel_name, 'id' => $channel_id];
        }
        return file_put_contents(PUBLIC_DIR.'/channels.json', json_encode($arr)) !== false;
    }

}