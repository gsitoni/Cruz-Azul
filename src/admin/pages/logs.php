<?php
require __DIR__ . '/auth.php';

if (isset($_GET['logout'])) {
    destruirSessao();
    header("Location: ../index.php");
    exit();
}

require __DIR__ . '/../../api/database.php';
/** @var PDO $pdo */

// ==========================
// FILTROS
// ==========================
$busca = $_GET['busca'] ?? '';
$tipo = $_GET['tipo'] ?? '';
$categoria = $_GET['categoria'] ?? '';

// ==========================
// FILTROS SQL
// ==========================
$where = "WHERE 1=1";
$params = [];

if (!empty($busca)) {
    $where .= " AND (
        descricao LIKE :busca
        OR acao LIKE :busca
        OR tabela_afetada LIKE :busca
    )";

    $params[':busca'] = "%$busca%";
}

if (!empty($tipo)) {
    $where .= " AND tipo = :tipo";
    $params[':tipo'] = strtoupper($tipo);
}

if (!empty($categoria)) {
    $where .= " AND categoria = :categoria";
    $params[':categoria'] = strtoupper($categoria);
}

// ==========================
// CONTADORES
// ==========================
$sqlTotais = "
SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN tipo = 'CRITICAL' THEN 1 ELSE 0 END) AS criticos,
    SUM(CASE WHEN categoria = 'SEGURANCA' THEN 1 ELSE 0 END) AS seguranca
FROM logs_sistema
$where
";

$stmtTotais = $pdo->prepare($sqlTotais);
$stmtTotais->execute($params);
$totais = $stmtTotais->fetch(PDO::FETCH_ASSOC) ?: [];

// ==========================
// QUERY LOGS
// ==========================
$sql = "
SELECT
    id_log,
    tipo,
    categoria,
    acao,
    descricao,
    tabela_afetada,
    id_referencia,
    ip_origem,
    data_hora
FROM logs_sistema
$where
ORDER BY data_hora DESC
LIMIT 200
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================
// BADGES
// ==========================
function badgeTipo($tipo)
{
    return match($tipo) {
        'INFO' => 'info',
        'WARNING' => 'warning',
        'ERROR' => 'error',
        'CRITICAL' => 'critical',
        default => 'info'
    };
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Monitoramento de Logs</title>

<link rel="stylesheet" href="../assets/css/logs.css?v=2">
</head>

<body>

<header class="topbar">

    <div>
        <h1>Cruz Azul Security Center</h1>
        <p>Monitoramento e auditoria do sistema</p>
    </div>

    <nav>
        <a href="./dashboard.php">Dashboard</a>
        <a href="./ongs.php">ONGs</a>
        <a class="active" href="./logs.php">Logs</a>
        <a href="./usuarios.php">Usuários</a>
        <a href="./configuracoes.php">Configurações</a>
        <a href="./logs.php?logout=true">Sair</a>
    </nav>

</header>

<main class="container">

<section class="hero">

    <div class="hero-card">
        <span>Total de Logs</span>
        <strong><?= (int) ($totais['total'] ?? 0) ?></strong>
    </div>

    <div class="hero-card">
        <span>Eventos críticos</span>
        <strong><?= (int) ($totais['criticos'] ?? 0) ?></strong>
    </div>

    <div class="hero-card">
        <span>Eventos de segurança</span>
        <strong><?= (int) ($totais['seguranca'] ?? 0) ?></strong>
    </div>

</section>

<section class="filters-box">

<form method="GET" class="filters">

    <input
        type="text"
        name="busca"
        placeholder="Buscar ação, descrição ou tabela..."
        value="<?= htmlspecialchars($busca) ?>"
    >

    <select name="tipo">
        <option value="">Todos os níveis</option>
        <option value="INFO">INFO</option>
        <option value="WARNING">WARNING</option>
        <option value="ERROR">ERROR</option>
        <option value="CRITICAL">CRITICAL</option>
    </select>

    <select name="categoria">
        <option value="">Todas categorias</option>
        <option value="DOACAO">DOAÇÃO</option>
        <option value="ESTOQUE">ESTOQUE</option>
        <option value="DISTRIBUICAO">DISTRIBUIÇÃO</option>
        <option value="SEGURANCA">SEGURANÇA</option>
        <option value="LOGIN">LOGIN</option>
    </select>

    <button type="submit">
        Filtrar
    </button>

</form>

</section>

<section class="logs-table-box">

<div class="table-header">
    <h2>Eventos registrados</h2>
</div>

<div class="table-wrapper">

<table>

<thead>

<tr>
    <th>ID</th>
    <th>Data</th>
    <th>Nível</th>
    <th>Categoria</th>
    <th>Ação</th>
    <th>Descrição</th>
    <th>Tabela</th>
    <th>Referência</th>
    <th>IP</th>
</tr>

</thead>

<tbody>

<?php if(empty($logs)): ?>

<tr>
<td colspan="9" class="empty">
Nenhum log encontrado.
</td>
</tr>

<?php endif; ?>

<?php foreach($logs as $log): ?>

<tr>

<td>#<?= $log['id_log'] ?></td>

<td>
<?= date('d/m/Y H:i:s', strtotime($log['data_hora'])) ?>
</td>

<td>
<span class="badge <?= badgeTipo($log['tipo']) ?>">
<?= $log['tipo'] ?>
</span>
</td>

<td>
<?= htmlspecialchars($log['categoria']) ?>
</td>

<td class="bold">
<?= htmlspecialchars($log['acao']) ?>
</td>

<td>
<?= htmlspecialchars($log['descricao']) ?>
</td>

<td>
<?= htmlspecialchars($log['tabela_afetada']) ?>
</td>

<td>
<?= $log['id_referencia'] ?>
</td>

<td>
<?= $log['ip_origem'] ?? 'N/A' ?>
</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</section>

</main>

</body>
</html>
