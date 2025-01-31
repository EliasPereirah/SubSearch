<?php

namespace App;

class Admin
{

    private Database $Database;

    public function __construct()
    {
        $this->Database = new Database();
    }


    /**
     * Verifica se o usuário está logado
     * @param string $by_post_or_session A verificação deve ser feita com $_POST ou $_SESSION ?
     * - 'both' para ambos
     * - 'session' - para verificar via $_SESSION
     *  - 'post' - para verificar via $_POST
     **/
    public function verifyLogin(string $by_post_or_session = 'both'): bool
    {

        if (($by_post_or_session == 'post' OR $by_post_or_session == 'both')  && !empty($_POST['email']) && !empty($_POST['password'])) {
            $email = strtolower(trim($_POST['email']));
            $password = trim($_POST['password']);
            $password = hash('sha256', $password);
            $binds = ['email' => $email, 'password' => $password];
            $sql = "SELECT id FROM adm_login WHERE email = :email AND password = :password LIMIT 1";
            if($this->Database->select($sql, $binds)->rowCount()){
                $this->setSession($email, $password);
                return true;
            }
            return false;
        } elseif (($by_post_or_session == 'session' OR $by_post_or_session == 'both') && !empty($_SESSION['email']) && !empty($_SESSION['password'])) {
            $binds = ['email' => $_SESSION['email'], 'password' => $_SESSION['password']];
            $sql = "SELECT id FROM adm_login WHERE email = :email AND password = :password LIMIT 1";
            return $this->Database->select($sql, $binds)->rowCount() > 0;
        }
        return false;
    }


    /**
     * Seta uma sessão no navegador
     * @param string $email Email do usuário iniciando login
     * @param string $password Hash (sha256) da senha do usuário iniciando login
     * @return void
     **/
    public function setSession(string $email, string $password):void{
        $_SESSION['email'] = $email;
        $_SESSION['password'] = $password;
    }


    /**
     * Muda senha e email de acesso do administrador
     * @param string $new_email Novo email
     * @param string $new_password Nova senha
     * @return bool Se a senha foi ou não modificada
     **/
    public function changeLoginData(string $new_email, string $new_password):bool
    {
        $old_email = $_SESSION['email'] ?? '';
        $old_password = $_SESSION['password'] ?? '';
        $new_email = strtolower(trim($new_email));
        $new_password = trim($new_password);
        $new_password = hash('sha256', $new_password);
        $sql = "UPDATE adm_login SET email = :email, password = :password WHERE email = :old_email AND password = :old_password";
        $binds = [
            'email' => $new_email,
            'password' => $new_password,
            'old_email' => $old_email,
            'old_password' => $old_password
        ];
        if($this->Database->update($sql, $binds)){
            $this->setSession($new_email, $new_password); // Inicia nova sessão com dados atualizados
            return true;
        }
        return false;
    }


    /**
     * Verifica se o usuário está usando email e senha padrão
     * @return bool Retorna um boolean representando se precisa ou não mudar a senha
     **/
    public function needChangeLoginData():bool
    {

        $default_email = "email@localhost"; // email padrão
        $default_pass = hash('sha256', "password"); // senha padrão
        $sql = "SELECT id FROM adm_login WHERE email = :email AND password = :password LIMIT 1";
        $binds = ['email' => $default_email, 'password' => $default_pass];
        return $this->Database->select($sql, $binds)->rowCount() > 0;
    }

}