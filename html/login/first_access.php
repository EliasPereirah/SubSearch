<?php
/**
 * No primeiro login que o usuário fazer será necessário mudar senha e email padrão
 **/
session_start();
set_time_limit(0);
require __DIR__ . "/../../bootstrap.php";
$Admin = new \App\Admin();
// É importante que a verificação de login seja feita via session nesse script em específico do contrário ao enviar um
// post com os dados do novo login (senha, email) ele vai tentar logar com os dados do post, e a não conseguira e vai
// redirecionar para página dash.php que vai mandar de volta para cá dessa forma o usuário nunca conseguiria mudar a senha
$base_url = BASE_URL;

if(!$Admin->verifyLogin('session')){
    // Se não estiver logado redireciona para dash.php
    header("Location: $base_url/dash");
    exit();
}
$msg = '';
if($Admin->needChangeLoginData()){
    // Se for o primeiro acesso do usuário vai precisar mudar senha
    $email = $_POST['email']  ?? '';
    $password = $_POST['password']  ?? '';
    if(!empty($email) && !empty(trim($password))){
        $email = strtolower(trim($email));
        $password = trim($password); // Será convertido para hash pela function changeLoginData
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $msg = "<div class='warning'>Email é inválido</div>";
        }else{
            if($Admin->changeLoginData($email, $password)){
                header("Location: $base_url/dash");
            }else{
                $msg = "<div class='warning'>Ops, houve um erro ao tentar mudar os dados de login.</div>";
            }
        }
    }
}else{
    // Não é obrigatório mudar a senha
    header("Location: $base_url/dash");
}


?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
</head>
<body>
<div class="content" style="text-align: center">
    <h1>Mude seus dados de acesso</h1>
    <p>Esse é seu primeiro login. Por favor, mude os dados de acesso.</p>
    <p><b>Atenção:</b> Guarde bem sua senha, pois não será possível mudá-la novamente.</p>
    <form method="post" action="">
        <label for="email">Email:</label>
        <input id="email" type="text" placeholder="Novo email" name="email">
        <label for="pass">Senha:</label>
        <input minlength="5" id="pass" type="password" placeholder="Nova senha" name="password">
        <button>Mudar e logar</button>
    </form>
    <?php
    echo $msg;
    ?>
</div>
</body>
<style>
    html {
        background-color: rgb(63 81 181 / 7%);
    }

    .info {
        padding: 30px 10px;
        margin: 15px;
        border: 1px solid #ccc;
        border-radius: 6px;
    }

    body {
        margin: 0;
    }

    label[for='file'] {
        padding: 8px;
        background-color: #fff;
        color: rgb(75, 69, 93);
        display: inline-block;
        border-radius: 4px;
        cursor: pointer;
        border: 1px solid #ccc;
        font-weight: 600;
    }

    .set_api_key form {
        margin-bottom: 8px;
    }

    .up_key {
        color: orange;
    }

    .add_key {
        color: blueviolet;
    }

    input#file {
        display: none;
    }

    h1 {
        /*color: #fff;*/
    }

    button {
        cursor: pointer;
        border-radius: 4px;
        padding: 9px;
        display: inline-block;
        background-color: #fff;
        color: rgb(75, 69, 93);
        border: 1px solid #ccc;
        font-weight: 600;
    }

    .vectara_corpus button {
        background-color: #4bb94b;
        font-weight: 600;
        margin-bottom: 6px;
        color: #fff;
    }

    .content {
        text-align: center;
    }

    .dash {
        border-radius: 4px;
        width: 60%;
        margin: 15px 20%;
        padding: 2px 0;
        background-color: #fff;
        min-height: 250px;
        box-sizing: border-box;
        font-size: 1.2em;
        display: none;
        color: black;
    }

    #active_btn {
        color: blueviolet;
    }

    .show {
        display: inline-block !important;
    }

    .success, .error {
        padding: 8px;
        background-color: #35bc4a;
        border-radius: 4px;
        color: #fff;
    }

    .error {
        background-color: #ff5646;
    }

    .models_list {
        padding: 25px 8px;
        box-sizing: border-box;
        background-color: #1f2ad4;
        color: #fff;
        margin-bottom: 5px;
        font-size: 1.2em;
        border-bottom-left-radius: 5px;
        border-bottom-right-radius: 5px;
    }

    select {
        padding: 8px;
        border-radius: 4px;
    }

    input {
        padding: 7px;
        border: 1px solid #ccc;
        border-radius: 4px;
        margin: 2px;
    }

    option {
        font-weight: 600;
        color: #484648;
    }

    .company {
        color: #fff;

    }

    .active_model {
        padding: 8px;
        background-color: #35bc4a;;
        color: #fff;
    }

    .warning {
        background-color: #554f4e;
        color: #fff;
        font-weight: 600;
        padding: 6px;
        border-radius: 4px;
    }

    .alert {
        background-color: #c11100;
        padding: 4px 8px;
        color: #fff;
        margin: 5px 0;
    }

    .hl {
        padding: 5px;
        border-radius: 6px;
        border: 1px solid #ccc;
    }

    .height_10px {
        height: 10px;
    }

    .height_35px {
        height: 35px;
    }


    .center {
        text-align: center;
        width: 100%;
    }

    .vectara_corpus {
        background-color: #fff;
        width: 250px;
        display: inline-block;
        padding: 0 15px;
        border-radius: 5px;
        box-sizing: border-box;
        margin-bottom: 5px;
        border: 1px solid #ccc;
    }


    button.active_corpus {
        background-color: #fff;
        border: 1px solid #ccc;
        color: #000;
    }

    .change_name {
        background-color: #282a48;
        color: burlywood;
    }

    textarea[name='sys_prompt'] {
        width: 70%;
        height: 80px;
        border-radius: 6px;
        padding: 5px;
    }

    .all_models {
        background-color: #fff;
    }

    .container {
        margin-top: 15px;
    }

    .manager,.go_to {
        border-radius: 5px;
        padding: 8px;
        margin: 0 5px;
        background-color: #fff;
        display: inline-block;
        color: #000;
        cursor: pointer;
        box-shadow: 0 0 2px 1px #00000014;
    }
    .go_to a{
        color: black;
        text-decoration: none;
    }
    .go_to a:hover{
        color: blue;
        text-decoration: underline;
    }

    .popup {
        padding: 10px;
        text-align: center;
        position: fixed;
        top: 56px;
        width: 100%;
        background-color: #eddabc;
        color: #000000;

    }
</style>
</html>