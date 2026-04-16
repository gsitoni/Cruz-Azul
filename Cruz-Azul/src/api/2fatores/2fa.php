<?php
session_start();
require_once __DIR__ . '/../database.php';

function gerarSecret($tamanho = 16) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = '';

    for ($i = 0; $i < $tamanho; $i++) {
        $secret .= $chars[random_int(0, strlen($chars) - 1)];
    }

    return $secret;
}

function b32Decode($b32) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $b32 = strtoupper($b32);
    $binary = '';

    foreach (str_split($b32) as $char) {
        $pos = strpos($alphabet, $char);
        if ($pos !== false) {
            $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
    }

    $result = '';
    foreach (str_split($binary, 8) as $byte) {
        if (strlen($byte) === 8) {
            $result .= chr(bindec($byte));
        }
    }

    return $result;
}

function verificarTOTP($secret, $codigoUsuario, $tolerancia = 1) {
    $timeSlice = floor(time() / 30);

    for ($i = -$tolerancia; $i <= $tolerancia; $i++) {
        $key = b32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlice + $i);
        $hash = hash_hmac('sha1', $time, $key, true);
        $offset = ord(substr($hash, -1)) & 0x0F;

        $truncatedHash =
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF);

        $code = str_pad((string) ($truncatedHash % 1000000), 6, '0', STR_PAD_LEFT);

        if (hash_equals($code, $codigoUsuario)) {
            return true;
        }
    }

    return false;
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pending2fa = $_SESSION['pending_2fa'] ?? null;
$usuarioLogado = $_SESSION['usuario'] ?? null;

if (!$pending2fa && !$usuarioLogado) {
    header('Location: ../../public/pages/login.php');
    exit;
}

$modo = $pending2fa ? 'login' : 'configuracao';
$idUsuario = $pending2fa
    ? (int) ($pending2fa['id_usuario'] ?? 0)
    : (int) ($usuarioLogado['id_usuario'] ?? 0);

if ($idUsuario <= 0) {
    $_SESSION = [];
    session_destroy();
    header('Location: ../../public/pages/login.php');
    exit;
}

$stmtUsuario = $pdo->prepare('SELECT id_usuario, email, permissao, chave_2fa FROM usuario WHERE id_usuario = ? LIMIT 1');
$stmtUsuario->execute([$idUsuario]);
$usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

if (!$usuario || empty($usuario['email'])) {
    http_response_code(404);
    exit('Usuario nao encontrado.');
}

$secret = (string) ($usuario['chave_2fa'] ?? '');

if ($modo === 'configuracao' && $secret === '') {
    $secret = gerarSecret();
    $stmtAtualiza = $pdo->prepare('UPDATE usuario SET chave_2fa = ? WHERE id_usuario = ?');
    $stmtAtualiza->execute([$secret, $idUsuario]);
}

if ($modo === 'login' && $secret === '') {
    unset($_SESSION['pending_2fa']);
    $_SESSION['usuario'] = [
        'id_usuario' => $usuario['id_usuario'],
        'email' => $usuario['email'],
        'permissao' => $usuario['permissao'] ?? '',
    ];
    header('Location: ../../public/pages/home_usuario.php');
    exit;
}

