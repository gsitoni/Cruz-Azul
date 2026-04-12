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
// DADOS DO BANCO
// ==========================
// TODO: Implementar queries no banco de dados
// $logs = obter_logs_banco(limite: 1000);

$logs = [];

// ==========================
// FILTROS
// ==========================
$busca = $_GET['busca'] ?? "";
$nivel = $_GET['nivel'] ?? "";
$periodo = $_GET['periodo'] ?? "";

// função filtro
$logs_filtrados = array_filter($logs, function($log) use ($busca, $nivel, $periodo) {

    $matchBusca = empty($busca) || 
        stripos($log['usuario'], $busca) !== false || 
        stripos($log['acao'], $busca) !== false;

    $matchNivel = empty($nivel) || $log['nivel'] === strtolower($nivel);

    $matchPeriodo = true;

    if ($periodo) {
        $dataLog = strtotime($log['data']);
        $agora = time();

        switch ($periodo) {
            case "24h":
                $matchPeriodo = ($agora - $dataLog) <= 86400;
                break;
            case "7d":
                $matchPeriodo = ($agora - $dataLog) <= 604800;
                break;
            case "30d":
                $matchPeriodo = ($agora - $dataLog) <= 2592000;
                break;
        }
    }

    return $matchBusca && $matchNivel && $matchPeriodo;
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

// ==========================
// RESPOSTA JSON PARA AJAX
// ==========================
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {

    header('Content-Type: application/json');
    echo json_encode(array_values($logs_filtrados));
    exit();
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

    <select name="periodo">
        <option value="">Período</option>
        <option value="24h">Últimas 24h</option>
        <option value="7d">Últimos 7 dias</option>
        <option value="30d">Último mês</option>
    </select>

    <button type="submit">Filtrar</button>
</form>

<!-- TABELA -->
<section class="table-box">

<table>
<thead>
<tr>
    <th>Data/Hora</th>
    <th>Usuário</th>
    <th>Ação</th>
    <th>IP</th>
    <th>Nível</th>
</tr>
</thead>

<tbody>

<?php if (empty($logs_filtrados)): ?>
<tr>
    <td colspan="5">Nenhum log encontrado</td>
</tr>
<?php endif; ?>

<?php foreach ($logs_filtrados as $log): ?>
<tr>
    <td><?= htmlspecialchars($log['data']) ?></td>
    <td><?= htmlspecialchars($log['usuario']) ?></td>
    <td><?= htmlspecialchars($log['acao']) ?></td>
    <td><?= htmlspecialchars($log['ip']) ?></td>
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
<script src="../js/logs.js"></script>

</body>assets/
</html>