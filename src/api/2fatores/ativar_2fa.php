<?php

require __DIR__ . '/../database.php';
require __DIR__ . '/2fa.php';

session_start();

if (empty($_SESSION['usuario']['id_usuario'])) {
    header('Location: ../../../public/pages/login.php');
    exit();
}

$idUsuario = (int) $_SESSION['usuario']['id_usuario'];
$email = (string) ($_SESSION['usuario']['email'] ?? '');
$erro = '';

if (empty($_SESSION['2fa_secret_temp'])) {
    $_SESSION['2fa_secret_temp'] = gerarSecret();
}

$secret = $_SESSION['2fa_secret_temp'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = preg_replace('/\D/', '', $_POST['codigo'] ?? '');

    if (!preg_match('/^\d{6}$/', $codigo)) {
        $erro = 'Informe um código de 6 dígitos.';
    } elseif (!verificarTOTP($secret, $codigo)) {
        $erro = 'Código inválido. Tente novamente.';
    } else {
        $stmt = $pdo->prepare('UPDATE usuario SET chave_2fa = ? WHERE id_usuario = ?');
        $stmt->execute([$secret, $idUsuario]);

        $_SESSION['usuario']['chave_2fa'] = $secret;
        $_SESSION['2fa_ok'] = true;
        $_SESSION['2fa_pendente'] = false;
        unset($_SESSION['2fa_secret_temp']);

        $destino = (stripos((string) ($_SESSION['usuario']['tipo'] ?? ''), 'admin') !== false)
            ? '../../admin/pages/dashboard.php'
            : '../../../public/pages/home_usuario.php';

        header('Location: ' . $destino);
        exit();
    }
}

$qrUrl = './gerarqr.php?secret=' . urlencode($secret);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ativar 2FA</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f7fb; color: #1f2937; margin: 0; }
        .container { max-width: 480px; margin: 48px auto; background: #fff; padding: 32px; border-radius: 16px; box-shadow: 0 20px 50px rgba(15, 23, 42, 0.12); }
        h1 { margin-top: 0; }
        p { line-height: 1.5; }
        .qr { text-align: center; margin: 24px 0; }
        .qr img { width: 200px; height: 200px; border: 1px solid #d1d5db; border-radius: 12px; }
        .secret { font-family: Consolas, monospace; background: #f3f4f6; padding: 12px; border-radius: 8px; word-break: break-all; }
        input { width: 100%; box-sizing: border-box; padding: 12px; border: 1px solid #cbd5e1; border-radius: 10px; margin: 16px 0; }
        button { width: 100%; padding: 12px; border: 0; border-radius: 10px; background: #0f766e; color: #fff; font-weight: 700; cursor: pointer; }
        .erro { color: #b91c1c; margin-bottom: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Ativar autenticação em duas etapas</h1>
        <p>Escaneie o QR Code no Google Authenticator, Microsoft Authenticator ou app compatível e informe o código gerado.</p>
        <?php if ($email !== ''): ?>
            <p><strong>Conta:</strong> <?= htmlspecialchars($email) ?></p>
        <?php endif; ?>
        <div class="qr">
            <img src="<?= htmlspecialchars($qrUrl) ?>" alt="QR Code para ativar 2FA">
        </div>
        <p>Se preferir, use esta chave manualmente:</p>
        <div class="secret"><?= htmlspecialchars($secret) ?></div>
        <?php if ($erro !== ''): ?>
            <div class="erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="text" name="codigo" inputmode="numeric" maxlength="6" placeholder="Código de 6 dígitos" required>
            <button type="submit">Confirmar ativação</button>
        </form>
    </div>
</body>
</html>