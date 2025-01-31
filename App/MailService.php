<?php
namespace App;

class MailService
{

    public function sendMail($api_key, $from_name, $from_email, $to_name, $to_email, $subject, $body, $is_html = false):bool
    {
        $Mail = new SendGrid(); // se for usar outro serviço crie a nova classe com a function sendMail
        $Mail->setApiKey($api_key);
        return $Mail->sendMail($from_name, $from_email, $to_name, $to_email, $subject, $body, $is_html);
    }

}