<?php
namespace App;
use DateTime;

class CronsInfo
{
    private Database $Database;
    private Logs $Logs;
    public function __construct()
    {
        $this->Database = new Database();
        $this->Logs = new Logs();
    }

    /**
     * Verifica se uma cron pode ser executada baseado no tempo desde sua última execução
     *
     * @param string $cronName Nome da cron a ser verificada
     * @param int $minutes Intervalo mínimo em minutos entre execuções
     * @return bool True se a cron pode ser executada, False caso contrário
     */
    public function checkLastExecution(string $cronName, int $minutes): bool
    {

        $sql = "SELECT last_execution FROM crons_info WHERE cron_name = :name ORDER BY last_execution DESC LIMIT 1";
        $binds = ['name' => $cronName];

        $result = $this->Database->select($sql, $binds)->fetch();

        if (!$result || empty($result->last_execution)) {
            return false; // Cron nunca foi executada
        }

        try {
            $lastExecution = new DateTime($result->last_execution);
            $now = new DateTime();
            $interval = $now->diff($lastExecution);
            $diffInMinutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
            return $diffInMinutes < $minutes;
        } catch (\Exception $e) {
            $this->Logs->register($e->getMessage(), true);
            exit();
        }
    }


    /**
     * Registra a execução de uma cron
     * @param string $cronName Nome da cron executada
     * @return bool True se o registro foi bem-sucedido, False caso contrário
     */
    public function registerExecution(string $cronName): bool{
        $sql = "UPDATE crons_info SET last_execution = CURRENT_TIMESTAMP WHERE cron_name = :name";
        $binds = ['name' => $cronName];
        if(!$this->Database->update($sql, $binds)){
            // primeira execução
            $sql = "INSERT INTO crons_info (cron_name) VALUES (:name)";
            return $this->Database->insert($sql,['name' => $cronName]);
        }
        return true;
    }

}