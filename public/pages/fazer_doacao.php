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
if (!$ongSelecionada) {
    header('Location: doar.php');
    exit;
}
$sucesso = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = trim($_POST['tipo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $quantidade = trim($_POST['quantidade'] ?? '');
    if ($tipo && $descricao && $quantidade) {
        $sucesso = 'Doação registrada com sucesso! Obrigado por ajudar.';
        // Aqui poderia salvar em banco ou sessão
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Doação — Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/fazer_doacao.css">
</head>
<body>
<nav>
    <span class="logo">🤝 Cruz Azul</span>
    <div>
        <a href="home_usuario.php">Início</a>
        <a href="doar.php">Voltar</a>
        <a href="logout.php">Sair</a>
    </div>
</nav>
<div class="container">
    <div class="header">
        <h1>Registrar Doação</h1>
        <p>Olá, <?php echo htmlspecialchars($primeiroNome); ?>! 👋 Escolha uma ONG e faça sua doação de suprimentos.</p>
    </div>
    <div class="box">
        <?php if ($sucesso): ?>
            <div class="alert"><?php echo htmlspecialchars($sucesso); ?></div>
        <?php endif; ?>
        <form method="post" action="fazer_doacao.php?ong=<?php echo $ongId; ?>">
            <div class="form-row">
                <label for="tipo">Tipo de doação</label>
                <select id="tipo" name="tipo" required>
                    <option value="">Selecione</option>
                    <option value="alimentos">Alimentos</option>
                    <option value="itens">Itens (roupas, higiene, etc.)</option>
                    <option value="medicamentos">Medicamentos</option>
                    <option value="outros">Outros</option>
                </select>
            </div>
            <div class="form-row">
                <label for="descricao">Descrição</label>
                <textarea id="descricao" name="descricao" rows="4" placeholder="Descreva os itens que está doando (ex: arroz, feijão, roupas de inverno)" required></textarea>
            </div>
            <div class="form-row">
                <label for="quantidade">Quantidade ou detalhes</label>
                <input type="text" id="quantidade" name="quantidade" placeholder="Ex: 10 kg de arroz, 5 cestas básicas" required>
            </div>
            <button type="submit">Registrar Doação</button>
        </form>
    </div>
</div>
</body>
</html>