<?php

require __DIR__ . '/../database.php';
require __DIR__ . '/2fa.php';

session_start();

if (empty($_SESSION['usuario']['id_usuario'])) {
    header('Location: ../../../public/pages/login.php');
    exit();
}

$idUsuario = (int) $_SESSION['usuario']['id_usuario'];
$stmt = $pdo->prepare('SELECT chave_2fa FROM usuario WHERE id_usuario = ? LIMIT 1');
$stmt->execute([$idUsuario]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);
$secret = (string) ($usuario['chave_2fa'] ?? '');

if ($secret === '') {
    header('Location: ./ativar_2fa.php');
    exit();
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = preg_replace('/\D/', '', $_POST['codigo'] ?? '');

    if (!preg_match('/^\d{6}$/', $codigo)) {
        $erro = 'Informe um código de 6 dígitos.';
    } elseif (!verificarTOTP($secret, $codigo)) {
        $erro = 'Código inválido. Tente novamente.';
    } else {
        $_SESSION['2fa_ok'] = true;
        $_SESSION['2fa_pendente'] = false;
        $_SESSION['usuario']['chave_2fa'] = $secret;

        $destino = (stripos((string) ($_SESSION['usuario']['tipo'] ?? ''), 'admin') !== false)
            ? '../../admin/pages/dashboard.php'
            : '../../../public/pages/home_usuario.php';

        header('Location: ' . $destino);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar 2FA</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f7fb; color: #1f2937; margin: 0; }
        .container { max-width: 420px; margin: 48px auto; background: #fff; padding: 32px; border-radius: 16px; box-shadow: 0 20px 50px rgba(15, 23, 42, 0.12); }
        h1 { margin-top: 0; }
        input { width: 100%; box-sizing: border-box; padding: 12px; border: 1px solid #cbd5e1; border-radius: 10px; margin: 16px 0; }
        button { width: 100%; padding: 12px; border: 0; border-radius: 10px; background: #0f766e; color: #fff; font-weight: 700; cursor: pointer; }
        .erro { color: #b91c1c; margin-bottom: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Confirmar autenticação em duas etapas</h1>
        <p>Abra seu aplicativo autenticador e informe o código gerado para concluir o login.</p>
        <?php if ($erro !== ''): ?>
            <div class="erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="text" name="codigo" inputmode="numeric" maxlength="6" placeholder="Código de 6 dígitos" required>
            <button type="submit">Validar código</button>
        </form>
    </div>
</body>
</html>