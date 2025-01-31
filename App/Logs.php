<?php
namespace App;
use App\Database;
class Logs{
    private Database $Database;
    private Logger $Logger;
    public function __construct() {
        $this->Database = new Database();
        $this->Logger = new Logger();
    }

    /**
     * Registra uma mensagem de log na base de dados
     * @param string $log_msg mensagem de log a ser registrado
     * @param bool $print se deseja imprimir na tela o erro apresentado
     * @return bool
     **/
    public function register(string $log_msg, bool $print = false):bool{
        $sql = "INSERT INTO logs (log_msg, status) VALUES (:msg, :status)";
        $binds = ['msg' => $log_msg, 'status' => 0]; // status = 0 significa que ainda não foi verificado pelo adm
        if($print){
            $this->printError($log_msg);
        }
        $this->Logger->register($log_msg);
        return $this->Database->insert($sql, $binds);
    }

    private function printError($log_error):void{
        echo "<div style='margin:4px;padding: 8px;color: #fff;background-color: rgba(237,42,39,0.82);border-radius: 4px;font-size: 1em'
              class='error'>{$log_error}</div>";
    }
}