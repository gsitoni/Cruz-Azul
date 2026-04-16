<?php
// ============================================================
//  recuperacao_de_senha.php  –  public/pages/
//  Segurança: CSRF, XSS, session segura
// ============================================================
$secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
 
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $secure,
    'httponly' => true,
    'samesite' => 'Lax'
]);
 
session_start();
 
// Headers de segurança
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
 
// Gera CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
 
$mensagem = '';
$tipo_msg = '';
 
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'sucesso') {
        $mensagem = 'Se o e-mail existir em nossa base, um código foi enviado.';
        $tipo_msg = 'sucesso';
    } elseif ($_GET['status'] === 'erro') {
        $mensagem = 'Erro ao processar solicitação. Tente novamente.';
        $tipo_msg = 'erro';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperação de senha – Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/recuperacao_de_senha.css">
</head>
<body>
 
<header>
    <h1>CRUZ-AZUL ✙</h1>
</header>
 
<div class="container">
 
    <h2 class="subtitulo">Recuperação de senha</h2>
 
    <p class="descricao">
        Informe seu e-mail abaixo e aguarde o código de verificação.
    </p>
 
    <?php if (!empty($mensagem)): ?>
        <p style="color:<?= $tipo_msg === 'erro' ? 'red' : 'darkblue' ?>;text-align:center;margin-bottom:10px;">
            <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>
 
    <form action="../../src/api/processa_recuperacao.php" method="POST" class="form">
 
        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
 
        <fieldset class="email_box">
            <legend class="legend">E-mail</legend>
            <input class="input" id="email_recuperacao" name="email_recuperacao"
                   type="email" required maxlength="254">
        </fieldset>
 
        <a href="codigo_de_verificacao.php" id="link_codigo">
            Já possuo o código de verificação
        </a>
 
        <div class="botoes">
            <button class="botao" type="button" onclick="history.back()">Voltar</button>
            <button class="botao" type="submit">Avançar</button>
        </div>
 
    </form>
 
</div>
 
<script src="../js/recuperacao_de_senha.js"></script>
</body>
</html>