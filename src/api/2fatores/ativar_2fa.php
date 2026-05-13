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

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$usuarioSessao = $_SESSION['usuario_temp'] ?? $_SESSION['usuario'] ?? null;

if (empty($usuarioSessao['id_usuario'])) {
    header('Location: ../../../public/pages/login.php');
    exit();
}

$idUsuario = (int) $usuarioSessao['id_usuario'];
$email = (string) ($usuarioSessao['email'] ?? '');
$erro = '';

if (empty($_SESSION['2fa_secret_temp']) || !preg_match('/^[A-Z2-7]+$/', (string) $_SESSION['2fa_secret_temp'])) {
    $_SESSION['2fa_secret_temp'] = gerarSecret();
}

$secret = strtoupper((string) $_SESSION['2fa_secret_temp']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = preg_replace('/\D/', '', $_POST['codigo'] ?? '');

    if (!preg_match('/^\d{6}$/', $codigo)) {
        $erro = 'Informe um codigo de 6 digitos.';
    } elseif (!verificarTOTP($secret, $codigo)) {
        $erro = 'Codigo invalido. Tente novamente.';
    } else {
        $stmt = $pdo->prepare('UPDATE usuario SET chave_2fa = ? WHERE id_usuario = ?');
        $stmt->execute([$secret, $idUsuario]);

        session_regenerate_id(true);
        $_SESSION['usuario'] = $usuarioSessao;
        $_SESSION['usuario']['chave_2fa'] = $secret;
        $_SESSION['2fa_ok'] = true;
        $_SESSION['2fa_pendente'] = false;
        unset($_SESSION['2fa_secret_temp']);
        unset($_SESSION['usuario_temp']);

        $destino = (stripos((string) ($_SESSION['usuario']['tipo'] ?? ''), 'admin') !== false)
            ? '../../admin/pages/dashboard.php'
            : '../../../public/pages/home_usuario.php';

        header('Location: ' . $destino);
        exit();
    }
}

$qrUrl = './gerarqr.php?secret=' . urlencode($secret) . '&email=' . urlencode($email) . '&v=' . urlencode(substr(hash('sha256', $secret . '|' . $email), 0, 12));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ativar 2FA</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #eef4ff 0%, #f8fbff 100%); color: #1f2937; margin: 0; padding: 24px; }
        .container { max-width: 920px; margin: 24px auto; background: #fff; border-radius: 24px; box-shadow: 0 24px 60px rgba(15, 23, 42, 0.12); overflow: hidden; display: grid; grid-template-columns: 1fr 1fr; }
        .panel { padding: 36px; }
        .panel--info { background: linear-gradient(160deg, #0f172a 0%, #12335f 100%); color: #f8fafc; }
        .eyebrow { display: inline-block; padding: 8px 12px; border-radius: 999px; background: rgba(255,255,255,0.1); font-size: 12px; text-transform: uppercase; letter-spacing: .08em; }
        h1 { margin: 18px 0 14px; font-size: 32px; line-height: 1.1; }
        p { line-height: 1.6; margin: 0 0 14px; }
        .panel--info p { color: #d8e3f1; }
        .account { margin-top: 18px; padding: 16px 18px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12); border-radius: 16px; }
        .qr-card { display: flex; flex-direction: column; justify-content: center; }
        .qr { display: flex; align-items: center; justify-content: center; margin: 20px 0 24px; padding: 22px; border: 1px solid #dbe4f0; border-radius: 22px; background: #f8fbff; min-height: 304px; }
        .qr img { width: 240px; height: 240px; max-width: 100%; display: block; border-radius: 16px; background: #fff; }
        .hint { color: #475569; margin-bottom: 18px; }
        input { width: 100%; padding: 14px 16px; border: 1px solid #cbd5e1; border-radius: 12px; margin: 16px 0; font-size: 16px; }
        button { width: 100%; padding: 14px; border: 0; border-radius: 12px; background: #0f766e; color: #fff; font-weight: 700; cursor: pointer; font-size: 16px; }
        .erro { color: #b91c1c; margin-bottom: 12px; }
        @media (max-width: 820px) {
            .container { grid-template-columns: 1fr; }
            .panel { padding: 28px; }
            body { padding: 14px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <section class="panel panel--info">
            <span class="eyebrow">Seguranca</span>
            <h1>Ative a autenticacao em duas etapas</h1>
            <p>Escaneie o QR Code com Google Authenticator, Microsoft Authenticator ou outro app compativel.</p>
            <p>Depois, informe abaixo o codigo de 6 digitos gerado no aplicativo para concluir a ativacao.</p>
            <?php if ($email !== ''): ?>
                <div class="account">
                    <strong>Conta protegida</strong>
                    <p><?= htmlspecialchars($email) ?></p>
                </div>
            <?php endif; ?>
        </section>

        <section class="panel qr-card">
            <div class="qr">
                <img src="<?= htmlspecialchars($qrUrl, ENT_QUOTES, 'UTF-8') ?>" alt="QR Code para ativar o 2FA">
            </div>
            <p class="hint">Se o codigo mudar no aplicativo antes do envio, aguarde o proximo e use o novo valor.</p>
            <?php if ($erro !== ''): ?>
                <div class="erro"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>
            <form method="post" autocomplete="off">
                <input type="text" name="codigo" inputmode="numeric" maxlength="6" placeholder="Codigo de 6 digitos" required>
                <button type="submit">Confirmar ativacao</button>
            </form>
        </section>
    </div>
</body>
</html>
