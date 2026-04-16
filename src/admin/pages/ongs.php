<?php
require __DIR__ . '/auth.php';

// ==========================
// LOGOUT
// ==========================
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../../../public/pages/login.php");
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
    
} catch (PDOException $e) {
    $ongs = [];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Solicitações de ONGs</title>
<link rel="stylesheet" href="../assets/css/ongs.css">
</head>

<body>

<header>
    <h1>Cruz Azul ✙</h1>
    <nav>
        <a href="./dashboard.php">Dashboard</a>
        <a href="./ongs.php">ONGs</a>
        <a href="./logs.php">Logs</a>
        <a href="./usuarios.php">Usuários</a>
        <a href="./configuracoes.php">Configurações</a>
        <a href="./ongs.php?logout=true">Sair</a>
    </nav>
</header>

<main class="container">

<h2>Gerenciamento de ONGs</h2>

<?php if(!empty($msg)): ?>
    <p style="color:green;"><?= htmlspecialchars($msg) ?></p>
<?php endif; ?>

<!-- FILTROS -->
<form method="GET" class="filters">
    <input type="text" name="busca" placeholder="Buscar ONG..." value="<?= htmlspecialchars($busca) ?>">
    <select name="status">
        <option value="">Status</option>
        <option value="pendente">Pendente</option>
        <option value="ativo">Ativo</option>
        <option value="rejeitado">Rejeitado</option>
    </select>
    <button type="submit">Filtrar</button>
</form>

<!-- TABELA -->
<section class="table-box">
    <table>
        <thead>
            <tr>
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
                <tr><td colspan="6">Nenhuma ONG encontrada</td></tr>
            <?php endif; ?>
            <?php foreach($ongs as $ong): ?>
                <tr>
                    <td><?= htmlspecialchars($ong['nome']) ?></td>
                    <td><?= htmlspecialchars($ong['email']) ?></td>
                    <td><?= htmlspecialchars($ong['email'] ?: 'Nao informado') ?></td>
                    <td><?= htmlspecialchars($ong['endereco']) ?></td>
                    <td>
                        <span class="badge <?= $ong['status_elegibilidade'] === 'ativo' ? 'aprovado' : ($ong['status_elegibilidade'] === 'pendente' ? 'pendente' : 'rejeitado') ?>">
                            <?= strtoupper($ong['status_elegibilidade']) ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="id" value="<?= $ong['id_ong'] ?>">
                            <?php if($ong['status_elegibilidade'] === 'pendente'): ?>
                                <button class="btn-aprovar" name="acao" value="aprovar">Aprovar</button>
                                <button class="btn-rejeitar" name="acao" value="rejeitar">Rejeitar</button>
                            <?php endif; ?>
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
<script src="../assets/js/ongs.js"></script>

</body>
</html>