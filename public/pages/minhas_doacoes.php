<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
$nome = $_SESSION['usuario']['nome'] ?? $_SESSION['usuario']['email'] ?? 'Usuário';
// Extrair primeiro nome
if (strpos($nome, '@') !== false) {
    $primeiroNome = explode('@', $nome)[0];
} else {
    $primeiroNome = explode(' ', $nome)[0];
}
$doacoes = [
    ['data' => '2026-04-05', 'ong' => 'Abrigo Esperança', 'item' => 'Cestas básicas', 'status' => 'Entregue'],
    ['data' => '2026-04-08', 'ong' => 'Casa do Bem', 'item' => 'Kits de higiene', 'status' => 'Em andamento'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Doações — Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/minhas_doacoes.css">
</head>
<body>
<nav>
    <span class="logo">🤝 Cruz Azul</span>
    <div>
        <a href="home_usuario.php">Início</a>
        <a href="doar.php">Fazer doação</a>
        <a href="servicos_publicos.php">Serviços Públicos</a>
        <a href="ongs.php">ONGs</a>
        <a href="logout.php">Sair</a>
    </div>
</nav>
<div class="container">
    <div class="header">
        <h1>Minhas doações</h1>
        <p>Olá, <?php echo htmlspecialchars($primeiroNome); ?>! 👋 Veja o histórico das suas contribuições.</p>
    </div>
    <?php if (empty($doacoes)): ?>
        <div class="box">Nenhuma doação registrada ainda.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>ONG</th>
                    <th>Item</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($doacoes as $doacao): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($doacao['data']); ?></td>
                        <td><?php echo htmlspecialchars($doacao['ong']); ?></td>
                        <td><?php echo htmlspecialchars($doacao['item']); ?></td>
                        <td class="status-<?php echo $doacao['status'] === 'Entregue' ? 'entregue' : 'andamento'; ?>">
                            <?php echo htmlspecialchars($doacao['status']); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
