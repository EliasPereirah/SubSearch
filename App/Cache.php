<?php
namespace App;
use DateTime;
use Exception;

class Cache
{


    /**
     * Verifica se existe cache para um cache_id
    **/
    public function hasCache(string $cache_id): bool
    {
        $cache_id = trim($cache_id);
        if (empty($cache_id)) {
            return false;
        }
        $cache_id = md5($cache_id);
        $path = CACHE_PATH . "/$cache_id.json";
        if (is_file($path)) {
            if($this->isCacheExpired($cache_id)){
                unlink($path);
                $cache_info_path = CACHE_PATH . "/cache_info/$cache_id.json";
                unlink($cache_info_path);
                return false; // cache expirado deletado - hasCache return false
            }
            return true;
        }
        return false;
    }



    private function isCacheExpired(string $md5_cache_id): bool
    {
        $path = CACHE_PATH . "/cache_info/$md5_cache_id.json";
        if(is_file($path)) {
            $cnt = file_get_contents($path);
            $data = json_decode($cnt);
            $dataComparar = $data->date;
            $dataAtual = date('Y-m-d H:i:s');
            try {
                $dataComparar = new DateTime($dataComparar);
                $dataAtual = new DateTime($dataAtual);
                $difference = $dataAtual->diff($dataComparar);
                if ($difference->days > CACHE_LIVE_DAYS) {
                    $this->deleteCache($md5_cache_id);
                    return true;
                }
            }catch (Exception) {
                return false;
            }
            return false;

        }
        return true;
    }


    /**
     * Salva resultado de uma busca em cache
     * @param string $cache_id ID do cache que será gerado
     * @param string $json resultados em string formatado em json
     **/
    public function saveCache(string $cache_id, string $json): bool
    {
        $cache_id = trim($cache_id);
        $cache_id = md5($cache_id);

        $path = CACHE_PATH . "/$cache_id.json";
        $info_path = CACHE_PATH . "/cache_info/$cache_id.json";
        $dt = date("Y-m-d H:i:s");
        file_put_contents($info_path, "{\"date\": \"$dt\"}"); // informação de quando o cache foi criado
        return file_put_contents($path, $json);
    }

    
    public function deleteCache(string $term): bool
    {
        if ($this->hasCache($term)) {
            $term = md5($term);
            $path = CACHE_PATH . "/$term.json";
            $path_info = CACHE_PATH . "/cache_info/$term.json";
            $rp = unlink($path);
            $rpi = unlink($path_info);
            if ($rp && $rpi) {
                return true;
            }
        }
        return false;
    }

    /**
     * Retorna uma cache com base em um cache_id se nada foi encontrado retorna false
     * @param string $cache_id termo da busca
     * @return string|bool Retorna o cache ou false caso não tenha nenhum
     */
    public function getCache(string $cache_id): string|bool
    {
        if ($this->hasCache($cache_id)) {
            $cache_id = trim($cache_id);
            $cache_id = md5($cache_id);
            $path = CACHE_PATH . "/$cache_id.json";
            return file_get_contents($path);
        }
        return false;
    }

}