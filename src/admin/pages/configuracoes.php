<?php
session_start();

// ==========================
// PROTEÇÃO DE ACESSO
// ==========================
if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../../../public/pages/login.php");
    exit();
}
// ==========================
// LOGOUT
// ==========================
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../../../public/pages/login.php");
    exit();
}
// ==========================
// CSRF
// ==========================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ==========================
// DADOS DE CONFIGURAÇÃO
// ==========================
// TODO: Implementar queries no banco de dados
// $config = obter_configuracoes_banco();

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
// AÇÕES
// ==========================
$msg = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF inválido");
    }

    $acao = $_POST['acao'] ?? "";

    switch ($acao) {
        case 'atualizar_config':
            // TODO: Validar e atualizar configurações no banco de dados
            $msg = "Configurações atualizadas com sucesso!";
            break;

        case 'resetar_cache':
            // TODO: Implementar limpeza de cache
            $msg = "Cache resetado com sucesso!";
            break;

        case 'backup_database':
            // TODO: Implementar backup do banco de dados
            $msg = "Backup iniciado!";
            break;

        default:
            $erro = "Ação desconhecida";
    }
}

$msg = $_SESSION['msg'] ?? $msg;
$erro = $_SESSION['erro'] ?? $erro;
unset($_SESSION['msg'], $_SESSION['erro']);

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Configurações - Cruz Azul</title>
<link rel="stylesheet" href="../assets/css/configuracoes.css">
</head>

<body>

<header class="header">
    <h1>Cruz Azul ✙</h1>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="ongs.php">ONGs</a>
        <a href="logs.php">Logs</a>
        <a href="usuarios.php">Usuários</a>
        <a href="configuracoes.php">Configurações</a>
        <a href="?logout=true">Sair</a>
    </nav>
</header>

<main class="container">

<h2>Configurações do Sistema</h2>

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

<!-- SEÇÃO: INFORMAÇÕES GERAIS -->
<section class="config-section">
    <h3>📋 Informações Gerais</h3>

    <form method="POST" class="form-config">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="acao" value="atualizar_config">

        <div class="form-group">
            <label for="nome_sistema">Nome do Sistema:</label>
            <input type="text" id="nome_sistema" name="nome_sistema" 
                   value="<?= htmlspecialchars($config['nome_sistema']) ?>" required>
        </div>

        <div class="form-group">
            <label for="email_admin">Email Administrativo:</label>
            <input type="email" id="email_admin" name="email_admin" 
                   value="<?= htmlspecialchars($config['email_admin']) ?>" required>
        </div>

        <div class="form-group">
            <label for="email_noreply">Email No-Reply:</label>
            <input type="email" id="email_noreply" name="email_noreply" 
                   value="<?= htmlspecialchars($config['email_noreply']) ?>" required>
        </div>

        <button type="submit" class="btn-primary">Salvar Alterações</button>
    </form>
</section>

<!-- SEÇÃO: SEGURANÇA -->
<section class="config-section">
    <h3>🔒 Segurança</h3>

    <form method="POST" class="form-config">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="acao" value="atualizar_config">

        <div class="form-group">
            <label for="tentativas_login">Máximo de Tentativas de Login:</label>
            <input type="number" id="tentativas_login" name="tentativas_login" 
                   value="<?= htmlspecialchars($config['tentativas_login']) ?>" min="1" required>
        </div>

        <div class="form-group">
            <label for="timeout_sessao">Timeout de Sessão (segundos):</label>
            <input type="number" id="timeout_sessao" name="timeout_sessao" 
                   value="<?= htmlspecialchars($config['timeout_sessao']) ?>" min="60" required>
        </div>

        <div class="form-group form-checkbox">
            <label>
                <input type="checkbox" name="autenticacao_2fa" 
                       <?= $config['autenticacao_2fa'] ? 'checked' : '' ?>>
                Ativar Autenticação em Dois Fatores
            </label>
        </div>

        <button type="submit" class="btn-primary">Salvar Alterações</button>
    </form>
</section>

<!-- SEÇÃO: NOTIFICAÇÕES -->
<section class="config-section">
    <h3>📧 Notificações</h3>

    <form method="POST" class="form-config">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="acao" value="atualizar_config">

        <div class="form-group form-checkbox">
            <label>
                <input type="checkbox" name="notificacoes_email" 
                       <?= $config['notificacoes_email'] ? 'checked' : '' ?>>
                Ativar Notificações por Email
            </label>
        </div>

        <button type="submit" class="btn-primary">Salvar Alterações</button>
    </form>
</section>

<!-- SEÇÃO: MANUTENÇÃO -->
<section class="config-section">
    <h3>🔧 Manutenção</h3>

    <div class="maintenance-actions">
        <form method="POST" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="acao" value="resetar_cache">
            <button type="submit" class="btn-warning" onclick="return confirm('Deseja resetar o cache do sistema?')">
                🗑️ Limpar Cache
            </button>
        </form>

        <form method="POST" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="acao" value="backup_database">
            <button type="submit" class="btn-info" onclick="return confirm('Iniciar backup do banco de dados?')">
                💾 Fazer Backup
            </button>
        </form>
    </div>
</section>

<!-- SEÇÃO: INFORMAÇÕES DO SISTEMA -->
<section class="config-section">
    <h3>ℹ️ Informações do Sistema</h3>

    <div class="system-info">
        <p><strong>Versão PHP:</strong> <?= phpversion() ?></p>
        <p><strong>Versão MySQL:</strong> TODO: Implementar query</p>
        <p><strong>Memória Disponível:</strong> TODO: Implementar função</p>
        <p><strong>Espaço em Disco:</strong> TODO: Implementar função</p>
        <p><strong>Taxa de Uptime:</strong> TODO: Implementar cálculo</p>
    </div>
</section>

</main>

<footer>
<p>© 2026 Cruz Azul</p>
</footer>

</body>
</html>
