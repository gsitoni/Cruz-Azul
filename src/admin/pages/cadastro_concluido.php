<?php
session_start();

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

$email = trim($_GET['email'] ?? '');
$origem = $_GET['origem'] ?? 'cadastro';

if ($email === '') {
    header('Location: ./login.php');
    exit();
}

$titulo = 'Administrador cadastrado com sucesso';
$mensagem = 'Enviamos um e-mail de confirmacao para';
$submensagem = 'Abra a mensagem, confirme o cadastro e depois volte para acessar o painel administrativo com autenticacao em duas etapas.';
$badge = 'Cadastro criado';
$acaoPrincipal = 'Ir para o login do admin';
$acaoSecundaria = 'Voltar ao portal do admin';

if ($origem === 'login') {
    $titulo = 'Confirmacao pendente para liberar o painel';
    $mensagem = 'Encontramos sua conta administrativa, mas ela ainda precisa ser confirmada no e-mail enviado para';
    $submensagem = 'Depois de clicar em "Confirmar cadastro" no e-mail, retorne aqui para fazer o login e concluir a validacao 2FA.';
    $badge = 'Confirmacao pendente';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro Concluido - Admin Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/cadastro_concluido.css">
</head>
<body>
    <main class="success-shell">
        <section class="success-panel success-panel--brand">
            <span class="eyebrow">Painel administrativo</span>
            <h1>Cruz Azul Admin</h1>
            <p>
                A liberacao do painel continua protegida: confirmacao por e-mail primeiro, autenticacao em duas etapas logo depois.
            </p>
            <div class="status-card">
                <span class="status-card__label"><?= htmlspecialchars($badge, ENT_QUOTES, 'UTF-8') ?></span>
                <strong><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></strong>
                <p>Use exatamente este e-mail para concluir a ativacao da conta administrativa.</p>
            </div>
        </section>

        <section class="success-panel success-panel--content">
            <span class="chip"><?= htmlspecialchars($badge, ENT_QUOTES, 'UTF-8') ?></span>
            <div class="icon-wrap" aria-hidden="true">OK</div>

            <h2><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h2>

            <p class="message">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
                <strong><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></strong>.
            </p>

            <p class="submessage"><?= htmlspecialchars($submensagem, ENT_QUOTES, 'UTF-8') ?></p>

            <div class="steps">
                <div class="step">1. Abra o e-mail de confirmacao enviado pela Cruz Azul.</div>
                <div class="step">2. Clique em "Confirmar cadastro" para ativar a conta admin.</div>
                <div class="step">3. Volte ao login do admin para entrar e validar o 2FA.</div>
            </div>

            <div class="actions">
                <a href="./login.php" class="button button--primary"><?= htmlspecialchars($acaoPrincipal, ENT_QUOTES, 'UTF-8') ?></a>
                <a href="../index.php" class="button button--secondary"><?= htmlspecialchars($acaoSecundaria, ENT_QUOTES, 'UTF-8') ?></a>
            </div>
        </section>
    </main>
</body>
</html>
