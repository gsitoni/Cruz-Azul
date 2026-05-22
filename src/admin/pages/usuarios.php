<?php
require __DIR__ . '/auth.php';

// ==========================
// LOGOUT
// ==========================
if (isset($_GET['logout'])) {
    destruirSessao();
    header("Location: ./index.php");
    exit();
}

// ==========================
// CONEXÃO BANCO
// ==========================
require __DIR__ . '/../../api/database.php';
/** @var PDO $pdo */
require_once __DIR__ . '/../../api/logs_sistema.php';

// ==========================
// AÇÕES POST
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token inválido');
    }
    
    $acao = $_POST['acao'] ?? '';
    $id = (int) filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
    $idAdminAtual = (int) ($_SESSION['usuario']['id_usuario'] ?? 0);
    
    try {
        if ($id && $id === $idAdminAtual && in_array($acao, ['bloquear', 'excluir'], true)) {
            registrarLogSistema($pdo, 'CRITICAL', 'SEGURANCA', 'SELF_ADMIN_ACTION_BLOCKED', 'Administrador tentou alterar a propria conta.', 'usuario', $id, $idAdminAtual, [
                'requested_action' => $acao,
                'impact' => 'self_lockout_prevented',
            ], 'BLOCKED');
            $_SESSION['msg'] = 'Voce nao pode bloquear ou excluir a propria conta administrativa.';
        } elseif ($acao === 'bloquear' && $id) {
            $stmt = $pdo->prepare("UPDATE usuario SET status_cadastro = 'bloqueado' WHERE id_usuario = ?");
            $stmt->execute([$id]);
            registrarLogSistema($pdo, 'WARNING', 'USUARIO', 'USER_STATUS_CHANGED', 'Usuario bloqueado pelo painel administrativo.', 'usuario', $id, null, [
                'before' => 'confirmado',
                'after' => 'bloqueado',
                'impact' => 'login_denied',
            ], 'SUCCESS');
            $_SESSION['msg'] = 'Usuário bloqueado com sucesso.';
        } elseif ($acao === 'desbloquear' && $id) {
            $stmt = $pdo->prepare("UPDATE usuario SET status_cadastro = 'confirmado' WHERE id_usuario = ?");
            $stmt->execute([$id]);
            registrarLogSistema($pdo, 'INFO', 'USUARIO', 'USER_STATUS_CHANGED', 'Usuario desbloqueado pelo painel administrativo.', 'usuario', $id, null, [
                'before' => 'bloqueado',
                'after' => 'confirmado',
                'impact' => 'login_allowed',
            ], 'SUCCESS');
            $_SESSION['msg'] = 'Usuário desbloqueado com sucesso.';
        } elseif ($acao === 'excluir' && $id) {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("UPDATE doador SET id_usuario = NULL WHERE id_usuario = ?");
            $stmt->execute([$id]);

            $stmt = $pdo->prepare("UPDATE ong SET id_usuario = NULL WHERE id_usuario = ?");
            $stmt->execute([$id]);

            $stmt = $pdo->prepare("DELETE FROM usuario WHERE id_usuario = ?");
            $stmt->execute([$id]);
            registrarLogSistema($pdo, 'CRITICAL', 'USUARIO', 'USER_DELETED', 'Usuario excluido pelo painel administrativo.', 'usuario', $id, null, [
                'before' => 'usuario_existente',
                'after' => 'usuario_removido',
                'impact' => 'account_deleted',
            ], 'SUCCESS');
            $pdo->commit();
            $_SESSION['msg'] = 'Usuário excluído com sucesso.';
        } else {
            $_SESSION['msg'] = 'Ação inválida ou usuário não encontrado.';
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
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
<link rel="stylesheet" href="../assets/css/usuarios.css?v=20260423a">
</head>

<body>

<header class="topbar">
    <div>
        <h1>Cruz Azul Admin</h1>
        <p>Gestao de contas da plataforma</p>
    </div>
    <nav>
        <a href="./dashboard.php">Dashboard</a>
        <a href="./ongs.php">ONGs</a>
        <a href="./logs.php">Logs</a>
        <a class="active" href="./usuarios.php">Usuarios</a>
        <a href="./configuracoes.php">Configuracoes</a>
        <a href="./usuarios.php?logout=true">Sair</a>
    </nav>
</header>

<main class="container">
<section class="page-header">
    <h2>Controle de usuarios</h2>
    <p>Acompanhe status, busque contas e execute acoes administrativas com seguranca.</p>
</section>

<?php if($msg): ?>
<p class="flash-msg"><?= htmlspecialchars($msg) ?></p>
<?php endif; ?>

<!-- FILTROS -->
<form method="GET" class="filters">
    <input type="text" name="busca" placeholder="Buscar por email..." value="<?= htmlspecialchars($busca) ?>">
    <select name="status">
        <option value="">Status</option>
        <option value="pendente" <?= $status === 'pendente' ? 'selected' : '' ?>>Pendente</option>
        <option value="confirmado" <?= $status === 'confirmado' ? 'selected' : '' ?>>Confirmado</option>
        <option value="bloqueado" <?= $status === 'bloqueado' ? 'selected' : '' ?>>Bloqueado</option>
    </select>
    <div class="filter-actions">
        <button type="submit">Filtrar</button>
        <a href="./usuarios.php" class="btn-clear">Limpar</a>
    </div>
</form>

<!-- TABELA -->
<section class="table-box">
    <div class="table-header">
        <strong>Resultado da consulta</strong>
        <span><?= count($usuarios) ?> usuario(s) encontrado(s)</span>
    </div>
    <div class="table-wrapper">
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
                <tr><td colspan="5" class="empty">Nenhum usuario encontrado</td></tr>
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
                        <form method="POST" class="action-form">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="id" value="<?= $u['id_usuario'] ?>">
                            <div class="action-buttons">
                            <?php if($u['status_cadastro'] === 'confirmado'): ?>
                                <button class="btn-bloquear" name="acao" value="bloquear" type="submit">Bloquear</button>
                            <?php elseif($u['status_cadastro'] === 'bloqueado'): ?>
                                <button class="btn-ativar" name="acao" value="desbloquear" type="submit">Desbloquear</button>
                            <?php endif; ?>
                            <button class="btn-excluir" name="acao" value="excluir" type="submit" onclick="return confirm('Tem certeza?')">Excluir</button>
                            </div>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</section>

</main>

<footer>
<p>© 2026 Cruz Azul</p>
</footer>

</body>
</html>
