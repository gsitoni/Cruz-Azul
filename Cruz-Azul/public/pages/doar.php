<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

require '../../src/api/database.php';

$nome = $_SESSION['usuario']['nome'] ?? $_SESSION['usuario']['email'] ?? 'Usuário';
if (strpos($nome, '@') !== false) {
    $primeiroNome = explode('@', $nome)[0];
} else {
    $primeiroNome = explode(' ', $nome)[0];
}

$labels_risco = [
    'emergencia'       => 'Emergência',
    'continuo'         => 'Contínuo',
    'pontual'          => 'Pontual',
    'baixa_prioridade' => 'Baixa Prioridade',
];

$stmt = $pdo->query(
    "SELECT id_beneficiario, nome_receptor, classificacao_risco
     FROM beneficiario
     WHERE status_elegibilidade = 'ativo'
     ORDER BY nome_receptor ASC"
);
$ongs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$ongId = intval($_GET['ong'] ?? 0);
if ($ongId > 0) {
    header('Location: fazer_doacao.php?ong=' . $ongId);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doar — Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/doar.css">
</head>
<body>
<nav>
    <a href="home_usuario.php" class="logo" style="text-decoration:none;color:#fff;">🤝 Cruz Azul</a>
    <div>
        <a href="home_usuario.php">Início</a>
        <a href="servicos_publicos.php">Serviços Públicos</a>
        <a href="minhas_doacoes.php">Minhas doações</a>
        <a href="ongs.php">ONGs</a>
        <a href="logout.php">Sair</a>
    </div>
</nav>

<nav aria-label="breadcrumb" style="background:#e9ecef;border-bottom:1px solid #dee2e6;padding:8px 20px;font-size:13px;">
    <ol style="list-style:none;margin:0 auto;padding:0;display:flex;flex-wrap:wrap;align-items:center;max-width:900px;">
        <li><a href="home_usuario.php" style="color:#007BFF;text-decoration:none;">Início</a></li>
        <li><span style="margin:0 6px;color:#aaa;">›</span></li>
        <li style="color:#555;">Fazer Doação</li>
    </ol>
</nav>

<div class="container">
    <div class="header">
        <h1>Fazer doação</h1>
        <p>Olá, <?php echo htmlspecialchars($primeiroNome); ?>! 👋 Escolha uma ONG e faça sua doação de suprimentos.</p>
    </div>
    <div class="box">
        <p>Escolha uma ONG abaixo para doar:</p>
        <?php if (empty($ongs)): ?>
            <p style="color:#555;">Nenhuma ONG disponível no momento.</p>
        <?php else: ?>
            <?php foreach ($ongs as $ong): ?>
                <div class="ong-card">
                    <strong><?= htmlspecialchars($ong['nome_receptor']) ?></strong>
                    <div><?= htmlspecialchars($labels_risco[$ong['classificacao_risco']] ?? $ong['classificacao_risco']) ?></div>
                    <a href="fazer_doacao.php?ong=<?= (int)$ong['id_beneficiario'] ?>">Registrar doação</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
