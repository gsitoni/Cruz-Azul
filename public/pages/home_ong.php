<?php
session_start();
if (!isset($_SESSION['ong'])) {
    header('Location: login_ong.php');
    exit;
}

$nome   = $_SESSION['ong']['nome'] ?? $_SESSION['ong']['email'] ?? 'ONG';
$area   = $_SESSION['ong']['area_atuacao'] ?? 'Assistência social';
$status = $_SESSION['ong']['status'] ?? 'ativo';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel ONG — Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/home_ong.css">
</head>
<body>

<nav>
    <span class="logo">🤝 Cruz Azul</span>
    <div>
        <a href="doacoes_recebidas.php">Doações</a>
        <a href="perfil_ong.php">Perfil</a>
        <a href="logout.php">Sair</a>
    </div>
</nav>

<?php if ($status === 'pendente'): ?>
<div class="aviso">
    ⏳ Sua ONG está <strong>aguardando validação</strong> do administrador.
</div>
<?php endif; ?>

<div class="topo">
    <h1><?= htmlspecialchars($nome) ?></h1>
    <div class="area-tag">📌 <?= htmlspecialchars($area) ?></div>
    <p>Gerencie as doações recebidas e atualize as necessidades da sua ONG.</p>
</div>

<div class="barra-stats">
    <div class="stat">
        <div class="numero">18</div>
        <div class="label">Doações recebidas</div>
    </div>
    <div class="stat">
        <div class="numero">3</div>
        <div class="label">Aguardando confirmação</div>
    </div>
    <div class="stat">
        <div class="numero">124</div>
        <div class="label">Pessoas atendidas</div>
    </div>
    <div class="stat">
        <div class="numero">87kg</div>
        <div class="label">Suprimentos recebidos</div>
    </div>
</div>

<div class="conteudo">

    <div class="titulo-secao">O que você quer fazer?</div>
    <div class="grid-acoes">
        <a href="doacoes_recebidas.php" class="card-acao">
            <div class="icone">📦</div>
            <h3>Ver doações</h3>
            <p>Confirme o recebimento das doações.</p>
        </a>
        <a href="relatorios.php" class="card-acao">
            <div class="icone">📊</div>
            <h3>Relatórios</h3>
            <p>Veja o histórico de impacto.</p>
        </a>
        <a href="perfil_ong.php" class="card-acao">
            <div class="icone">✏️</div>
            <h3>Editar perfil</h3>
            <p>Atualize os dados da ONG.</p>
        </a>
    </div>

    <div class="titulo-secao">Doações aguardando confirmação</div>
    <div class="lista-doacoes">
        <div class="doacao-item">
            <div class="doacao-info">
                <h4>🍚 Arroz e feijão — João Silva</h4>
                <span>8kg · Enviado em 05/04/2026</span>
            </div>
            <div class="doacao-botoes">
                <button class="btn btn-aceitar">✓ Aceitar</button>
                <button class="btn btn-recusar">✗ Recusar</button>
            </div>
        </div>
        <div class="doacao-item">
            <div class="doacao-info">
                <h4>🧴 Kit higiene — Maria Oliveira</h4>
                <span>3 kits · Enviado em 04/04/2026</span>
            </div>
            <div class="doacao-botoes">
                <button class="btn btn-aceitar">✓ Aceitar</button>
                <button class="btn btn-recusar">✗ Recusar</button>
            </div>
        </div>
        <div class="doacao-item">
            <div class="doacao-info">
                <h4>👕 Roupas de inverno — Pedro Costa</h4>
                <span>12 peças · Enviado em 03/04/2026</span>
            </div>
            <div class="doacao-botoes">
                <button class="btn btn-aceitar">✓ Aceitar</button>
                <button class="btn btn-recusar">✗ Recusar</button>
            </div>
        </div>
    </div>

    <div class="titulo-secao">Necessidades atuais</div>
    <div class="grid-nec">
        <div class="card-nec">
            <h4>🍚 Alimentos</h4>
            <div class="qtd">12 / 50 kg recebidos</div>
            <div class="barra-fundo">
                <div class="barra-fill urgente" style="width: 24%"></div>
            </div>
        </div>
        <div class="card-nec">
            <h4>🧴 Higiene</h4>
            <div class="qtd">28 / 40 kits recebidos</div>
            <div class="barra-fundo">
                <div class="barra-fill" style="width: 70%"></div>
            </div>
        </div>
        <div class="card-nec">
            <h4>👕 Roupas</h4>
            <div class="qtd">45 / 50 peças recebidas</div>
            <div class="barra-fundo">
                <div class="barra-fill ok" style="width: 90%"></div>
            </div>
        </div>
        <div class="card-nec">
            <h4>💊 Remédios</h4>
            <div class="qtd">5 / 30 unidades recebidas</div>
            <div class="barra-fundo">
                <div class="barra-fill urgente" style="width: 17%"></div>
            </div>
        </div>
    </div>

</div>

<footer>
    &copy; <?= date('Y') ?> Cruz Azul — Sistema de Suprimentos
</footer>

</body>
</html>
