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
    1 => ['nome' => 'Banco de Alimentos PR', 'area' => 'Alimentação', 'cidade' => 'Curitiba, PR', 'descricao' => 'Distribui alimentos a famílias em insegurança alimentar no Paraná.'],
    2 => ['nome' => 'Abrigo Recomeço', 'area' => 'Moradia', 'cidade' => 'São José dos Pinhais, PR', 'descricao' => 'Acolhe vítimas de desastres com abrigo e apoio psicológico.'],
    3 => ['nome' => 'Saúde Solidária', 'area' => 'Saúde', 'cidade' => 'Curitiba, PR', 'descricao' => 'Leva medicamentos a comunidades sem acesso à saúde pública.'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ONGs — Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/home_usuario.css">
</head>
<body>

<nav>
    <span class="logo">🤝 Cruz Azul</span>
    <div>
        <a href="home_usuario.php">Início</a>
        <a href="doar.php">Fazer doação</a>
        <a href="servicos_publicos.php">Serviços Públicos</a>
        <a href="minhas_doacoes.php">Minhas doações</a>
        <a href="ongs.php">ONGs</a>
        <a href="logout.php">Sair</a>
    </div>
</nav>

<div class="conteudo">
    <div class="titulo-secao">ONGs Cadastradas</div>
    <p>Olá, <?= htmlspecialchars($primeiroNome) ?>! 👋 Conheça as ONGs que você pode ajudar.</p>
    
    <div class="grid-ongs">
        <?php foreach ($ongs as $id => $ong): ?>
            <div class="card-ong">
                <h3><?= htmlspecialchars($ong['nome']) ?></h3>
                <span class="area"><?= htmlspecialchars($ong['area']) ?></span>
                <p><?= htmlspecialchars($ong['descricao']) ?></p>
                <div class="cidade">📍 <?= htmlspecialchars($ong['cidade']) ?></div>
                <a href="doar.php?ong=<?= $id ?>" class="btn-doar">Doar agora</a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<footer>
    <p>© 2026 Cruz Azul — Plataforma de Doações Solidárias</p>
</footer>

</body>
</html>
