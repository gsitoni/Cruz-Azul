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
$ongs = [
    1 => ['nome' => 'Abrigo Esperança', 'area' => 'Alimentos e Acolhimento'],
    2 => ['nome' => 'Casa do Bem', 'area' => 'Saúde e Emergência'],
    3 => ['nome' => 'Rede Solidária', 'area' => 'Doações e Voluntariado'],
];
$ongId = intval($_GET['ong'] ?? 0);
$ongSelecionada = $ongs[$ongId] ?? null;
if ($ongSelecionada) {
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
    <span class="logo">🤝 Cruz Azul</span>
    <div>
        <a href="home_usuario.php">Início</a>
        <a href="servicos_publicos.php">Serviços Públicos</a>
        <a href="minhas_doacoes.php">Minhas doações</a>
        <a href="ongs.php">ONGs</a>
        <a href="logout.php">Sair</a>
    </div>
</nav>
<div class="container">
    <div class="header">
        <h1>Fazer doação</h1>
        <p>Olá, <?php echo htmlspecialchars($primeiroNome); ?>! 👋 Escolha uma ONG e faça sua doação de suprimentos.</p>
    </div>
    <div class="box">
        <p>Escolha uma ONG abaixo para doar:</p>
        <?php foreach ($ongs as $id => $ong): ?>
            <div class="ong-card">
                <strong><?php echo htmlspecialchars($ong['nome']); ?></strong>
                <div><?php echo htmlspecialchars($ong['area']); ?></div>
                <a href="fazer_doacao.php?ong=<?php echo $id; ?>">Registrar doação</a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
