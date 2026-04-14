<?php
session_start();
if (!isset($_SESSION['ong'])) {
    header('Location: login_ong.php');
    exit;
}
require '../../src/api/database.php';

function formatarNumeroRecebido($valor)
{
    $numero = (float) $valor;

    if ((float) ((int) $numero) === $numero) {
        return number_format($numero, 0, ',', '.');
    }

    return number_format($numero, 1, ',', '.');
}

$ongId = (int) ($_SESSION['ong']['id'] ?? 0);
$nome = $_SESSION['ong']['nome'] ?? $_SESSION['ong']['email'] ?? 'ONG';

if ($ongId > 0) {
    $stmt = $pdo->prepare('SELECT nome_receptor, email FROM beneficiario WHERE id_beneficiario = ?');
    $stmt->execute([$ongId]);
    $ong = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($ong) {
        $nome = $ong['nome_receptor'] ?: ($ong['email'] ?: $nome);
        $_SESSION['ong']['nome'] = $nome;
        $_SESSION['ong']['email'] = $ong['email'] ?? ($_SESSION['ong']['email'] ?? null);
    }
}

// Extrair primeiro nome
if (strpos($nome, '@') !== false) {
    $primeiroNome = explode('@', $nome)[0];
} else {
    $primeiroNome = explode(' ', $nome)[0];
}

$doacoes = [];

if ($ongId > 0) {
    $stmt = $pdo->prepare('
        SELECT
            d.item,
            di.quantidade_retirada,
            d.unidade_medida,
            doador.nome AS remetente,
            di.data_hora,
            e.status_operacional
        FROM distribuicao di
        INNER JOIN estoque e ON e.id_lote = di.id_lote
        INNER JOIN doacao d ON d.id_doacao = e.id_doacao
        LEFT JOIN doador ON doador.id_doador = d.id_doador
        WHERE di.id_beneficiario = ?
        ORDER BY di.data_hora DESC
    ');
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
                    <strong>Nenhuma doação encontrada</strong>
                    <div>As distribuições registradas para sua ONG aparecerão aqui.</div>
                </div>
                <div class="status status-aguardando">Sem dados</div>
            </div>
        <?php else: ?>
        <?php foreach ($doacoes as $doacao): ?>
            <div class="item">
                <div>
                    <strong><?php echo htmlspecialchars($doacao['item']); ?></strong>
                    <div>Remetente: <?php echo htmlspecialchars($doacao['remetente'] ?: 'Doador não identificado'); ?></div>
                    <div>Quantidade: <?php echo htmlspecialchars(formatarNumeroRecebido($doacao['quantidade_retirada'])); ?> <?php echo htmlspecialchars($doacao['unidade_medida']); ?></div>
                    <div>Data: <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($doacao['data_hora']))); ?></div>
                </div>
                <?php $status = $doacao['status_operacional'] === 'disponivel' ? 'Registrado' : ucfirst((string) $doacao['status_operacional']); ?>
                <div class="status <?php echo $doacao['status_operacional'] === 'disponivel' ? 'status-confirmado' : 'status-aguardando'; ?>">
                    <?php echo htmlspecialchars($status); ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
