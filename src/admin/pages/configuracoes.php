<?php
require __DIR__ . '/auth.php';
require_once __DIR__ . '/admin_config.php';

if (isset($_GET['logout'])) {
    destruirSessao();
    header("Location: ../index.php");
    exit();
}

$config = adminConfigCarregar();
$msg = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $erro = 'CSRF token invalido';
    } else {
        $novaConfig = [
            'nome_sistema' => $_POST['nome_sistema'] ?? $config['nome_sistema'],
            'email_admin' => $_POST['email_admin'] ?? $config['email_admin'],
            'email_noreply' => $_POST['email_noreply'] ?? $config['email_noreply'],
            'tentativas_login' => $_POST['tentativas_login'] ?? $config['tentativas_login'],
            'timeout_sessao' => $_POST['timeout_sessao'] ?? $config['timeout_sessao'],
            'notificacoes_email' => isset($_POST['notificacoes_email']),
            'autenticacao_2fa' => isset($_POST['autenticacao_2fa']),
        ];

        if (adminConfigSalvar($novaConfig)) {
            $config = adminConfigCarregar();
            $msg = 'Configuracoes salvas com sucesso!';
        } else {
            $erro = 'Nao foi possivel salvar as configuracoes.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Configuracoes - Cruz Azul</title>
<link rel="stylesheet" href="../assets/css/configuracoes.css?v=20260423a">
</head>

<body>

<header class="topbar">
    <div>
        <h1><?= htmlspecialchars($config['nome_sistema']) ?> Admin</h1>
        <p>Parametros de seguranca e operacao</p>
    </div>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="ongs.php">ONGs</a>
        <a href="logs.php">Logs</a>
        <a href="usuarios.php">Usuarios</a>
        <a class="active" href="configuracoes.php">Configuracoes</a>
        <a href="?logout=true">Sair</a>
    </nav>
</header>

<main class="container">
<section class="page-header">
    <h2>Configuracoes do sistema</h2>
    <p>Gerencie os parametros principais da plataforma em um painel unificado.</p>
</section>

<?php if($msg): ?>
<div class="alert alert-success">
    <?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>

<?php if($erro): ?>
<div class="alert alert-error">
    <?= htmlspecialchars($erro) ?>
</div>
<?php endif; ?>

<section class="config-section">
    <h3>Informacoes gerais</h3>

    <form method="POST" class="form-config">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="form-group">
            <label for="nome_sistema">Nome do Sistema:</label>
            <input type="text" id="nome_sistema" name="nome_sistema"
                   value="<?= htmlspecialchars($config['nome_sistema']) ?>" required>
        </div>

        <div class="form-group">
            <label for="email_admin">Email do Administrador:</label>
            <input type="email" id="email_admin" name="email_admin"
                   value="<?= htmlspecialchars($config['email_admin']) ?>" required>
        </div>

        <div class="form-group">
            <label for="email_noreply">Email No-Reply:</label>
            <input type="email" id="email_noreply" name="email_noreply"
                   value="<?= htmlspecialchars($config['email_noreply']) ?>" required>
        </div>

        <div class="form-group">
            <label for="tentativas_login">Tentativas de Login Maximas:</label>
            <input type="number" id="tentativas_login" name="tentativas_login"
                   value="<?= htmlspecialchars((string) $config['tentativas_login']) ?>" min="1" max="10" required>
        </div>

        <div class="form-group">
            <label for="timeout_sessao">Timeout da Sessao (segundos):</label>
            <input type="number" id="timeout_sessao" name="timeout_sessao"
                   value="<?= htmlspecialchars((string) $config['timeout_sessao']) ?>" min="300" max="86400" required>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="notificacoes_email"
                       <?= $config['notificacoes_email'] ? 'checked' : '' ?>>
                Habilitar notificacoes por email
            </label>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="autenticacao_2fa"
                       <?= $config['autenticacao_2fa'] ? 'checked' : '' ?>>
                Exigir autenticacao 2FA para admins
            </label>
        </div>

        <button type="submit" class="btn-primary">Salvar configuracoes</button>
    </form>
</section>

<section class="config-section">
    <h3>Informacoes do ambiente</h3>
    <div class="system-info">
        <p><strong>Versao PHP:</strong> <?= htmlspecialchars(phpversion()) ?></p>
        <p><strong>2FA de administradores:</strong> <?= $config['autenticacao_2fa'] ? 'Ativado' : 'Desativado' ?></p>
        <p><strong>Notificacoes por email:</strong> <?= $config['notificacoes_email'] ? 'Ativadas' : 'Desativadas' ?></p>
        <p><strong>Tentativas de login:</strong> <?= (int) $config['tentativas_login'] ?></p>
        <p><strong>Timeout de sessao:</strong> <?= (int) $config['timeout_sessao'] ?> segundos</p>
    </div>
</section>

</main>

<footer>
<p>&copy; 2026 Cruz Azul</p>
</footer>

</body>
</html>
