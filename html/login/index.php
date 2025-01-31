<?php
require_once __DIR__."/../../bootstrap.php";
session_start();
set_time_limit(0);
$Admin = new \App\Admin();
if($Admin->verifyLogin()){
    $base_url = BASE_URL;
    // Se já estiver logado
    header("Location: $base_url/dash");
    exit();
}
$msg = "";
$tentativa = $_POST['email']  ?? '';
if($tentativa){
    $msg = "<div class='warning'>Dados de login incorretos!</div>";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="icon" href="../favicon.png">
</head>
<body>
<div class="container">
    <form id="loginForm" class="form" method="post">
        <h1 class="new_post">Login</h1>
        <?= $msg ?>
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="text" id="email" name="email" placeholder="E-mail">
            <label for="password">Senha:</label>
            <input id="password" name="password" type="password" placeholder="Senha">
        </div>
        <button type="submit">Logar</button>
    </form>
</div>
</body>
<script>
    let all_inputs = document.querySelectorAll("input, textarea");
    all_inputs.forEach(input=>{
        input.setAttribute("required","required")
    })
</script>
<style>
    :root {
        --primary-color: #2563eb;
        --text-color: #1f2937;
        --background-color: #f3f4f6;
        --input-background: #ffffff;
        --border-color: #e5e7eb;
        --shadow-color: rgba(0, 0, 0, .1)
    }
    h1{
        font-size: 1.1em;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box
    }

    body {
        font-family: Inter, system-ui, -apple-system, sans-serif;
        background-color: var(--background-color);
        color: var(--text-color);
        line-height: 1.5;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center
    }

    .container {
        width: 50%;
        margin: 0 25%;

        padding: 2rem
    }

    .form {
        background-color: var(--input-background);
        padding: 2rem;
        border-radius: 1rem;
        box-shadow: 0 4px 6px var(--shadow-color)
    }

    h1 {
        font-size: 1.875rem;
        font-weight: 600;
        margin-bottom: 2rem;
        color: var(--text-color);
        text-align: center
    }

    .form-group {
        margin-bottom: 1.5rem
    }

    label {
        display: block;
        margin-bottom: .5rem;
        font-weight: 500;
        color: var(--text-color)
    }

    input, textarea {
        width: 100%;
        padding: .65rem;
        border: 1px solid var(--border-color);
        border-radius: .5rem;
        background-color: var(--input-background);
        color: var(--text-color);
        font-size: 1rem;
        transition: border-color .2s, box-shadow .2s
    }

    textarea {
        min-height: 220px;
        resize: vertical
    }

    input:focus, textarea:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px #2563eb1a
    }

    button {
        width: 100%;
        padding: .75rem;
        background-color: var(--primary-color);
        color: #fff;
        border: none;
        border-radius: .5rem;
        font-size: 1rem;
        font-weight: 500;
        cursor: pointer;
        transition: background-color .2s
    }

    button:hover {
        background-color: #1d4ed8
    }

    ::placeholder {
        color: #9ca3af
    }
    .status_msgs,.already_logged{
        padding: 1px 6px 4px 6px;
        background-color: #b9b9b0;
        border-radius: 4px;
        color: #fff;
    }
    .already_logged{
        background-color: #83ffc5;
        color: #1f2937;
    }
</style>
</html>