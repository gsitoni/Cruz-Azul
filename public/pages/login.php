<?php
// ==========================
// CONFIG COOKIE SEGURO
// ==========================
$secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
 
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/Cruz-Azul',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();
 
// Headers de segurança
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
 
// ==========================
// PROTEÇÃO DE ACESSO
// ==========================
if (
    !isset($_SESSION['usuario']) ||
    !isset($_SESSION['usuario']['permissao']) ||
    strpos($_SESSION['usuario']['permissao'], 'Admin') === false
) {
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
// CONEXÃO BANCO
// ==========================
require __DIR__ . '/../../api/database.php';
 
// ==========================
// DADOS DO BANCO
// ==========================
try {
    // Logs de segurança (simulando, pois não há tabela logs)
    $logs = [];
    
    // ONGs pendentes (usando tabela beneficiario ou algo, mas ajustando)
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM beneficiario WHERE status_elegibilidade = 'pendente'");
    $stmt->execute();
    $ongs_pendentes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Usuários
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuario");
    $stmt->execute();
    $total_usuarios = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Doações
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM doacao");
    $stmt->execute();
    $total_doacoes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
} catch (PDOException $e) {
    die("Erro no banco: " . $e->getMessage());
}
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
            <li>Usuário logado: <strong><?= htmlspecialchars($_SESSION['usuario']['nome'] ?? 'Admin') ?></strong></li>
            <li>Status: <strong>Seguro</strong></li>
            <li>ONGs pendentes: <strong><?= $ongs_pendentes ?></strong></li>
            <li>Total usuários: <strong><?= $total_usuarios ?></strong></li>
            <li>Total doações: <strong><?= $total_doacoes ?></strong></li>
        </ul>
    </article>
 
    <article>
        <h3>🚨 Alertas</h3>
        <p>Nenhum alerta crítico no momento</p>
        <p>ONGs pendentes: <?= $ongs_pendentes ?></p>
    </article>
 
    <article>
        <h3>📜 Estatísticas</h3>
        <ul>
            <li>Usuários registrados: <?= $total_usuarios ?></li>
            <li>Doações realizadas: <?= $total_doacoes ?></li>
            <li>ONGs ativas: (não implementado)</li>
        </ul>
    </article>
</section>
 
<section>
<h2>Solicitações de ONGs Pendentes</h2>
<p>Total de solicitações pendentes: <?= $ongs_pendentes ?></p>
<p><a href="./ongs.php">Gerenciar ONGs →</a></p>
</section>
 
<section>
<h2>Gerenciamento de Usuários</h2>
<p>Total de usuários: <?= $total_usuarios ?></p>
<p><a href="./usuarios.php">Gerenciar usuários →</a></p>
</section>
 
<footer>
<p>&copy; 2026 Cruz Azul</p>
</footer>
<script src="../assets/js/dashboard.js"></script>
 
</body>
</html>