<?php
session_start();

// ==========================
// PROTEÇÃO DE ACESSO
// ==========================
if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../pages/login.php");
    exit();
}

// ==========================
// CSRF
// ==========================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ==========================
// DADOS MOCK (trocar por BD)
// ==========================
$ongs = [
    [
        "nome"=>"Mãos Solidárias",
        "email"=>"contato@maos.org",
        "cnpj"=>"00.000.000/0001-00",
        "descricao"=>"Apoio a famílias",
        "status"=>"pendente"
    ],
    [
        "nome"=>"Esperança Viva",
        "email"=>"contato@esperanca.org",
        "cnpj"=>"11.111.111/0001-11",
        "descricao"=>"Projetos educacionais",
        "status"=>"pendente"
    ]
];

// ==========================
// FILTROS
// ==========================
$busca = $_GET['busca'] ?? "";
$status = $_GET['status'] ?? "";

// filtrar
$ongs_filtradas = array_filter($ongs, function($ong) use ($busca, $status) {
    return
        (empty($busca) || stripos($ong['nome'], $busca) !== false) &&
        (empty($status) || $ong['status'] === strtolower($status));
});

// ==========================
// AÇÃO (aprovar/rejeitar)
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF inválido");
    }

    $acao = $_POST['acao'] ?? "";
    $nome = $_POST['nome'] ?? "";

    // aqui entraria o UPDATE no banco
    // exemplo:
    // UPDATE ongs SET status='aprovado' WHERE nome=?

    // apenas feedback simulado
    $_SESSION['mensagem'] = "Ação '$acao' realizada para $nome";

    header("Location: ongs.php");
    exit();
}

$mensagem = $_SESSION['mensagem'] ?? "";
unset($_SESSION['mensagem']);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Solicitações de ONGs</title>
<link rel="stylesheet" href="../css/ongs.css">
</head>

<body>

<header>
    <h1>Cruz Azul ✙</h1>
    <nav>
        <a href="dashboard_admin.php">Dashboard</a>
        <a href="ongs.php">ONGs</a>
        <a href="logs.php">Logs</a>
        <a href="usuarios.php">Usuários</a>
        <a href="?logout=true">Sair</a>
    </nav>
</header>

<main class="container">

<h2>Solicitações de ONGs</h2>

<?php if($mensagem): ?>
<p style="color:green;"><?= htmlspecialchars($mensagem) ?></p>
<?php endif; ?>

<!-- FILTROS -->
<form method="GET" class="filters">
    <input type="text" name="busca" placeholder="Buscar ONG..." value="<?= htmlspecialchars($busca) ?>">

    <select name="status">
        <option value="">Status</option>
        <option value="pendente">Pendente</option>
        <option value="aprovado">Aprovado</option>
        <option value="rejeitado">Rejeitado</option>
    </select>

    <button type="submit">Filtrar</button>
</form>

<!-- LISTA -->
<section class="ongs-list">

<?php foreach($ongs_filtradas as $ong): ?>

<div class="ong-card">

<div class="ong-info">
    <h3><?= htmlspecialchars($ong['nome']) ?></h3>
    <p><strong>Email:</strong> <?= htmlspecialchars($ong['email']) ?></p>
    <p><strong>CNPJ:</strong> <?= htmlspecialchars($ong['cnpj']) ?></p>
    <p><?= htmlspecialchars($ong['descricao']) ?></p>
</div>

<div class="ong-actions">
    <span class="badge"><?= strtoupper($ong['status']) ?></span>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="nome" value="<?= htmlspecialchars($ong['nome']) ?>">

        <button name="acao" value="aprovar">Aprovar</button>
        <button name="acao" value="rejeitar">Rejeitar</button>
    </form>
</div>

</div>

<?php endforeach; ?>

</section>

</main>

<footer>
<p>© 2026 Cruz Azul</p>
</footer>
<script src="../js/ongs.js"></script>

</body>
</html>