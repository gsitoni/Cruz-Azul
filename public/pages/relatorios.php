<?php
session_start();
if (!isset($_SESSION['ong'])) {
    header('Location: login_ong.php');
    exit;
}

$nome = $_SESSION['ong']['nome'] ?? $_SESSION['ong']['email'] ?? 'ONG';

// Dados fictícios de relatório
$relatorio = [
    'total_doacoes' => 18,
    'confirmadas' => 15,
    'pendentes' => 3,
    'total_peso' => '87kg',
    'periodo' => 'Março a Abril de 2026',
    'doacoes' => [
        ['item' => 'Arroz', 'quantidade' => '50kg', 'doador' => 'João Silva', 'data' => '05/04/2026', 'status' => 'Confirmado'],
        ['item' => 'Feijão', 'quantidade' => '30kg', 'doador' => 'Instituto Beneficente', 'data' => '04/04/2026', 'status' => 'Confirmado'],
        ['item' => 'Kit higiene', 'quantidade' => '20 kits', 'doador' => 'Maria Oliveira', 'data' => '04/04/2026', 'status' => 'Awaiting'],
        ['item' => 'Roupas', 'quantidade' => '50 peças', 'doador' => 'Pedro Costa', 'data' => '03/04/2026', 'status' => 'Confirmado'],
        ['item' => 'Medicamentos', 'quantidade' => '30 caixas', 'doador' => 'Farmácia Central', 'data' => '02/04/2026', 'status' => 'Confirmado'],
    ]
];
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
    <span class="logo">🤝 Cruz Azul</span>
    <div>
        <a href="home_ong.php">Início</a>
        <a href="doacoes_recebidas.php">Doações</a>
        <a href="relatorios.php">Relatório</a>
        <a href="perfil_ong.php">Perfil</a>
        <a href="logout.php">Sair</a>
    </div>
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
                <?php foreach ($relatorio['doacoes'] as $doacao): ?>
                <tr>
                    <td><?= htmlspecialchars($doacao['item']) ?></td>
                    <td><?= htmlspecialchars($doacao['quantidade']) ?></td>
                    <td><?= htmlspecialchars($doacao['doador']) ?></td>
                    <td><?= htmlspecialchars($doacao['data']) ?></td>
                    <td>
                        <?php if ($doacao['status'] === 'Confirmado'): ?>
                            <span class="status-confirmado">✓ Confirmado</span>
                        <?php else: ?>
                            <span class="status-pendente">⏳ Pendente</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
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
