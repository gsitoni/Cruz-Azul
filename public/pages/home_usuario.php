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
$inicial = strtoupper(mb_substr($nome, 0, 1));

$stmtEmerg = $pdo->query(
    "SELECT id_beneficiario, nome_receptor, localizacao
     FROM beneficiario
     WHERE classificacao_risco = 'emergencia' AND status_elegibilidade = 'ativo'
     ORDER BY nome_receptor ASC"
);
$ongsEmergencia = $stmtEmerg->fetchAll(PDO::FETCH_ASSOC);

$userEmail = $_SESSION['usuario']['email'];
$stmtDoacoes = $pdo->prepare(
    "SELECT d.item, d.data_doacao, b.nome_receptor,
            CASE WHEN dist.id_operacao IS NOT NULL THEN 'entregue'
                 WHEN e.id_lote IS NOT NULL THEN 'andamento'
                 ELSE 'pendente' END AS status_doacao
     FROM doacao d
     INNER JOIN doador dr ON dr.id_doador = d.id_doador
     LEFT JOIN estoque e ON e.id_doacao = d.id_doacao
     LEFT JOIN distribuicao dist ON dist.id_lote = e.id_lote
     LEFT JOIN beneficiario b ON b.id_beneficiario = dist.id_beneficiario
     WHERE dr.email = ?
     ORDER BY d.data_doacao DESC, d.criado_em DESC
     LIMIT 5"
);
$stmtDoacoes->execute([$userEmail]);
$ultimasDoacoes = $stmtDoacoes->fetchAll(PDO::FETCH_ASSOC);
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
    <a href="home_usuario.php" class="logo" style="text-decoration:none;color:#fff;">🤝 Cruz Azul</a>
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
            <p>Encontre hospitais e serviços de emergência próximos.</p>
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
        <a href="servicos_publicos.php?categoria=emergencia" class="card-acao">
            <div class="icone">🚨</div>
            <h3>Emergências</h3>
            <p>Telefones de emergência e serviços de urgência.</p>
        </a>
    </div>

    <div class="titulo-secao">🚨 ONGs que precisam de ajuda agora</div>
    <?php if (empty($ongsEmergencia)): ?>
        <p style="color:#555;font-size:14px;margin-bottom:24px;">Nenhuma ONG em situação de emergência no momento.</p>
    <?php else: ?>
        <div class="grid-ongs">
            <?php foreach ($ongsEmergencia as $ong): ?>
                <div class="card-ong">
                    <h3><?= htmlspecialchars($ong['nome_receptor']) ?></h3>
                    <span class="area" style="display:inline-block;margin-bottom:8px;font-size:12px;background:#fdecea;color:#b71c1c;padding:3px 10px;border-radius:999px;">Emergência</span>
                    <div class="cidade">📍 <?= htmlspecialchars($ong['localizacao']) ?></div>
                    <a href="fazer_doacao.php?ong=<?= (int)$ong['id_beneficiario'] ?>" class="btn-doar" style="display:inline-block;margin-top:14px;">Doar agora</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="titulo-secao">Suas últimas doações</div>
    <?php if (empty($ultimasDoacoes)): ?>
        <p style="color:#555;font-size:14px;margin-bottom:24px;">Nenhuma doação registrada ainda. <a href="ongs.php">Faça sua primeira doação!</a></p>
    <?php else: ?>
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
                <?php foreach ($ultimasDoacoes as $d):
                    $badgeClass = $d['status_doacao'] === 'entregue' ? 'badge-entregue' : 'badge-andamento';
                    $badgeLabel = match($d['status_doacao']) {
                        'entregue'  => 'Entregue',
                        'andamento' => 'Em andamento',
                        default     => 'Pendente',
                    };
                ?>
                    <tr>
                        <td><?= htmlspecialchars($d['item']) ?></td>
                        <td><?= $d['nome_receptor'] ? htmlspecialchars($d['nome_receptor']) : '<span style="color:#aaa;">—</span>' ?></td>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($d['data_doacao']))) ?></td>
                        <td><span class="badge <?= $badgeClass ?>"><?= $badgeLabel ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</div>

<footer>
    &copy; <?= date('Y') ?> Cruz Azul — Sistema de Suprimentos
</footer>

</body>
</html>
