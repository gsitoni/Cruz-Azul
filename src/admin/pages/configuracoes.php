<?php
require __DIR__ . '/auth.php';

// ==========================
// LOGOUT
// ==========================
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../index.php");
    exit();
}

// ==========================
// CONFIGURAÇÕES (simuladas)
// ==========================
$config = [
    'nome_sistema' => 'Cruz Azul',
    'email_admin' => 'admin@cruzazul.com',
    'email_noreply' => 'noreply@cruzazul.com',
    'tentativas_login' => 5,
    'timeout_sessao' => 3600,
    'notificacoes_email' => true,
    'autenticacao_2fa' => true,
];

// ==========================
// SALVAR CONFIGURAÇÕES
// ==========================
$msg = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $erro = 'CSRF token inválido';
    } else {
        // Simular salvamento
        $config['nome_sistema'] = filter_input(INPUT_POST, 'nome_sistema', FILTER_SANITIZE_STRING) ?: $config['nome_sistema'];
        $config['email_admin'] = filter_input(INPUT_POST, 'email_admin', FILTER_SANITIZE_EMAIL) ?: $config['email_admin'];
        $config['email_noreply'] = filter_input(INPUT_POST, 'email_noreply', FILTER_SANITIZE_EMAIL) ?: $config['email_noreply'];
        $config['tentativas_login'] = filter_input(INPUT_POST, 'tentativas_login', FILTER_SANITIZE_NUMBER_INT) ?: $config['tentativas_login'];
        $config['timeout_sessao'] = filter_input(INPUT_POST, 'timeout_sessao', FILTER_SANITIZE_NUMBER_INT) ?: $config['timeout_sessao'];
        $config['notificacoes_email'] = isset($_POST['notificacoes_email']);
        $config['autenticacao_2fa'] = isset($_POST['autenticacao_2fa']);
        
        $msg = 'Configurações salvas com sucesso!';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Configurações - Cruz Azul</title>
<link rel="stylesheet" href="../assets/css/configuracoes.css?v=20260423a">
</head>

<body>

<header class="topbar">
    <div>
        <h1>Cruz Azul Admin</h1>
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
    ✓ <?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>

<?php if($erro): ?>
<div class="alert alert-error">
    ✗ <?= htmlspecialchars($erro) ?>
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
            <label for="tentativas_login">Tentativas de Login Máximas:</label>
            <input type="number" id="tentativas_login" name="tentativas_login" 
                   value="<?= htmlspecialchars($config['tentativas_login']) ?>" min="1" max="10" required>
        </div>

        <div class="form-group">
            <label for="timeout_sessao">Timeout da Sessão (segundos):</label>
            <input type="number" id="timeout_sessao" name="timeout_sessao" 
                   value="<?= htmlspecialchars($config['timeout_sessao']) ?>" min="300" max="86400" required>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="notificacoes_email" 
                       <?= $config['notificacoes_email'] ? 'checked' : '' ?>> 
                Habilitar notificações por email
            </label>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="autenticacao_2fa" 
                       <?= $config['autenticacao_2fa'] ? 'checked' : '' ?>> 
                Exigir autenticação 2FA para admins
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
        <p><strong>Timeout de sessao:</strong> <?= (int) $config['timeout_sessao'] ?> segundos</p>
    </div>
</section>

</main>

<footer>
<p>© 2026 Cruz Azul</p>
</footer>

</body>
</html>
