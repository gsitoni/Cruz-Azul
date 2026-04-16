<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

require '../../src/api/database.php';

$primeiroNome = explode('@', $_SESSION['usuario']['email'])[0];

$stmt = $pdo->query(
    "SELECT id_ong, nome, localizacao, classificacao_risco
     FROM ong
     WHERE status_elegibilidade = 'ativo'
     ORDER BY nome ASC"
);
$ong = $stmt->fetchAll(PDO::FETCH_ASSOC);

$labels_risco = [
    'emergencia'       => 'Emergência',
    'continuo'         => 'Contínuo',
    'pontual'          => 'Pontual',
    'baixa_prioridade' => 'Baixa Prioridade',
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

<nav aria-label="breadcrumb" style="background:#e9ecef;border-bottom:1px solid #dee2e6;padding:8px 20px;font-size:13px;">
    <ol style="list-style:none;margin:0 auto;padding:0;display:flex;flex-wrap:wrap;align-items:center;max-width:900px;">
        <li><a href="home_usuario.php" style="color:#007BFF;text-decoration:none;">Início</a></li>
        <li><span style="margin:0 6px;color:#aaa;">›</span></li>
        <li style="color:#555;">ONGs</li>
    </ol>
</nav>

<div class="conteudo">
    <div class="titulo-secao">ONGs Cadastradas</div>
    <p>Olá, <?= htmlspecialchars($primeiroNome) ?>! 👋 Conheça as ONGs que você pode ajudar.</p>

    <?php if (empty($ong)): ?>
        <div class="card-ong" style="text-align:center;color:#555;">
            Nenhuma ONG cadastrada ainda.
        </div>
    <?php else: ?>
        <div class="grid-ongs">
            <?php foreach ($ong as $b): ?>
                <div class="card-ong">
                    <h3><?= htmlspecialchars($b['nome']) ?></h3>
                    <p style="font-weight:500;color:#333;margin-bottom:8px;">
                        <?= htmlspecialchars($labels_risco[$b['classificacao_risco']] ?? $b['classificacao_risco']) ?>
                    </p>
                    <div class="cidade">📍 <?= htmlspecialchars($b['localizacao']) ?></div>
                    <a href="fazer_doacao.php?ong=<?= (int)$b['id_ong'] ?>" class="btn-doar" style="display:inline-block;margin-top:14px;">
                        Doar agora
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<footer>
    <p>© 2026 Cruz Azul — Plataforma de Doações Solidárias</p>
</footer>

</body>
</html>
