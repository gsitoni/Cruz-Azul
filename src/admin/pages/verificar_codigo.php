<?php

session_start();

if (
    !isset($_SESSION['codigo_telegram']) ||
    !isset($_SESSION['usuario_temp'])
) {

    header('Location: login.php');

    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $codigo = trim($_POST['codigo'] ?? '');

    // ==========================
    // VALIDA
    // ==========================

    if ($codigo == $_SESSION['codigo_telegram']) {

        // LOGIN DEFINITIVO

        $_SESSION['usuario'] = [
            'id_usuario' => $_SESSION['usuario_temp']['id_usuario'],
            'email' => $_SESSION['usuario_temp']['email'],
            'tipo' => $_SESSION['usuario_temp']['tipo']
        ];

        $_SESSION['admin_autenticado'] = true;

        // LIMPA TEMPORÁRIOS

        unset($_SESSION['codigo_telegram']);
        unset($_SESSION['usuario_temp']);

        header('Location: dashboard.php');

        exit();

    } else {

        $erro = "Código inválido.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Verificar Código</title>

<style>

body{
    font-family: Arial;
    background:#f4f7fb;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}

.box{
    background:white;
    padding:40px;
    border-radius:12px;
    width:350px;
    box-shadow:0 5px 20px rgba(0,0,0,.1);
}

input{
    width:100%;
    padding:12px;
    margin-top:10px;
    border-radius:8px;
    border:1px solid #ccc;
}

button{
    width:100%;
    margin-top:20px;
    padding:12px;
    border:none;
    border-radius:8px;
    background:#1565c0;
    color:white;
    cursor:pointer;
}

.erro{
    color:red;
    margin-top:10px;
}

</style>

</head>
<body>

<div class="box">

<h2>Verificação Telegram</h2>

<p>
Digite o código enviado no Telegram.
</p>

<form method="POST">

<input
    type="number"
    name="codigo"
    placeholder="Código"
    required
>

<button type="submit">
Verificar
</button>

</form>

<?php if(isset($erro)): ?>

<div class="erro">

<?= $erro ?>

</div>

<?php endif; ?>

</div>

</body>
</html>