<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$nome    = $_SESSION['usuario']['nome'] ?? $_SESSION['usuario']['email'] ?? 'Usuário';// Extrair primeiro nome
if (strpos($nome, '@') !== false) {
    $primeiroNome = explode('@', $nome)[0];
} else {
    $primeiroNome = explode(' ', $nome)[0];
}$inicial = strtoupper(mb_substr($nome, 0, 1));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Início — Doador</title>
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

<div class="topo">
    <h1>Olá, <?= htmlspecialchars($primeiroNome) ?>! 👋</h1>
    <p>Escolha uma ONG e faça sua doação de suprimentos.</p>
</div>

<div class="conteudo">

    <div class="titulo-secao">O que você quer fazer?</div>
    <div class="grid-acoes">
        <a href="doar.php" class="card-acao">
            <div class="icone">📦</div>
            <h3>Fazer uma doação</h3>
            <p>Registre sua doação para uma ONG.</p>
        </a>
        <a href="servicos_publicos.php" class="card-acao">
            <div class="icone">🏥</div>
            <h3>Serviços Públicos</h3>
            <p>Encontre hospitais, postos de saúde e alimentação próxima.</p>
        </a>
        <a href="ongs.php" class="card-acao">
            <div class="icone">🏢</div>
            <h3>Ver ONGs</h3>
            <p>Encontre organizações próximas.</p>
        </a>
        <a href="minhas_doacoes.php" class="card-acao">
            <div class="icone">📋</div>
            <h3>Minhas doações</h3>
            <p>Veja o histórico das suas doações.</p>
        </a>
        <a href="perfil.php" class="card-acao">
            <div class="icone">👤</div>
            <h3>Meu perfil</h3>
            <p>Edite seus dados cadastrais.</p>
        </a>
    </div>

    <div class="titulo-secao">🏥 Serviços Úteis para a População</div>
    <div class="grid-acoes">
        <a href="servicos_publicos.php?categoria=hospitais" class="card-acao">
            <div class="icone">🏥</div>
            <h3>Hospitais Próximos</h3>
            <p>Encontre unidades de saúde de emergência e atendimento.</p>
        </a>
        <a href="servicos_publicos.php?categoria=postos_saude" class="card-acao">
            <div class="icone">⚕️</div>
            <h3>Postos de Saúde</h3>
            <p>Unidades básicas de saúde e centros de atendimento.</p>
        </a>
        <a href="servicos_publicos.php?categoria=alimentacao" class="card-acao">
            <div class="icone">🍽️</div>
            <h3>Alimentação</h3>
            <p>Bancos de alimentos, cozinhas comunitárias e refeições.</p>
        </a>
        <a href="servicos_publicos.php?categoria=abrigos" class="card-acao">
            <div class="icone">🏠</div>
            <h3>Abrigos</h3>
            <p>Locais de acolhimento para pessoas em situação de rua.</p>
        </a>
        <a href="servicos_publicos.php?categoria=assistencia_social" class="card-acao">
            <div class="icone">🤝</div>
            <h3>Assistência Social</h3>
            <p>Centros de referência e programas sociais.</p>
        </a>
        <a href="servicos_publicos.php?categoria=emergencia" class="card-acao">
            <div class="icone">🚨</div>
            <h3>Emergências</h3>
            <p>Telefones de emergência e serviços de urgência.</p>
        </a>
    </div>

    <div class="titulo-secao">ONGs que precisam de ajuda agora</div>
    <div class="grid-ongs">
        <div class="card-ong">
            <h3>Banco de Alimentos PR</h3>
            <span class="area">Alimentação</span>
            <p>Distribui alimentos a famílias em insegurança alimentar no Paraná.</p>
            <div class="cidade">📍 Curitiba, PR</div>
            <a href="doar.php?ong=1" class="btn-doar">Doar agora</a>
        </div>
        <div class="card-ong">
            <h3>Abrigo Recomeço</h3>
            <span class="area">Moradia</span>
            <p>Acolhe vítimas de desastres com abrigo e apoio psicológico.</p>
            <div class="cidade">📍 São José dos Pinhais, PR</div>
            <a href="doar.php?ong=2" class="btn-doar">Doar agora</a>
        </div>
        <div class="card-ong">
            <h3>Saúde Solidária</h3>
            <span class="area">Saúde</span>
            <p>Leva medicamentos a comunidades sem acesso à saúde pública.</p>
            <div class="cidade">📍 Curitiba, PR</div>
            <a href="doar.php?ong=3" class="btn-doar">Doar agora</a>
        </div>
    </div>

    <div class="titulo-secao">Suas últimas doações</div>
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>ONG</th>
                <th>Data</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Alimentos não perecíveis (5kg)</td>
                <td>Banco de Alimentos PR</td>
                <td>28/03/2026</td>
                <td><span class="badge badge-entregue">Entregue</span></td>
            </tr>
            <tr>
                <td>Kit de higiene pessoal</td>
                <td>Abrigo Recomeço</td>
                <td>15/03/2026</td>
                <td><span class="badge badge-andamento">Em andamento</span></td>
            </tr>
            <tr>
                <td>Roupas de inverno (7 peças)</td>
                <td>Abrigo Recomeço</td>
                <td>01/03/2026</td>
                <td><span class="badge badge-entregue">Entregue</span></td>
            </tr>
        </tbody>
    </table>

</div>

<footer>
    &copy; <?= date('Y') ?> Cruz Azul — Sistema de Suprimentos
</footer>

</body>
</html>
