<?php
namespace App;
use DateTime;
class VideoInfo
{
    private Database $Database;
    private Logs $Logs;
    private MySense $MySense;

    public function __construct()
    {
        $this->Database = new Database();
        $this->Logs = new Logs();
        $this->MySense = new MySense();
    }



    public function addInfo($title, $video_id, $publishedAt, $channel_name, $channel_id): bool
    {

        try {
            $data = new DateTime($publishedAt);
        } catch (\Exception $e) {
            $data = new DateTime();
            $this->Logs->register($e->getMessage() . " - publishedAt: $publishedAt", true);
        }
        $timestampUnix = $data->getTimestamp();
        $videoInfoInput = [
            "status_code" => 0,
            "attempts" => 0,
            "title" => $title,
            "video_id" => $video_id,
            "channel_name" => $channel_name,
            "channel_id" => $channel_id,
            "ex_date" => $timestampUnix
        ];
        return $this->MySense->addVideoInfo($videoInfoInput);
    }


    public function hasVideoAlready(string $video_id): bool
    {
        return $this->MySense->hasVideo($video_id);
    }
}