$mensagem = '';
$tipoMensagem = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $mensagem = 'Sessao invalida. Recarregue a pagina e tente novamente.';
        $tipoMensagem = 'erro';
    } elseif ($modo === 'configuracao' && ($_POST['acao'] ?? '') === 'desativar') {
        $stmtDesativa = $pdo->prepare('UPDATE usuario SET chave_2fa = NULL WHERE id_usuario = ?');
        $stmtDesativa->execute([$idUsuario]);
        unset($_SESSION['2fa_ok']);
        header('Location: ../../public/pages/perfil.php?status=2fa_desativado');
        exit;
    } elseif ($modo === 'configuracao' && ($_POST['acao'] ?? '') === 'regenerar') {
        $secret = gerarSecret();
        $stmtAtualiza = $pdo->prepare('UPDATE usuario SET chave_2fa = ? WHERE id_usuario = ?');
        $stmtAtualiza->execute([$secret, $idUsuario]);
        $mensagem = 'Nova chave 2FA gerada. Escaneie o novo QR Code e confirme para concluir a troca.';
        $tipoMensagem = 'info';
    } else {
        $codigo = preg_replace('/\D+/', '', (string) ($_POST['codigo'] ?? ''));

        if (strlen($codigo) !== 6) {
            $mensagem = 'Informe um codigo de 6 digitos.';
            $tipoMensagem = 'erro';
        } elseif (verificarTOTP($secret, $codigo)) {
            $_SESSION['2fa_ok'] = true;

            if ($modo === 'login') {
                unset($_SESSION['pending_2fa']);
                $_SESSION['usuario'] = [
                    'id_usuario' => $usuario['id_usuario'],
                    'email' => $usuario['email'],
                    'permissao' => $usuario['permissao'] ?? '',
                ];

                $redirect = !empty($pending2fa['redirect'])
                    ? $pending2fa['redirect']
                    : '../../public/pages/home_usuario.php';

                header('Location: ' . $redirect);
                exit;
            }

            header('Location: ../../public/pages/perfil.php?status=2fa_ativado');
            exit;
        } else {
            $mensagem = 'Codigo invalido. Tente novamente.';
            $tipoMensagem = 'erro';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autenticacao em Dois Fatores</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(180deg, #eef7fb 0%, #ffffff 100%);
            color: #153243;
        }
        .container {
            max-width: 520px;
            margin: 40px auto;
            background: #fff;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 18px 48px rgba(21, 50, 67, 0.12);
        }
        h1 {
            margin-top: 0;
            font-size: 28px;
        }
        .mensagem {
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 16px;
        }
        .mensagem.info { background: #eef6ff; color: #0e4f85; }
        .mensagem.sucesso { background: #e8f7ee; color: #1d6b3d; }
        .mensagem.erro { background: #fdecec; color: #a12828; }
        .qr-box {
            text-align: center;
            margin: 24px 0;
            padding: 20px;
            border: 1px solid #d9e6ee;
            border-radius: 12px;
            background: #f9fcfe;
        }
        .chave {
            display: inline-block;
            padding: 10px 14px;
            border-radius: 10px;
            background: #153243;
            color: #fff;
            letter-spacing: 1px;
            margin-top: 10px;
        }
        form {
            display: grid;
            gap: 12px;
            margin-top: 20px;
        }
        input {
            padding: 12px;
            border: 1px solid #b8cad5;
            border-radius: 10px;
            font-size: 16px;
        }
        button, .link-btn {
            padding: 12px 16px;
            border: 0;
            border-radius: 10px;
            background: #0d6e8a;
            color: #fff;
            font-size: 16px;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
        }
        .acoes {
            margin-top: 16px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .link-btn.secundario {
            background: #dce8ef;
            color: #153243;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo $modo === 'login' ? 'Confirmar login com 2FA' : 'Ativar autenticacao em dois fatores'; ?></h1>

        <?php if ($mensagem !== ''): ?>
            <div class="mensagem <?php echo e($tipoMensagem); ?>"><?php echo e($mensagem); ?></div>
        <?php endif; ?>

        <?php if ($modo === 'configuracao'): ?>
            <p>Escaneie o QR Code no Google Authenticator ou em outro aplicativo compatível com TOTP.</p>
            <div class="qr-box">
                <img src="./gerarqr.php?secret=<?php echo urlencode($secret); ?>" alt="QR Code para autenticacao em dois fatores">
                <div class="chave"><?php echo e($secret); ?></div>
            </div>
        <?php else: ?>
            <p>Digite o codigo de 6 digitos gerado no seu aplicativo autenticador para concluir o login.</p>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
            <input type="text" name="codigo" placeholder="Codigo de 6 digitos" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required>
            <button type="submit"><?php echo $modo === 'login' ? 'Confirmar login' : 'Confirmar ativacao'; ?></button>
        </form>

        <?php if ($modo === 'configuracao' && $secret !== ''): ?>
            <div class="acoes">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="acao" value="regenerar">
                    <button type="submit">Gerar nova chave</button>
                </form>
                <form method="POST" onsubmit="return confirm('Desativar o 2FA desta conta?');">
                    <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="acao" value="desativar">
                    <button type="submit" class="link-btn secundario">Desativar 2FA</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="acoes">
            <?php if ($modo === 'configuracao'): ?>
                <a class="link-btn secundario" href="../../public/pages/perfil.php">Voltar ao perfil</a>
            <?php else: ?>
                <a class="link-btn secundario" href="../../public/pages/login.php">Voltar ao login</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
