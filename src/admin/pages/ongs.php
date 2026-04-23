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
        if ($acao === 'aprovar' && $id) {
            $stmt = $pdo->prepare("UPDATE ong SET status_elegibilidade = 'ativo' WHERE id_ong = ?");
            $stmt->execute([$id]);
            $_SESSION['msg'] = 'ONG aprovada com sucesso.';
        } elseif ($acao === 'rejeitar' && $id) {
            $stmt = $pdo->prepare("UPDATE ong SET status_elegibilidade = 'rejeitado' WHERE id_ong = ?");
            $stmt->execute([$id]);
            $_SESSION['msg'] = 'ONG rejeitada com sucesso.';
        } else {
            $_SESSION['msg'] = 'Ação inválida ou ONG não encontrada.';
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

$msg = $_SESSION['msg'] ?? "";
unset($_SESSION['msg']);

// ==========================
// QUERY ONGS
// ==========================
try {
    $sql = "SELECT id_ong, nome, email, endereco, status_elegibilidade FROM ong WHERE 1=1";
    $params = [];
    
    if (!empty($busca)) {
        $sql .= " AND nome LIKE ?";
        $params[] = "%$busca%";
    }
    
    if (!empty($status)) {
        $sql .= " AND status_elegibilidade = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY nome ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ongs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM ong WHERE status_elegibilidade = 'pendente'");
    $stmt->execute();
    $totalPendentes = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM ong WHERE status_elegibilidade = 'ativo'");
    $stmt->execute();
    $totalAtivas = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM ong WHERE status_elegibilidade = 'rejeitado'");
    $stmt->execute();
    $totalRejeitadas = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
} catch (PDOException $e) {
    $ongs = [];
    $totalPendentes = 0;
    $totalAtivas = 0;
    $totalRejeitadas = 0;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Solicitações de ONGs</title>
<link rel="stylesheet" href="../assets/css/ongs.css?v=20260423b">
</head>

<body>

<header class="topbar">
    <div>
        <h1>Cruz Azul Admin</h1>
        <p>Central de analise e moderacao de ONGs</p>
    </div>
    <nav>
        <a href="./dashboard.php">Dashboard</a>
        <a class="active" href="./ongs.php">ONGs</a>
        <a href="./logs.php">Logs</a>
        <a href="./usuarios.php">Usuarios</a>
        <a href="./configuracoes.php">Configuracoes</a>
        <a href="./ongs.php?logout=true">Sair</a>
    </nav>
</header>

<main class="container">

<section class="page-header">
    <h2>Gerenciamento de ONGs</h2>
    <p>Visualize o status das solicitacoes e aprove ou rejeite cadastros pendentes com rastreabilidade.</p>
</section>

<?php if(!empty($msg)): ?>
    <p class="flash-msg"><?= htmlspecialchars($msg) ?></p>
<?php endif; ?>

<section class="stats-grid">
    <article class="stat-card stat-pendente">
        <h3>Pendentes</h3>
        <p><?= $totalPendentes ?></p>
    </article>
    <article class="stat-card">
        <h3>Ativas</h3>
        <p><?= $totalAtivas ?></p>
    </article>
    <article class="stat-card">
        <h3>Rejeitadas</h3>
        <p><?= $totalRejeitadas ?></p>
    </article>
    <article class="stat-card">
        <h3>Total em analise</h3>
        <p><?= count($ongs) ?></p>
    </article>
</section>

<!-- FILTROS -->
<form method="GET" class="filters">
    <input type="text" name="busca" placeholder="Buscar ONG por nome..." value="<?= htmlspecialchars($busca) ?>">
    <select name="status">
        <option value="">Status</option>
        <option value="pendente" <?= $status === 'pendente' ? 'selected' : '' ?>>Pendente</option>
        <option value="ativo" <?= $status === 'ativo' ? 'selected' : '' ?>>Ativo</option>
        <option value="rejeitado" <?= $status === 'rejeitado' ? 'selected' : '' ?>>Rejeitado</option>
    </select>
    <div class="filter-actions">
        <button type="submit">Filtrar</button>
        <a href="./ongs.php" class="btn-clear">Limpar</a>
    </div>
</form>

<!-- TABELA -->
<section class="table-box">
    <div class="table-header">
        <strong>Resultado da consulta</strong>
        <span><?= count($ongs) ?> ONG(s) encontrada(s)</span>
    </div>
    <div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Contato</th>
                <th>Endereço</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($ongs)): ?>
                <tr><td colspan="7" class="empty">Nenhuma ONG encontrada</td></tr>
            <?php endif; ?>
            <?php foreach($ongs as $ong): ?>
                <tr>
                    <td>#<?= (int) $ong['id_ong'] ?></td>
                    <td><?= htmlspecialchars($ong['nome']) ?></td>
                    <td><?= htmlspecialchars($ong['email']) ?></td>
                    <td><?= htmlspecialchars($ong['email'] ?: 'Nao informado') ?></td>
                    <td><?= htmlspecialchars($ong['endereco'] ?: 'Nao informado') ?></td>
                    <td>
                        <span class="badge <?= $ong['status_elegibilidade'] === 'ativo' ? 'aprovado' : ($ong['status_elegibilidade'] === 'pendente' ? 'pendente' : 'rejeitado') ?>">
                            <?= strtoupper($ong['status_elegibilidade']) ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" class="action-form">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="id" value="<?= $ong['id_ong'] ?>">
                            <?php if($ong['status_elegibilidade'] === 'pendente'): ?>
                                <div class="action-buttons">
                                    <button class="btn-aprovar" name="acao" value="aprovar" data-acao="aprovar">Aprovar</button>
                                    <button class="btn-rejeitar" name="acao" value="rejeitar" data-acao="rejeitar">Rejeitar</button>
                                </div>
                            <?php else: ?>
                                <span class="status-finalizado">Sem acoes pendentes</span>
                            <?php endif; ?>
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
<script src="../assets/js/ongs.js"></script>

</body>
</html>
