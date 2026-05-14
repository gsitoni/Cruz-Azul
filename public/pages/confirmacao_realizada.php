<?php
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

$status = $_GET['status'] ?? 'info';
$mensagem = $_GET['msg'] ?? 'A confirmacao foi processada.';
$email = trim($_GET['email'] ?? '');
$icone = '??';
$titulo = 'Status da confirmacao';

if ($status === 'sucesso') {
    $icone = '';
    $titulo = 'Confirmacao concluida';
} elseif ($status === 'erro') {
    $icone = '??';
    $titulo = 'Nao foi possivel confirmar';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro Confirmado - Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/confirmacao_cadastro.css">
</head>
<body>
    <div class="card">
        <div class="icone"><?= htmlspecialchars($icone, ENT_QUOTES, 'UTF-8') ?></div>
        <h1><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h1>

        <div class="mensagem <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
        </div>

        <p class="submensagem">
            <?php if ($email !== ''): ?>
                Conta vinculada a <strong><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></strong>.
            <?php else: ?>
                Se necessario, solicite um novo fluxo de confirmacao entrando novamente na plataforma.
            <?php endif; ?>
        </p>

        <div class="botoes">
            <a href="login.php" class="botao">Ir para o login</a>
            <a href="index.php" class="botao secundario">Voltar ao inicio</a>
        </div>
    </div>
</body>
</html>
