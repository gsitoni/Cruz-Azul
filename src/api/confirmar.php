<?php
// confirmar.php - Valida o token e ativa a conta
require 'database.php';

$token = trim($_GET['token'] ?? '');
$mensagem = '';
$tipo     = 'erro';

if (empty($token)) {
    $mensagem = 'Token inválido ou ausente.';
} else {
    // Busca usuário pelo token
    $stmt = $pdo->prepare("
        SELECT id, confirmado FROM usuarios WHERE token_confirmacao = ?
    ");
    $stmt->execute([$token]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        $mensagem = 'Link de confirmação inválido ou expirado.';
    } elseif ($usuario['confirmado']) {
        $mensagem = 'Sua conta já foi confirmada anteriormente. Faça login.';
        $tipo     = 'info';
    } else {
        // Ativa a conta e apaga o token
        $upd = $pdo->prepare("
            UPDATE usuarios SET confirmado = 1, token_confirmacao = NULL WHERE id = ?
        ");
        $upd->execute([$usuario['id']]);
        $mensagem = 'E-mail confirmado com sucesso! Sua conta está ativa.';
        $tipo     = 'sucesso';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Confirmação de Cadastro</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 420px; margin: 80px auto; padding: 0 16px; text-align: center; }
        .sucesso { color: #1e7e34; background: #eafaf1; padding: 18px; border-radius: 6px; }
        .erro    { color: #c0392b; background: #fdecea; padding: 18px; border-radius: 6px; }
        .info    { color: #0c5460; background: #d1ecf1; padding: 18px; border-radius: 6px; }
        a { display: inline-block; margin-top: 16px; color: #4CAF50; }
    </style>
</head>
<body>
    <h2>Confirmação de E-mail</h2>
    <div class="<?= $tipo ?>">
        <?= htmlspecialchars($mensagem) ?>
    </div>
    <a href="cadastro.php">← Voltar ao cadastro</a>
</body>
</html>