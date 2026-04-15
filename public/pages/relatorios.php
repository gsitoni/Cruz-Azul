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

function formatarDataRelatorio(?string $valor, string $formato = 'd/m/Y')
{
    if (empty($valor)) {
        return 'Sem registros';
    }

    return date($formato, strtotime($valor));
}

// Variáveis para exibição
$ongId = (int) ($_SESSION['ong']['id'] ?? 0);
$nome = $_SESSION['ong']['nome'] ?? $_SESSION['ong']['email'] ?? 'ONG';
$relatorio = [
    'total_doacoes' => 0,
    'total_lotes' => 0,
    'doadores_unicos' => 0,
    'categorias_ativas' => 0,
    'periodo' => 'Sem movimentação registrada',
    'doacoes' => [],
];

if ($ongId > 0) {
    $stmt = $pdo->prepare(
        'SELECT
            COUNT(*) AS total_doacoes,
            COUNT(DISTINCT di.id_lote) AS total_lotes,
            COUNT(DISTINCT d.id_doador) AS doadores_unicos,
            COUNT(DISTINCT d.categoria) AS categorias_ativas,
            MIN(di.data_hora) AS primeira_entrada,
            MAX(di.data_hora) AS ultima_entrada
         FROM distribuicao di
         INNER JOIN estoque e ON e.id_lote = di.id_lote
         INNER JOIN doacao d ON d.id_doacao = e.id_doacao
         WHERE di.id_beneficiario = ?'
    );
    $stmt->execute([$ongId]);
    $resumo = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    if (!empty($resumo)) {
        $relatorio['total_doacoes'] = (int) ($resumo['total_doacoes'] ?? 0);
        $relatorio['total_lotes'] = (int) ($resumo['total_lotes'] ?? 0);
        $relatorio['doadores_unicos'] = (int) ($resumo['doadores_unicos'] ?? 0);
        $relatorio['categorias_ativas'] = (int) ($resumo['categorias_ativas'] ?? 0);

        if (!empty($resumo['primeira_entrada']) && !empty($resumo['ultima_entrada'])) {
            $relatorio['periodo'] = formatarDataRelatorio($resumo['primeira_entrada']) . ' a ' . formatarDataRelatorio($resumo['ultima_entrada']);
        }
    }

    $stmt = $pdo->prepare(
        'SELECT
            d.item,
            di.quantidade_retirada,
            d.unidade_medida,
            doador.nome AS doador,
            di.data_hora,
            d.categoria
         FROM distribuicao di
         INNER JOIN estoque e ON e.id_lote = di.id_lote
         INNER JOIN doacao d ON d.id_doacao = e.id_doacao
         LEFT JOIN doador ON doador.id_doador = d.id_doador
         WHERE di.id_beneficiario = ?
         ORDER BY di.data_hora DESC'
    );
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
    <style>
        .relatorio-header {
            background: #007BFF;
            color: #fff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .relatorio-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #fff;
            border-left: 4px solid #007BFF;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .stat-card h4 {
            color: #666;
            font-size: 14px;
            margin-bottom: 8px;
        }
        .stat-card .numero {
            font-size: 28px;
            color: #007BFF;
            font-weight: bold;
        }
        .tabela-doacoes {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .tabela-doacoes table {
            width: 100%;
            border-collapse: collapse;
        }
        .tabela-doacoes th {
            background: #007BFF;
            color: #fff;
            padding: 14px 16px;
            text-align: left;
            font-weight: bold;
        }
        .tabela-doacoes td {
            padding: 14px 16px;
            border-bottom: 1px solid #eee;
        }
        .tabela-doacoes tr:last-child td {
            border-bottom: none;
        }
        .status-confirmado {
            background: #e7f3ff;
            color: #007BFF;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pendente {
            background: #fff3e0;
            color: #e07820;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
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
            <h4>Total de Registros</h4>
            <div class="numero"><?= $relatorio['total_doacoes'] ?></div>
        </div>
        <div class="stat-card">
            <h4>Lotes Recebidos</h4>
            <div class="numero" style="color: #28a745;"><?= $relatorio['total_lotes'] ?></div>
        </div>
        <div class="stat-card">
            <h4>Doadores Únicos</h4>
            <div class="numero" style="color: #e07820;"><?= $relatorio['doadores_unicos'] ?></div>
        </div>
        <div class="stat-card">
            <h4>Categorias Ativas</h4>
            <div class="numero"><?= $relatorio['categorias_ativas'] ?></div>
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
                        <td colspan="5">Nenhuma doação recebida foi registrada para esta ONG até o momento.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($relatorio['doacoes'] as $doacao): ?>
                    <tr>
                        <td><?= htmlspecialchars($doacao['item']) ?></td>
                        <td><?= htmlspecialchars(formatarNumeroRelatorio($doacao['quantidade_retirada'])) . ' ' . htmlspecialchars($doacao['unidade_medida']) ?></td>
                        <td><?= htmlspecialchars($doacao['doador'] ?: 'Doador não identificado') ?></td>
                        <td><?= htmlspecialchars(formatarDataRelatorio($doacao['data_hora'], 'd/m/Y H:i')) ?></td>
                        <td>
                            <span class="status-confirmado">✓ Recebido</span>
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
