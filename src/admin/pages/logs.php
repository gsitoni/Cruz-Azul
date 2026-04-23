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
// QUERY LOGS (simulado com usuários criados)
// ==========================
try {
    $stmt = $pdo->prepare("SELECT id_usuario as id, email as usuario, 'cadastro' as acao, 'info' as nivel, data_criacao as data FROM usuario ORDER BY data_criacao DESC LIMIT 100");
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $logs = [];
}

// ==========================
// FILTROS
// ==========================
$busca = $_GET['busca'] ?? "";
$nivel = $_GET['nivel'] ?? "";

// Filtrar
$logs_filtrados = array_filter($logs, function($log) use ($busca, $nivel) {
    $matchBusca = empty($busca) || 
        stripos($log['usuario'], $busca) !== false || 
        stripos($log['acao'], $busca) !== false;
    
    $matchNivel = empty($nivel) || $log['nivel'] === strtolower($nivel);
    
    return $matchBusca && $matchNivel;
});

// ==========================
// FUNÇÃO BADGE
// ==========================
function badgeClass($nivel) {
    return match($nivel) {
        "info" => "info",
        "alerta" => "alerta",
        "critico" => "critico",
        default => "info"
    };
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Logs - Cruz Azul</title>
<link rel="stylesheet" href="../assets/css/logs.css?v=20260423a">
</head>

<body>

<header class="topbar">
    <div>
        <h1>Cruz Azul Admin</h1>
        <p>Painel de monitoramento e auditoria</p>
    </div>
    <nav>
        <a href="./dashboard.php">Dashboard</a>
        <a href="./ongs.php">ONGs</a>
        <a class="active" href="./logs.php">Logs</a>
        <a href="./usuarios.php">Usuarios</a>
        <a href="./configuracoes.php">Configuracoes</a>
        <a href="./logs.php?logout=true">Sair</a>
    </nav>
</header>

<main class="container">

<section class="page-header">
    <h2>Logs de seguranca</h2>
    <p>Visualize eventos recentes da plataforma e filtre por usuario, acao ou nivel de criticidade.</p>
</section>

<!-- FILTROS -->
<form method="GET" class="filters">
    <input type="text" name="busca" placeholder="Buscar..." value="<?= htmlspecialchars($busca) ?>">
    <select name="nivel">
        <option value="">Todos os níveis</option>
        <option value="info" <?= $nivel === 'info' ? 'selected' : '' ?>>Info</option>
        <option value="alerta" <?= $nivel === 'alerta' ? 'selected' : '' ?>>Alerta</option>
        <option value="critico" <?= $nivel === 'critico' ? 'selected' : '' ?>>Critico</option>
    </select>
    <div class="filter-actions">
        <button type="submit">Filtrar</button>
        <a href="./logs.php" class="btn-clear">Limpar</a>
    </div>
</form>

<!-- TABELA -->
<section class="table-box">
    <div class="table-header">
        <strong>Eventos encontrados</strong>
        <span><?= count($logs_filtrados) ?> registro(s)</span>
    </div>
    <div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Usuário</th>
                <th>Ação</th>
                <th>Nível</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($logs_filtrados)): ?>
                <tr><td colspan="4" class="empty">Nenhum log encontrado</td></tr>
            <?php endif; ?>
            <?php foreach($logs_filtrados as $log): ?>
                <tr>
                    <td><?= htmlspecialchars($log['data']) ?></td>
                    <td><?= htmlspecialchars($log['usuario']) ?></td>
                    <td><?= htmlspecialchars($log['acao']) ?></td>
                    <td>
                        <span class="badge <?= badgeClass($log['nivel']) ?>">
                            <?= strtoupper($log['nivel']) ?>
                        </span>
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
<script src="../assets/js/logs.js"></script>

</body>
</html>
