<?php
session_start();
if (!isset($_SESSION['ong'])) {
    header('Location: login_ong.php');
    exit;
}

require '../../src/api/database.php';

function formatarNumeroRelatorio($valor)
{
    $numero = (float) $valor;

    if ((float) ((int) $numero) === $numero) {
        return number_format($numero, 0, ',', '.');
    }

    return number_format($numero, 1, ',', '.');
}

$ongId = (int) ($_SESSION['ong']['id'] ?? 0);
$nome = $_SESSION['ong']['nome'] ?? $_SESSION['ong']['email'] ?? 'ONG';

$relatorio = [
    'total_doacoes' => 0,
    'confirmadas' => 0,
    'pendentes' => 0,
    'total_peso' => '0',
    'periodo' => 'Sem movimentação registrada',
    'doacoes' => [],
];

if ($ongId > 0) {
    $stmt = $pdo->prepare('SELECT nome_receptor, email FROM beneficiario WHERE id_beneficiario = ?');
    $stmt->execute([$ongId]);
    $ong = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($ong) {
        $nome = $ong['nome_receptor'] ?: ($ong['email'] ?: $nome);
        $_SESSION['ong']['nome'] = $nome;
        $_SESSION['ong']['email'] = $ong['email'] ?? ($_SESSION['ong']['email'] ?? null);
    }

    $stmt = $pdo->prepare('
        SELECT
            COUNT(*) AS total_doacoes,
            SUM(CASE WHEN e.status_operacional = "disponivel" THEN 1 ELSE 0 END) AS confirmadas,
            SUM(CASE WHEN e.status_operacional <> "disponivel" THEN 1 ELSE 0 END) AS pendentes,
            COALESCE(SUM(di.quantidade_retirada), 0) AS total_quantidade,
            MIN(di.data_hora) AS primeira_data,
            MAX(di.data_hora) AS ultima_data
        FROM distribuicao di
        INNER JOIN estoque e ON e.id_lote = di.id_lote
        WHERE di.id_beneficiario = ?
    ');
    $stmt->execute([$ongId]);
    $resumo = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $relatorio['total_doacoes'] = (int) ($resumo['total_doacoes'] ?? 0);
    $relatorio['confirmadas'] = (int) ($resumo['confirmadas'] ?? 0);
    $relatorio['pendentes'] = (int) ($resumo['pendentes'] ?? 0);
    $relatorio['total_peso'] = formatarNumeroRelatorio($resumo['total_quantidade'] ?? 0);

    if (!empty($resumo['primeira_data']) && !empty($resumo['ultima_data'])) {
        $relatorio['periodo'] = date('d/m/Y', strtotime($resumo['primeira_data'])) . ' a ' . date('d/m/Y', strtotime($resumo['ultima_data']));
    }

    $stmt = $pdo->prepare('
        SELECT
            d.item,
            di.quantidade_retirada,
            d.unidade_medida,
            doador.nome AS doador,
            di.data_hora,
            e.status_operacional
        FROM distribuicao di
        INNER JOIN estoque e ON e.id_lote = di.id_lote
        INNER JOIN doacao d ON d.id_doacao = e.id_doacao
        LEFT JOIN doador ON doador.id_doador = d.id_doador
        WHERE di.id_beneficiario = ?
        ORDER BY di.data_hora DESC
        LIMIT 10
    ');
    $stmt->execute([$ongId]);
    $relatorio['doacoes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Doações — Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/home_ong.css">
    
</head>
<body>

<nav>
    <a href="home_ong.php" class="logo" style="text-decoration:none;color:#fff;">🤝 Cruz Azul</a>
    <div>
        <a href="home_ong.php">Início</a>
        <a href="doacoes_recebidas.php">Doações</a>
        <a href="relatorios.php">Relatório</a>
        <a href="perfil_ong.php">Perfil</a>
        <a href="logout.php">Sair</a>
    </div>
</nav>

<nav aria-label="breadcrumb" style="background:#e9ecef;border-bottom:1px solid #dee2e6;padding:8px 20px;font-size:13px;">
    <ol style="list-style:none;margin:0 auto;padding:0;display:flex;flex-wrap:wrap;align-items:center;max-width:900px;">
        <li><a href="home_ong.php" style="color:#007BFF;text-decoration:none;">Início</a></li>
        <li><span style="margin:0 6px;color:#aaa;">›</span></li>
        <li style="color:#555;">Relatórios</li>
    </ol>
</nav>

<div class="conteudo">
    <div class="relatorio-header">
        <h1>📊 Relatório de Doações</h1>
        <p><?= htmlspecialchars($nome) ?></p>
        <small>Período: <?= htmlspecialchars($relatorio['periodo']) ?></small>
    </div>

    <div class="relatorio-stats">
        <div class="stat-card">
            <h4>Total de Doações</h4>
            <div class="numero"><?= $relatorio['total_doacoes'] ?></div>
        </div>
        <div class="stat-card">
            <h4>Confirmadas</h4>
            <div class="numero" style="color: #28a745;"><?= $relatorio['confirmadas'] ?></div>
        </div>
        <div class="stat-card">
            <h4>Aguardando Confirmação</h4>
            <div class="numero" style="color: #e07820;"><?= $relatorio['pendentes'] ?></div>
        </div>
        <div class="stat-card">
            <h4>Total Recebido</h4>
            <div class="numero"><?= htmlspecialchars($relatorio['total_peso']) ?></div>
        </div>
    </div>

    <div class="titulo-secao">Histórico de Doações</div>
    <div class="tabela-doacoes">
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantidade</th>
                    <th>Doador</th>
                    <th>Data</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($relatorio['doacoes'])): ?>
                <tr>
                    <td colspan="5">Nenhuma distribuição foi registrada para esta ONG até o momento.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($relatorio['doacoes'] as $doacao): ?>
                <tr>
                    <td><?= htmlspecialchars($doacao['item']) ?></td>
                    <td><?= htmlspecialchars(formatarNumeroRelatorio($doacao['quantidade_retirada'])) . ' ' . htmlspecialchars($doacao['unidade_medida']) ?></td>
                    <td><?= htmlspecialchars($doacao['doador'] ?: 'Doador não identificado') ?></td>
                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($doacao['data_hora']))) ?></td>
                    <td>
                        <?php if ($doacao['status_operacional'] === 'disponivel'): ?>
                            <span class="status-confirmado">✓ Confirmado</span>
                        <?php else: ?>
                            <span class="status-pendente">⏳ Pendente</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 30px; text-align: center;">
        <a href="home_ong.php" style="display: inline-block; background: #666; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 6px;">← Voltar</a>
    </div>
</div>

<footer>
    &copy; <?= date('Y') ?> Cruz Azul — Sistema de Suprimentos
</footer>

</body>
</html>
