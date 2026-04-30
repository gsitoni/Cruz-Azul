<?php
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

$status = $_GET['status'] ?? 'info';
$mensagem = $_GET['msg'] ?? 'A confirmacao foi processada.';
$email = trim($_GET['email'] ?? '');
$tipo = strtolower(trim($_GET['tipo'] ?? 'admin'));

$icone = '!';
$titulo = 'Confirmacao processada';
$descricao = 'Seu acesso administrativo esta quase pronto.';

if ($status === 'sucesso') {
    $icone = 'OK';
    $titulo = 'Cadastro administrativo confirmado';
    $descricao = 'O painel ja esta liberado para este perfil apos o login e a verificacao em duas etapas.';
} elseif ($status === 'erro') {
    $icone = 'X';
    $titulo = 'Nao foi possivel confirmar';
    $descricao = 'Revise o link recebido por e-mail ou solicite um novo fluxo de confirmacao.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmacao do Admin - Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/confirmacao_realizada.css">
</head>
<body>
    <main class="confirmation-shell">
        <section class="confirmation-card">
            <div class="badge">Painel administrativo</div>
            <div class="icon" aria-hidden="true"><?= htmlspecialchars($icone, ENT_QUOTES, 'UTF-8') ?></div>
            <h1><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="lead"><?= htmlspecialchars($descricao, ENT_QUOTES, 'UTF-8') ?></p>

            <div class="message message--<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
            </div>

            <p class="account">
                <?php if ($email !== ''): ?>
                    Conta vinculada a <strong><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></strong>.
                <?php else: ?>
                    Nenhum e-mail foi identificado neste fluxo.
                <?php endif; ?>
            </p>

            <div class="actions">
                <a href="./login.php" class="button">Ir para o login do admin</a>
                <a href="../index.php" class="button button--secondary">Voltar ao portal admin</a>
            </div>

            <?php if ($tipo === 'admin' && $status !== 'sucesso'): ?>
                <p class="support">
                    Se o problema persistir, refaca o cadastro administrativo para gerar um novo link de confirmacao.
                </p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
