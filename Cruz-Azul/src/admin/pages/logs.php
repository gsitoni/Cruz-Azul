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
<link rel="stylesheet" href="../assets/css/logs.css">
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
        <a href="./logs.php?logout=true">Sair</a>
    </nav>
</header>

<main class="container">

<h2>Logs de Segurança</h2>

<!-- FILTROS -->
<form method="GET" class="filters">
    <input type="text" name="busca" placeholder="Buscar..." value="<?= htmlspecialchars($busca) ?>">
    <select name="nivel">
        <option value="">Todos os níveis</option>
        <option value="info">Info</option>
        <option value="alerta">Alerta</option>
        <option value="critico">Crítico</option>
    </select>
    <button type="submit">Filtrar</button>
</form>

<!-- TABELA -->
<section class="table-box">
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
                <tr><td colspan="4">Nenhum log encontrado</td></tr>
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
</section>
</main>

<footer>
<p>© 2026 Cruz Azul</p>
</footer>
<script src="../assets/js/logs.js"></script>

</body>
</html>