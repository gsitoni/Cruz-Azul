<?php
session_start();
if (!isset($_SESSION['ong'])) {
    header('Location: login_ong.php');
    exit;
}

require '../../src/api/database.php';

function formatarNumeroDoacao($valor)
{
    $numero = (float) $valor;

    if ((float) ((int) $numero) === $numero) {
        return number_format($numero, 0, ',', '.');
    }

    return number_format($numero, 1, ',', '.');
}

function formatarDataDoacao(?string $valor, string $formato = 'd/m/Y')
{
    if (empty($valor)) {
        return 'Sem data';
    }

    return date($formato, strtotime($valor));
}

$nome = $_SESSION['ong']['nome'] ?? $_SESSION['ong']['email'] ?? 'ONG';
// Extrair primeiro nome
if (strpos($nome, '@') !== false) {
    $primeiroNome = explode('@', $nome)[0];
} else {
    $primeiroNome = explode(' ', $nome)[0];
}

$ongId = (int) ($_SESSION['ong']['id'] ?? 0);
$doacoes = [];

if ($ongId > 0) {
    $stmt = $pdo->prepare(
        'SELECT
            d.item,
            d.categoria,
            di.quantidade_retirada,
            d.unidade_medida,
            doador.nome AS remetente,
            di.data_hora,
            e.codigo_lote
         FROM distribuicao di
         INNER JOIN estoque e ON e.id_lote = di.id_lote
         INNER JOIN doacao d ON d.id_doacao = e.id_doacao
         LEFT JOIN doador ON doador.id_doador = d.id_doador
         WHERE di.id_beneficiario = ?
         ORDER BY di.data_hora DESC'
    );
    $stmt->execute([$ongId]);
    $doacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
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
        <?php if (empty($doacoes)): ?>
            <div class="item">
                <div>
                    <strong>Nenhuma doação recebida ainda.</strong>
                    <div>As doações distribuídas para esta ONG aparecerão aqui.</div>
                </div>
                <div class="status status-aguardando">Sem registros</div>
            </div>
        <?php else: ?>
            <?php foreach ($doacoes as $doacao): ?>
                <div class="item">
                    <div>
                        <strong><?php echo htmlspecialchars((string) $doacao['item']); ?></strong>
                        <div>Categoria: <?php echo htmlspecialchars(ucfirst((string) $doacao['categoria'])); ?></div>
                        <div>Quantidade: <?php echo htmlspecialchars(formatarNumeroDoacao($doacao['quantidade_retirada'])); ?> <?php echo htmlspecialchars((string) $doacao['unidade_medida']); ?></div>
                        <div>Remetente: <?php echo htmlspecialchars((string) ($doacao['remetente'] ?: 'Doador não identificado')); ?></div>
                        <div>Lote: <?php echo htmlspecialchars((string) $doacao['codigo_lote']); ?></div>
                        <div>Recebido em: <?php echo htmlspecialchars(formatarDataDoacao($doacao['data_hora'], 'd/m/Y H:i')); ?></div>
                    </div>
                    <div class="status status-confirmado">Recebido</div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
