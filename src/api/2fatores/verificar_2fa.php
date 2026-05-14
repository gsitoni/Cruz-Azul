<?php

require __DIR__ . '/../database.php';
require __DIR__ . '/2fa.php';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

$usuarioSessao = $_SESSION['usuario_temp'] ?? $_SESSION['usuario'] ?? null;

if (empty($usuarioSessao['id_usuario'])) {
    header('Location: ../../../public/pages/login.php');
    exit();
}

$idUsuario = (int) $usuarioSessao['id_usuario'];
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
        $erro = 'Informe um codigo de 6 digitos.';
    } elseif (!verificarTOTP($secret, $codigo)) {
        $erro = 'Codigo invalido. Tente novamente.';
    } else {
        session_regenerate_id(true);
        $_SESSION['usuario'] = $usuarioSessao;
        $_SESSION['2fa_ok'] = true;
        $_SESSION['2fa_pendente'] = false;
        $_SESSION['usuario']['chave_2fa'] = $secret;
        unset($_SESSION['usuario_temp']);

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
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #eef4ff 0%, #f8fbff 100%); color: #1f2937; margin: 0; padding: 24px; }
        .container { max-width: 460px; margin: 32px auto; background: #fff; padding: 34px; border-radius: 22px; box-shadow: 0 20px 50px rgba(15, 23, 42, 0.12); }
        .eyebrow { display: inline-block; padding: 8px 12px; border-radius: 999px; background: #e6f0ff; color: #1d4ed8; font-size: 12px; text-transform: uppercase; letter-spacing: .08em; }
        h1 { margin: 18px 0 12px; }
        p { line-height: 1.6; color: #475569; }
        input { width: 100%; padding: 14px 16px; border: 1px solid #cbd5e1; border-radius: 12px; margin: 16px 0; font-size: 16px; }
        button { width: 100%; padding: 14px; border: 0; border-radius: 12px; background: #0f766e; color: #fff; font-weight: 700; cursor: pointer; font-size: 16px; }
        .erro { color: #b91c1c; margin-bottom: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <span class="eyebrow">2FA</span>
        <h1>Confirmar autenticacao em duas etapas</h1>
        <p>Abra seu aplicativo autenticador, aguarde o codigo atual de 6 digitos e use-o para concluir o login.</p>
        <?php if ($erro !== ''): ?>
            <div class="erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="text" name="codigo" inputmode="numeric" maxlength="6" placeholder="Codigo de 6 digitos" required>
            <button type="submit">Validar codigo</button>
        </form>
    </div>
</body>
</html>
