<?php
session_start();

// Headers de segurança
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

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
// CONEXÃO BANCO
// ==========================
require '../../api/database.php';

// ==========================
// AÇÕES POST
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token inválido');
    }
    
    $acao = $_POST['acao'] ?? '';
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
    
    try {
        if ($acao === 'bloquear' && $id) {
            $stmt = $pdo->prepare("UPDATE usuario SET status_cadastro = 'bloqueado' WHERE id_usuario = ?");
            $stmt->execute([$id]);
            $_SESSION['msg'] = 'Usuário bloqueado com sucesso.';
        } elseif ($acao === 'desbloquear' && $id) {
            $stmt = $pdo->prepare("UPDATE usuario SET status_cadastro = 'confirmado' WHERE id_usuario = ?");
            $stmt->execute([$id]);
            $_SESSION['msg'] = 'Usuário desbloqueado com sucesso.';
        } elseif ($acao === 'excluir' && $id) {
            $stmt = $pdo->prepare("DELETE FROM usuario WHERE id_usuario = ?");
            $stmt->execute([$id]);
            $_SESSION['msg'] = 'Usuário excluído com sucesso.';
        } else {
            $_SESSION['msg'] = 'Ação inválida ou usuário não encontrado.';
        }
    } catch (PDOException $e) {
        $_SESSION['msg'] = 'Erro ao processar a solicitação.';
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// ==========================
// FILTROS
// ==========================
$busca = $_GET['busca'] ?? "";
$status = $_GET['status'] ?? "";

// ==========================
// QUERY USUÁRIOS
// ==========================
try {
    $sql = "SELECT id_usuario, email, status_cadastro, data_criacao FROM usuario WHERE 1=1";
    $params = [];
    
    if (!empty($busca)) {
        $sql .= " AND email LIKE ?";
        $params[] = "%$busca%";
    }
    
    if (!empty($status)) {
        $sql .= " AND status_cadastro = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY data_criacao DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $usuarios = [];
}

$msg = $_SESSION['msg'] ?? "";
unset($_SESSION['msg']);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Usuários - Cruz Azul</title>
<link rel="stylesheet" href="../assets/css/usuarios.css">
</head>

<body>

<header class="header">
    <h1>Cruz Azul ✙</h1>
    <nav>
        <a href="./dashboard.php">Dashboard</a>
        <a href="./ongs.php">ONGs</a>
        <a href="./logs.php">Logs</a>
        <a href="./usuarios.php">Usuários</a>
        <a href="./configuracoes.php">Configurações</a>
        <a href="./usuarios.php?logout=true">Sair</a>
    </nav>
</header>

<main class="container">

<h2>Controle de Usuários</h2>

<?php if($msg): ?>
<p style="color:green;"><?= htmlspecialchars($msg) ?></p>
<?php endif; ?>

<!-- FILTROS -->
<form method="GET" class="filters">
    <input type="text" name="busca" placeholder="Buscar por email..." value="<?= htmlspecialchars($busca) ?>">
    <select name="status">
        <option value="">Status</option>
        <option value="pendente">Pendente</option>
        <option value="confirmado">Confirmado</option>
        <option value="bloqueado">Bloqueado</option>
    </select>
    <button type="submit">Filtrar</button>
</form>

<!-- TABELA -->
<section class="table-box">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Email</th>
                <th>Status</th>
                <th>Data Criação</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($usuarios)): ?>
                <tr><td colspan="5">Nenhum usuário encontrado</td></tr>
            <?php endif; ?>
            <?php foreach($usuarios as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['id_usuario']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td>
                        <span class="badge <?= $u['status_cadastro'] === 'confirmado' ? 'ativo' : 'bloqueado' ?>">
                            <?= strtoupper($u['status_cadastro']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($u['data_criacao']) ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="id" value="<?= $u['id_usuario'] ?>">
                            <?php if($u['status_cadastro'] === 'confirmado'): ?>
                                <button class="btn-bloquear" name="acao" value="bloquear">Bloquear</button>
                            <?php elseif($u['status_cadastro'] === 'bloqueado'): ?>
                                <button class="btn-ativar" name="acao" value="desbloquear">Desbloquear</button>
                            <?php endif; ?>
                            <button class="btn-excluir" name="acao" value="excluir" onclick="return confirm('Tem certeza?')">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

</main>

<footer>
<p>© 2026 Cruz Azul</p>
</footer>

</body>
</html>