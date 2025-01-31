<?php

namespace App;
class Logger
{
    private Database $Database;
    private MailService $MailService;
    private string $project_name;
    public function __construct()
    {
        $this->Database = new Database();
        $this->MailService = new MailService();
        $this->project_name = $_ENV['PROJECT_NAME'] ?? '';

    }

    /**
     * Registra uma mensagem de log na base de dados
     * @param string $log_msg mensagem de log a ser registrado
     * @param bool $print se deseja imprimir na tela o erro apresentado
     * @return bool
     **/
    public function register(string $log_msg, bool $print = false): bool
    {
        $sql = "INSERT INTO sys_logs (log_msg, status, project) VALUES (:msg, :status, :project)";
        $binds = ['msg' => $log_msg, 'status' => 0,'project'=> $this->project_name]; // status = 0 significa que ainda não foi verificado pelo adm
        if ($print) {
            $this->printError($log_msg);
        }
        if($this->canSendLogByMail()){
             $this->sendLogToEmail("$this->project_name Erro - Encontrado", $log_msg, true);
        }
        return $this->Database->insert($sql, $binds);
    }

    private function printError($log_error): void
    {
        echo "<div style='padding: 8px;color: #fff;background-color: rgba(237,42,39,0.82);border-radius: 4px;font-size: 1em'
              class='error'>{$log_error}</div>";
    }

    private function sendLogToEmail(string $subject, string $body, bool $is_html): bool
    {
            $sender_name = $_ENV['ADM_NAME'] ?? '';
            $sender_mail = $_ENV['SENDGRID_MAIL'] ?? '';
            $to_email = $_ENV['MAIL_FOR_BUG_REPORTS'] ?? '';
            $to_name = 'User';
            $api_key = $_ENV['SENDGRID_API_KEY'] ?? '';
            if(!ENABLE_EMAIL_LOGGING){
                return false;
            }
            $success = $this->MailService->sendMail($api_key, $sender_name, $sender_mail, $to_name, $to_email, $subject, $body, $is_html);
            if($success){
                $this->setLastSendMail();
            }
            return $success;
    }




    /**
     * Verifica se pode enviar um novo e-mail informando sobre erros
     * @return boolean - Caso nenhum e-mail tenha sido enviado nas últimas 12 horas retorna true, do contrário false
     **/
    private function canSendLogByMail(): bool
    {
        $sql = "SELECT id FROM sys_logs WHERE has_send_mail = 1 AND project = :project AND created_at >= NOW() - INTERVAL 12 HOUR LIMIT 1";

        // algum e-mail enviado nas últimas 12 horas
        // nesse caso retorna false - significando não poder enviar novos e-mails para evitar muitos envios
        if($this->Database->select($sql, ['project'=> $this->project_name])->rowCount() > 0){
            return false;
        }

        // Nenhum e-mail enviado nas últimas 12 horas, pode enviar um novo e-mail
        return true;
    }




    /***
     * Marca o último e-mail como enviado
     ***/
    private function setLastSendMail():void
    {
        $sql = "UPDATE sys_logs SET has_send_mail = :has_send_mail WHERE project = :project ORDER BY id DESC LIMIT 1";
        $binds = ['has_send_mail' => 1, 'project' => $this->project_name];
        if(!$this->Database->update($sql, $binds)){
            // Se não conseguiu atualizar possívelmente é porque ainda não tem nenhum log no sistema
            // É preciso colocar um para que o sistema de e-mail funcione adequadamente
            $sql = "INSERT INTO sys_logs (log_msg, status, has_send_mail, project) VALUES (:msg, :status, :has_send_mail, :project)";
            $binds = ['msg' => 'log inicial', 'status' => 1,'has_send_mail'=> 1,'project'=> $this->project_name]; // status = 0 significa que ainda não foi verificado pelo adm
            $this->Database->insert($sql, $binds);

        }

    }

}