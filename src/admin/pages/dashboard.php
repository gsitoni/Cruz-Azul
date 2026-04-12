<?php
// ==========================
// CONFIG COOKIE SEGURO
// ==========================
$secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

// ==========================
// PROTEÇÃO DE ACESSO
// ==========================
if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../../../public/pages/login.php");
    exit();
}

// ==========================
// CSRF TOKEN
// ==========================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
// DADOS DO BANCO
// ==========================
// TODO: Implementar queries no banco de dados
// $logs = obter_logs_banco();
// $ongs = obter_ongs_pendentes_banco();
// $usuarios = obter_usuarios_banco();

$logs = [];
$ongs = [];
$usuarios = [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - Cruz Azul</title>
<link rel="stylesheet" href="../assets/css/dashboard.css">
</head>

<body>

<header>
    <h1>Cruz Azul ✙</h1>
    <nav>
        <ul>
            <li><a href="./dashboard.php">Dashboard</a></li>
            <li><a href="./ongs.php">ONGs</a></li>
            <li><a href="./logs.php">Logs</a></li>
            <li><a href="./usuarios.php">Usuários</a></li>
            <li><a href="./configuracoes.php">Configurações</a></li>
            <li><a href="?logout=true">Sair</a></li>
        </ul>
    </nav>
</header>

<section>
    <h2>Dashboard de Segurança</h2>

    <article>
        <h3>⚠️ Visão Geral</h3>
        <ul>
            <li>Usuário logado: <strong><?= htmlspecialchars($_SESSION['usuario']) ?></strong></li>
            <li>Status: <strong>Seguro</strong></li>
            <li>Tentativas falhas: <strong><?= count($logs) > 0 ? count(array_filter($logs, fn($l) => isset($l['status']) && $l['status'] === 'falha')) : 0 ?></strong></li>
        </ul>
    </article>

    <article>
        <h3>🚨 Alertas</h3>
        <?php if(count($logs) === 0): ?>
        <p>Nenhum alerta no momento</p>
        <?php else: ?>
        <ul>
            <?php foreach(array_slice($logs, 0, 5) as $log): ?>
            <li>[<?= strtoupper($log['nivel'] ?? 'INFO') ?>] <?= htmlspecialchars($log['acao'] ?? '') ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </article>

    <article>
        <h3>📜 Últimos Logs</h3>
        <?php if(count($logs) === 0): ?>
        <p>Nenhum log registrado</p>
        <?php else: ?>
        <table>
            <tr>
                <th>Data</th><th>Usuário</th><th>Ação</th><th>Nível</th>
            </tr>
            <?php foreach(array_slice($logs, 0, 10) as $log): ?>
            <tr>
                <td><?= htmlspecialchars($log['data'] ?? '') ?></td>
                <td><?= htmlspecialchars($log['usuario'] ?? '') ?></td>
                <td><?= htmlspecialchars($log['acao'] ?? '') ?></td>
                <td><?= htmlspecialchars($log['nivel'] ?? 'info') ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <p><a href="./logs.php">Ver todos os logs →</a></p>
        <?php endif; ?>
    </article>
</section>

<section>
<h2>Solicitações de ONGs Pendentes</h2>
<?php if(count($ongs) === 0): ?>
<p>Nenhuma solicitação pendente</p>
<?php else: ?>
<?php foreach(array_slice($ongs, 0, 5) as $ong): ?>
<article>
    <h3><?= htmlspecialchars($ong['nome'] ?? '') ?></h3>
    <p><?= htmlspecialchars($ong['email'] ?? '') ?></p>
    <p><?= htmlspecialchars($ong['descricao'] ?? '') ?></p>
</article>
<?php endforeach; ?>
<p><a href="./ongs.php">Ver todas as solicitações →</a></p>
<?php endif; ?>
</section>

<section>
<h2>Gerenciamento de Usuários</h2>
<p>Total de usuários: <?= count($usuarios) ?></p>
<p><a href="./usuarios.php">Ir para gerenciamento de usuários →</a></p>
</section>

<footer>
<p>&copy; 2026 Cruz Azul</p>
</footer>
<script src="../assets/js/dashboard.js"></script>

</body>
</html>