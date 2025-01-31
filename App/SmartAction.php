<?php

namespace App;
class SmartAction
{
    private $db;
    private array $suggestions;

    public function timeToSeconds(int $hour, int $minute, int $seconds)
    {
        return (($hour * 60) * 60) + ($minute * 60) + $seconds;
    }



    /**
     * Remove qualquer caractere que não seja ".", "-", "_" e letras do alfabeto
     * @param string $channel_id String a ser na
     * @return string sanitizada
    **/
    public function safeChannel(string $channel_id):string
    {
        $channel_id = strip_tags($channel_id);
        return preg_replace("/[^a-z0-9-_]/i", '', $channel_id);

    }


    /**
     * Remove pagination from search term: Example: consciência/2 -> consciência
     */
    function getTerm($term):string
    {
        $term = trim($term);
        $term = preg_replace("/\/[0-9]+$/", "", $term);
        $term = str_replace("/", " ", $term);
        $term = preg_replace("/\s+/"," ", $term);
        return trim($term);
    }

    public function getNextPage($term): int
    {
        $term = trim($term);
        $explodedTerm = explode('/', $term);
        if (count($explodedTerm) > 1) {
            $nextPage = (int) $explodedTerm[count($explodedTerm) - 1];
            $nextPage++;
            if ($nextPage > 100) {
                $nextPage = 101;
            }
            if ($nextPage > 1) {
                return $nextPage;
            }
        }
        return 2;
    }


    private function getDB():Database
    {
        if (empty($this->db)) {
            $this->db = new Database();
        }
        return $this->db;
    }


    public function getTimeComponents(string $time): array
    {
        $hour = (int)substr($time, 0, strpos($time, ":"));
        $minute = (int)substr($time, 3, 2);
        $seconds = (int)substr($time, 6, 2);
        $microseconds = (int)substr($time, 9, 3);
        if ($microseconds > 500) {
            $seconds += 1;
        }
        $showMinute = substr($time, 0, 8);

        return [
            $hour,
            $minute,
            $seconds,
            $showMinute
        ];
    }


    public function addStats(string $term, int $is_cache):bool
    {
        // O ip do usuário é anonimizado com md5
        $db = $this->getDB();
        $referer_full = $_SERVER['HTTP_REFERER'] ?? '';
        $referer = preg_replace("/(http(s)?:\/\/)/","", $referer_full);
        $referer = preg_replace("/\/.*/","", $referer);
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? false;
        if(!$ip){
            $ip =  $_SERVER['REMOTE_ADDR'] ?? 'no-ip';
        }
        $ip .= $_SERVER['HTTP_USER_AGENT'] ?? '';
        $term = md5($term);
        $ip = md5($ip);
        $sql = "INSERT INTO statistics(referer, ip, term, is_cache) VALUES (:referer, :ip, :term, :is_cache)";
        $binds = [':referer' => $referer, ':ip' => $ip, ':term' => $term, ':is_cache' => $is_cache];
        return $db->insert($sql, $binds);
    }

    public function doHighlight($subtitle_part, $highlight_snippet)
    {
        $subtitle_part = " $subtitle_part ";
        // ^^ coloca espaço no final para regex ser capaz de capturar
        // palavras no início e no final da string

        preg_match_all("/<mark>(.*?)<\/mark>/", $highlight_snippet, $matches);
        $to_highlight = $matches[1] ?? [];
        foreach ($to_highlight as $hl) {
            $hl = trim($hl);
            if (mb_strlen($hl) > 2) {
                $hl_escaped = preg_quote($hl, '/');
                $subtitle_part = preg_replace("/\b{$hl_escaped}\b/", "<em>{$hl}</em>", $subtitle_part, 1);
            }

        }
        return trim($subtitle_part);
    }
}