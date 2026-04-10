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
// VERIFICA LOGIN E ROLE
// ==========================
if (!isset($_SESSION['usuario']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: ../pages/public/php/login.php");
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
    header("Location: ../pages/php/login.php");
    exit();
}

// ==========================
// DADOS (SIMULAÇÃO - trocar por BD)
// ==========================
$usuario = htmlspecialchars($_SESSION['usuario']);

$logs = [
    ["data"=>"07/04/2026 10:32","user"=>"admin","acao"=>"Login","status"=>"Sucesso"],
    ["data"=>"07/04/2026 09:58","user"=>"ong_teste","acao"=>"Tentativa","status"=>"Falha"]
];

$ongs = [
    ["nome"=>"Mãos Solidárias","email"=>"contato@maos.org","cnpj"=>"00.000","desc"=>"Ajuda social"],
    ["nome"=>"Esperança Viva","email"=>"contato@esp.org","cnpj"=>"11.111","desc"=>"Educação"]
];

$usuarios = [
    ["user"=>"admin","tipo"=>"Administrador","status"=>"Ativo"],
    ["user"=>"ong_teste","tipo"=>"ONG","status"=>"Bloqueado"]
];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Painel Admin - Cruz Azul</title>
<link rel="stylesheet" href="../css/dashboard.css">
</head>

<body>

<header>
    <h1>Cruz Azul ✙</h1>
    <nav>
        <ul>
            <li><a href="#">Dashboard</a></li>
            <li><a href="ongs.php">ONGs</a></li>
            <li><a href="logs.php">Logs</a></li>
            <li><a href="usuarios.php">Usuários</a></li>
            <li><a href="?logout=true">Sair</a></li>
        </ul>
    </nav>
</header>

<section>
    <h2>Dashboard de Segurança</h2>

    <article>
        <h3>⚠️ Visão Geral</h3>
        <ul>
            <li>Usuário logado: <strong><?= $usuario ?></strong></li>
            <li>Status: <strong>Seguro</strong></li>
            <li>Tentativas falhas: <strong>5</strong></li>
        </ul>
    </article>

    <article>
        <h3>🚨 Alertas</h3>
        <ul>
            <li>[ALERTA] Tentativas de login</li>
            <li>[INFO] Nova ONG cadastrada</li>
        </ul>
    </article>

    <article>
        <h3>📜 Logs</h3>
        <table>
            <tr>
                <th>Data</th><th>Usuário</th><th>Ação</th><th>Status</th>
            </tr>

            <?php foreach($logs as $log): ?>
            <tr>
                <td><?= htmlspecialchars($log['data']) ?></td>
                <td><?= htmlspecialchars($log['user']) ?></td>
                <td><?= htmlspecialchars($log['acao']) ?></td>
                <td><?= htmlspecialchars($log['status']) ?></td>
            </tr>
            <?php endforeach; ?>

        </table>
    </article>
</section>

<section>
<h2>Solicitações de ONGs</h2>

<?php foreach($ongs as $ong): ?>
<article>
    <h3><?= htmlspecialchars($ong['nome']) ?></h3>
    <p><?= htmlspecialchars($ong['email']) ?></p>
    <p><?= htmlspecialchars($ong['desc']) ?></p>

    <form method="POST" action="aprovar_ong.php">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="ong" value="<?= htmlspecialchars($ong['nome']) ?>">
        <button name="acao" value="aprovar">Aprovar</button>
        <button name="acao" value="rejeitar">Rejeitar</button>
    </form>
</article>
<?php endforeach; ?>

</section>

<section>
<h2>Usuários</h2>

<table>
<tr><th>Usuário</th><th>Tipo</th><th>Status</th><th>Ação</th></tr>

<?php foreach($usuarios as $u): ?>
<tr>
<td><?= htmlspecialchars($u['user']) ?></td>
<td><?= htmlspecialchars($u['tipo']) ?></td>
<td><?= htmlspecialchars($u['status']) ?></td>
<td>
    <form method="POST" action="gerenciar_usuario.php">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="user" value="<?= htmlspecialchars($u['user']) ?>">
        <button name="acao" value="toggle">Alterar</button>
    </form>
</td>
</tr>
<?php endforeach; ?>

</table>
</section>

<footer>
<p>&copy; 2026 Cruz Azul</p>
</footer>
<script src="../js/dashboard.js"></script>

</body>
</html>