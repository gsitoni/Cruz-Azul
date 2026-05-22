<?php
require __DIR__ . '/auth.php';

if (isset($_GET['logout'])) {
    destruirSessao();
    header("Location: ./index.php");
    exit();
}

require __DIR__ . '/../../api/database.php';
/** @var PDO $pdo */

$busca = $_GET['busca'] ?? '';
$tipo = $_GET['tipo'] ?? '';
$categoria = $_GET['categoria'] ?? '';

$where = "WHERE 1=1";
$params = [];

if ($busca !== '') {
    $where .= " AND (
        descricao LIKE :busca
        OR acao LIKE :busca
        OR tabela_afetada LIKE :busca
        OR ip_origem LIKE :busca
    )";
    $params[':busca'] = "%$busca%";
}

if ($tipo !== '') {
    $where .= " AND tipo = :tipo";
    $params[':tipo'] = strtoupper($tipo);
}

if ($categoria !== '') {
    $where .= " AND categoria = :categoria";
    $params[':categoria'] = strtoupper($categoria);
}

$sqlTotais = "
SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN tipo = 'CRITICAL' THEN 1 ELSE 0 END) AS criticos,
    SUM(CASE WHEN categoria = 'SEGURANCA' THEN 1 ELSE 0 END) AS seguranca,
    SUM(CASE WHEN tipo IN ('WARNING', 'ERROR', 'CRITICAL') THEN 1 ELSE 0 END) AS alertas
FROM logs_sistema
$where
";

$stmtTotais = $pdo->prepare($sqlTotais);
$stmtTotais->execute($params);
$totais = $stmtTotais->fetch(PDO::FETCH_ASSOC) ?: [];

$sql = "
SELECT
    id_log, tipo, categoria, acao, descricao, tabela_afetada,
    id_referencia, ip_origem, user_agent, data_hora
FROM logs_sistema
$where
ORDER BY data_hora DESC
LIMIT 200
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

function badgeTipo(string $tipo): string
{
    return match($tipo) {
        'DEBUG' => 'debug',
        'INFO' => 'info',
        'WARNING' => 'warning',
        'ERROR' => 'error',
        'CRITICAL' => 'critical',
        default => 'info',
    };
}

function detalhesLog(?string $descricao): array
{
    $json = json_decode((string) $descricao, true);
    if (!is_array($json) || empty($json['event_id'])) {
        return [
            'event_id' => '-',
            'user_login' => '-',
            'user_email' => '-',
            'status' => '-',
            'risk' => 'LOW',
            'route' => '-',
            'method' => '-',
            'execution_time_ms' => '-',
            'reason' => $descricao ?: '',
        ];
    }

    return [
        'event_id' => $json['event_id'] ?? '-',
        'user_login' => $json['user_login'] ?? '-',
        'user_email' => $json['user_email'] ?? '-',
        'status' => $json['status'] ?? '-',
        'risk' => $json['risk'] ?? 'LOW',
        'route' => $json['route'] ?? '-',
        'method' => $json['method'] ?? '-',
        'execution_time_ms' => $json['execution_time_ms'] ?? '-',
        'reason' => $json['reason'] ?? '',
    ];
}

