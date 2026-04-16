<?php
session_start();

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

$email = trim($_GET['email'] ?? '');
$tipo = $_GET['tipo'] ?? 'usuario';
$origem = $_GET['origem'] ?? 'cadastro';

if ($email === '') {
    header('Location: index.php');
    exit();
}

$titulo = 'Cadastro realizado com sucesso!';
$mensagem = 'Enviamos um e-mail de confirmacao para';
$submensagem = 'Verifique sua caixa de entrada e tambem a pasta de spam. So apos clicar no link de confirmacao o acesso sera liberado.';

if ($origem === 'login') {
    $titulo = 'Confirme seu cadastro para continuar';
    $mensagem = 'Encontramos sua conta, mas ela ainda precisa ser confirmada no e-mail enviado para';
    $submensagem = 'Abra a mensagem de confirmacao, clique em "Confirmar cadastro" e depois volte para fazer login normalmente.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmacao de Cadastro - Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/cadastro_concluido.css">
</head>
<body>

<header>
    <h1>CRUZ-AZUL</h1>
</header>

<div class="container">
    <div class="card">
        <div class="icone">??</div>

        <h2><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h2>

        <p class="mensagem">
            <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
            <strong><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></strong>.
        </p>

        <p class="submensagem">
            <?= htmlspecialchars($submensagem, ENT_QUOTES, 'UTF-8') ?>
        </p>

        <div class="botoes">
            <a href="login.php" class="botao">Ir para o login</a>
            <a href="index.php" class="botao secundario">Voltar ao inicio</a>
        </div>
    </div>
</div>

</body>
</html>
