<?php
session_start();

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'none'");
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

require_once __DIR__ . '/../../src/api/database.php';

if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['id_usuario'])) {
    header('Location: login.php');
    exit();
}

$idUsuario = (int) $_SESSION['usuario']['id_usuario'];

try {
    $stmtUser = $pdo->prepare(
        'SELECT nome, email, chave_2fa FROM usuario WHERE id_usuario = ? LIMIT 1'
    );
    $stmtUser->execute([$idUsuario]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit();
    }

    $stmtDoador = $pdo->prepare(
        'SELECT nome FROM doador WHERE email = ? LIMIT 1'
    );
    $stmtDoador->execute([$user['email']]);
    $perfil = $stmtDoador->fetch(PDO::FETCH_ASSOC);
    $tipo = 'doador';

    if (!$perfil) {
        $stmtBeneficiario = $pdo->prepare(
            'SELECT nome_receptor, localizacao FROM beneficiario WHERE email = ? LIMIT 1'
        );
        $stmtBeneficiario->execute([$user['email']]);
        $perfil = $stmtBeneficiario->fetch(PDO::FETCH_ASSOC);
        $tipo = 'beneficiario';
    }

    if (!$perfil) {
        $perfil = [
            'nome' => $user['nome'] ?: $user['email'],
        ];
        $tipo = 'doador';
    }
} catch (Throwable $e) {
    error_log('perfil.php: ' . $e->getMessage());
    http_response_code(500);
    exit('Erro ao carregar perfil.');
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$status2fa = !empty($user['chave_2fa']);
$mensagem = '';

if (isset($_GET['status']) && $_GET['status'] === 'atualizado') {
    $mensagem = 'Dados atualizados com sucesso.';
} elseif (isset($_GET['status']) && $_GET['status'] === '2fa_ativado') {
    $mensagem = 'Autenticacao em dois fatores ativada com sucesso.';
} elseif (isset($_GET['status']) && $_GET['status'] === '2fa_desativado') {
    $mensagem = 'Autenticacao em dois fatores desativada com sucesso.';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Cruz Azul</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            color: #18313f;
        }
        .perfil-container {
            max-width: 760px;
            margin: 40px auto;
            background: #fff;
            border-radius: 18px;
            padding: 28px;
            box-shadow: 0 14px 40px rgba(24, 49, 63, 0.12);
        }
        .alerta-ok {
            background: #e8f7ee;
            color: #1f6b3a;
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 18px;
        }
        .campo-valor {
            margin-bottom: 14px;
            font-size: 16px;
        }
        .campo-label {
            font-weight: 700;
            display: inline-block;
            min-width: 120px;
        }
        .acoes {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 28px;
        }
        .btn {
            display: inline-block;
            padding: 11px 18px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
        }
        .btn-primario {
            background: #0d6e8a;
            color: #fff;
        }
        .btn-secundario {
            background: #dce8ef;
            color: #18313f;
        }
        .painel-2fa {
            margin-top: 26px;
            padding: 18px;
            border: 1px solid #d7e2ea;
            border-radius: 14px;
            background: #f9fcfe;
        }
        .status-pill {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
            margin-left: 8px;
        }
        .status-on {
            background: #def4e5;
            color: #1f6b3a;
        }
        .status-off {
            background: #fdecec;
            color: #a12828;
        }
    </style>
</head>
<body>
    <div class="perfil-container">
        <?php if ($mensagem !== ''): ?>
            <div class="alerta-ok" role="alert"><?php echo e($mensagem); ?></div>
        <?php endif; ?>

        <h1>Meu Perfil</h1>

        <div class="campo-valor">
            <span class="campo-label">E-mail:</span>
            <?php echo e($user['email']); ?>
        </div>

        <?php if ($tipo === 'doador'): ?>
            <div class="campo-valor">
                <span class="campo-label">Nome:</span>
                <?php echo e($perfil['nome'] ?? ''); ?>
            </div>
        <?php else: ?>
            <div class="campo-valor">
                <span class="campo-label">Instituicao:</span>
                <?php echo e($perfil['nome_receptor'] ?? ''); ?>
            </div>
            <div class="campo-valor">
                <span class="campo-label">Localizacao:</span>
                <?php echo e($perfil['localizacao'] ?? ''); ?>
            </div>
        <?php endif; ?>

        <div class="painel-2fa">
            <h2>
                Autenticacao em Dois Fatores
                <span class="status-pill <?php echo $status2fa ? 'status-on' : 'status-off'; ?>">
                    <?php echo $status2fa ? 'Ativado' : 'Nao ativado'; ?>
                </span>
            </h2>
            <p>
                <?php echo $status2fa
                    ? 'Seu login ja exige um codigo temporario do aplicativo autenticador.'
                    : 'Ative um segundo fator de autenticacao para proteger sua conta com um codigo TOTP.'; ?>
            </p>
            <a href="../../src/api/2fatores/2fa.php" class="btn btn-primario">
                <?php echo $status2fa ? 'Gerenciar 2FA' : 'Ativar 2FA'; ?>
            </a>
        </div>

        <div class="acoes">
            <a href="editar_perfil.php" class="btn btn-primario">Editar Informacoes</a>
            <a href="home_usuario.php" class="btn btn-secundario">Voltar</a>
            <a href="logout.php" class="btn btn-secundario">Sair</a>
        </div>
    </div>
</body>
</html>