function categoriaVisual(string $categoria): string
{
    return match($categoria) {
        'LOGIN' => 'AUTH',
        'SEGURANCA' => 'SECURITY',
        'USUARIO' => 'ADMIN',
        'SISTEMA' => 'SYSTEM',
        default => $categoria,
    };
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Security Center - Logs</title>
<link rel="stylesheet" href="../assets/css/logs.css?v=3">
</head>
<body>

<header class="topbar">
    <div>
        <h1>Cruz Azul Security Center</h1>
        <p>Auditoria, rastreabilidade e deteccao de incidentes</p>
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

<section class="hero">
    <div class="hero-card">
        <span>Total de logs</span>
        <strong><?= (int) ($totais['total'] ?? 0) ?></strong>
    </div>
    <div class="hero-card critical-card">
        <span>Eventos criticos</span>
        <strong><?= (int) ($totais['criticos'] ?? 0) ?></strong>
    </div>
    <div class="hero-card">
        <span>Seguranca</span>
        <strong><?= (int) ($totais['seguranca'] ?? 0) ?></strong>
    </div>
    <div class="hero-card">
        <span>Alertas</span>
        <strong><?= (int) ($totais['alertas'] ?? 0) ?></strong>
    </div>
</section>

<section class="filters-box">
<form method="GET" class="filters">
    <input
        type="text"
        name="busca"
        placeholder="Buscar evento, acao, rota, tabela ou IP..."
        value="<?= htmlspecialchars($busca, ENT_QUOTES, 'UTF-8') ?>"
    >

    <select name="tipo">
        <option value="">Todos os niveis</option>
        <?php foreach (['INFO', 'WARNING', 'ERROR', 'CRITICAL'] as $nivel): ?>
            <option value="<?= $nivel ?>" <?= strtoupper($tipo) === $nivel ? 'selected' : '' ?>><?= $nivel ?></option>
        <?php endforeach; ?>
    </select>

    <select name="categoria">
        <option value="">Todas categorias</option>
        <?php foreach (['LOGIN', 'SEGURANCA', 'USUARIO', 'ONG', 'SISTEMA'] as $cat): ?>
            <option value="<?= $cat ?>" <?= strtoupper($categoria) === $cat ? 'selected' : '' ?>><?= categoriaVisual($cat) ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Filtrar</button>
</form>
</section>

<section class="logs-table-box">
<div class="table-header">
    <h2>Eventos registrados</h2>
    <p>Formato compativel com auditoria, SIEM e investigacao forense.</p>
</div>

<div class="table-wrapper">
<table>
<thead>
<tr>
    <th>ID</th>
    <th>Evento</th>
    <th>Responsavel</th>
    <th>Email</th>
    <th>Data</th>
    <th>Nivel</th>
    <th>Categoria</th>
    <th>Status</th>
    <th>Risco</th>
    <th>Acao</th>
    <th>Detalhes</th>
    <th>Rota</th>
    <th>Tempo</th>
    <th>IP</th>
</tr>
</thead>

<tbody>
<?php if (empty($logs)): ?>
<tr>
    <td colspan="14" class="empty">Nenhum log encontrado.</td>
</tr>
<?php endif; ?>

<?php foreach ($logs as $log): ?>
<?php $detalhes = detalhesLog($log['descricao']); ?>
<tr class="<?= $log['tipo'] === 'CRITICAL' ? 'row-critical' : '' ?>">
    <td>#<?= (int) $log['id_log'] ?></td>
    <td class="mono"><?= htmlspecialchars((string) $detalhes['event_id'], ENT_QUOTES, 'UTF-8') ?></td>
    <td><?= htmlspecialchars((string) $detalhes['user_login'], ENT_QUOTES, 'UTF-8') ?></td>
    <td class="mono"><?= htmlspecialchars((string) $detalhes['user_email'], ENT_QUOTES, 'UTF-8') ?></td>
    <td><?= date('d/m/Y H:i:s', strtotime($log['data_hora'])) ?></td>
    <td><span class="badge <?= badgeTipo((string) $log['tipo']) ?>"><?= htmlspecialchars($log['tipo'], ENT_QUOTES, 'UTF-8') ?></span></td>
    <td><span class="category-tag"><?= htmlspecialchars(categoriaVisual((string) $log['categoria']), ENT_QUOTES, 'UTF-8') ?></span></td>
    <td><span class="status-tag <?= strtolower((string) $detalhes['status']) ?>"><?= htmlspecialchars((string) $detalhes['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
    <td><span class="risk-tag <?= strtolower((string) $detalhes['risk']) ?>"><?= htmlspecialchars((string) $detalhes['risk'], ENT_QUOTES, 'UTF-8') ?></span></td>
    <td class="bold"><?= htmlspecialchars($log['acao'], ENT_QUOTES, 'UTF-8') ?></td>
    <td><?= htmlspecialchars((string) $detalhes['reason'], ENT_QUOTES, 'UTF-8') ?></td>
    <td class="mono"><?= htmlspecialchars($detalhes['method'] . ' ' . $detalhes['route'], ENT_QUOTES, 'UTF-8') ?></td>
    <td><?= htmlspecialchars((string) $detalhes['execution_time_ms'], ENT_QUOTES, 'UTF-8') ?>ms</td>
    <td class="mono"><?= htmlspecialchars($log['ip_origem'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</section>

</main>
</body>
</html>
