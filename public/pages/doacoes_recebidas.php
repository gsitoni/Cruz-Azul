<?php
session_start();
if (!isset($_SESSION['ong'])) {
    header('Location: login_ong.php');
    exit;
}
$nome = $_SESSION['ong']['nome'] ?? $_SESSION['ong']['email'] ?? 'ONG';
// Extrair primeiro nome
if (strpos($nome, '@') !== false) {
    $primeiroNome = explode('@', $nome)[0];
} else {
    $primeiroNome = explode(' ', $nome)[0];
}
$doacoes = [
    ['item' => 'Arroz e feijão', 'remetente' => 'João Silva', 'data' => '05/04/2026', 'status' => 'Aguardando'],
    ['item' => 'Kit higiene', 'remetente' => 'Maria Oliveira', 'data' => '04/04/2026', 'status' => 'Aguardando'],
    ['item' => 'Roupas de inverno', 'remetente' => 'Pedro Costa', 'data' => '03/04/2026', 'status' => 'Confirmado'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doações Recebidas — Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/doacoes_recebidas.css">
</head>
<body>
<nav>
    <a href="home_ong.php" class="logo" style="text-decoration:none;color:#fff;">🤝 Cruz Azul</a>
    <div>
        <a href="home_ong.php">Início</a>
        <a href="perfil_ong.php">Perfil</a>
        <a href="logout.php">Sair</a>
    </div>
</nav>

<nav aria-label="breadcrumb" style="background:#e9ecef;border-bottom:1px solid #dee2e6;padding:8px 20px;font-size:13px;">
    <ol style="list-style:none;margin:0 auto;padding:0;display:flex;flex-wrap:wrap;align-items:center;max-width:900px;">
        <li><a href="home_ong.php" style="color:#007BFF;text-decoration:none;">Início</a></li>
        <li><span style="margin:0 6px;color:#aaa;">›</span></li>
        <li style="color:#555;">Doações Recebidas</li>
    </ol>
</nav>

<div class="container">
    <h1>Doações recebidas</h1>
    <p>Olá, <?php echo htmlspecialchars($primeiroNome); ?>! 👋 Confira as doações encaminhadas para sua ONG.</p>
    <div class="card">
        <?php foreach ($doacoes as $doacao): ?>
            <div class="item">
                <div>
                    <strong><?php echo htmlspecialchars($doacao['item']); ?></strong>
                    <div>Remetente: <?php echo htmlspecialchars($doacao['remetente']); ?></div>
                    <div>Data: <?php echo htmlspecialchars($doacao['data']); ?></div>
                </div>
                <div class="status <?php echo $doacao['status'] === 'Confirmado' ? 'status-confirmado' : 'status-aguardando'; ?>">
                    <?php echo htmlspecialchars($doacao['status']); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
