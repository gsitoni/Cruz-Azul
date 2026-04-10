<?php
session_start();

// ==========================
// PROTEÇÃO DE ACESSO
// ==========================
if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ./usuarios.php");
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
$usuarios = [
    ["nome"=>"admin","email"=>"admin@cruzazul.com","tipo"=>"admin","status"=>"ativo","ultimo"=>"Hoje 10:32"],
    ["nome"=>"ong_teste","email"=>"ong@teste.com","tipo"=>"ong","status"=>"bloqueado","ultimo"=>"Ontem 18:20"],
    ["nome"=>"usuario123","email"=>"user@email.com","tipo"=>"comum","status"=>"ativo","ultimo"=>"Hoje 09:10"],
];

// ==========================
// FILTROS
// ==========================
$busca = $_GET['busca'] ?? "";
$tipo = $_GET['tipo'] ?? "";
$status = $_GET['status'] ?? "";

$usuarios_filtrados = array_filter($usuarios, function($u) use ($busca, $tipo, $status) {
    return
        (empty($busca) || stripos($u['nome'], $busca) !== false) &&
        (empty($tipo) || $u['tipo'] === strtolower($tipo)) &&
        (empty($status) || $u['status'] === strtolower($status));
});

// ==========================
// AÇÕES
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF inválido");
    }

    $acao = $_POST['acao'] ?? "";
    $usuario = $_POST['usuario'] ?? "";

    // Aqui entra banco:
    // UPDATE usuarios SET status='bloqueado' WHERE nome=?

    $_SESSION['msg'] = "Ação '$acao' aplicada ao usuário $usuario";

    header("Location: ./usuarios.php");
    exit();
}

$msg = $_SESSION['msg'] ?? "";
unset($_SESSION['msg']);

// ==========================
// HELPERS VISUAIS
// ==========================
function badgeTipo($tipo) {
    return match($tipo) {
        "admin" => "admin",
        "ong" => "ong",
        default => "comum"
    };
}

function badgeStatus($status) {
    return $status === "ativo" ? "ativo" : "bloqueado";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Usuários - Cruz Azul</title>
<link rel="stylesheet" href="../css/usuarios.css">
</head>

<body>

<header class="header">
    <h1>Cruz Azul ✙</h1>
    <nav>
        <a href="dashboard_admin.php">Dashboard</a>
        <a href="ongs.php">ONGs</a>
        <a href="logs.php">Logs</a>
        <a href="usuarios.php">Usuários</a>
        <a href="configuracoes.php">Config</a>
        <a href="logout.php">Sair</a>
    </nav>
</header>

<main class="container">

<h2>Controle de Usuários</h2>

<?php if($msg): ?>
<p style="color:green;"><?= htmlspecialchars($msg) ?></p>
<?php endif; ?>

<!-- FILTROS -->
<form method="GET" class="filters">

<input type="text" name="busca" placeholder="Buscar usuário..." value="<?= htmlspecialchars($busca) ?>">

<select name="tipo">
    <option value="">Tipo</option>
    <option value="admin">Administrador</option>
    <option value="ong">ONG</option>
    <option value="comum">Usuário comum</option>
</select>

<select name="status">
    <option value="">Status</option>
    <option value="ativo">Ativo</option>
    <option value="bloqueado">Bloqueado</option>
</select>

<button type="submit">Filtrar</button>

</form>

<!-- TABELA -->
<section class="table-box">

<table>
<thead>
<tr>
    <th>Usuário</th>
    <th>Email</th>
    <th>Tipo</th>
    <th>Status</th>
    <th>Último acesso</th>
    <th>Ações</th>
</tr>
</thead>

<tbody>

<?php if(empty($usuarios_filtrados)): ?>
<tr><td colspan="6">Nenhum usuário encontrado</td></tr>
<?php endif; ?>

<?php foreach($usuarios_filtrados as $u): ?>
<tr>

<td><?= htmlspecialchars($u['nome']) ?></td>
<td><?= htmlspecialchars($u['email']) ?></td>

<td>
<span class="badge <?= badgeTipo($u['tipo']) ?>">
<?= strtoupper($u['tipo']) ?>
</span>
</td>

<td>
<span class="badge <?= badgeStatus($u['status']) ?>">
<?= strtoupper($u['status']) ?>
</span>
</td>

<td><?= htmlspecialchars($u['ultimo']) ?></td>

<td>

<form method="POST" style="display:inline;">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<input type="hidden" name="usuario" value="<?= htmlspecialchars($u['nome']) ?>">

<?php if($u['status'] === "ativo"): ?>
<button class="btn-bloquear" name="acao" value="bloquear">Bloquear</button>
<?php else: ?>
<button class="btn-ativar" name="acao" value="ativar">Ativar</button>
<?php endif; ?>

<?php if($u['tipo'] !== "admin"): ?>
<button class="btn-promover" name="acao" value="promover">Promover</button>
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

</body>
</html>