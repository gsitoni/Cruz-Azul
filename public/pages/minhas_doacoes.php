<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

require '../../src/api/database.php';

$userEmail    = $_SESSION['usuario']['email'];
$primeiroNome = explode('@', $userEmail)[0];

// Busca doações do usuário logado via e-mail do doador
$stmt = $pdo->prepare(
    "SELECT d.data_doacao, d.categoria, d.item, d.quantidade, d.unidade_medida,
            b.nome
     FROM doacao d
     INNER JOIN doador dr ON dr.id_doador = d.id_doador
     INNER JOIN usuario u ON u.id_usuario = dr.id_usuario
     LEFT JOIN estoque e ON e.id_doacao = d.id_doacao
     LEFT JOIN distribuicao dist ON dist.id_lote = e.id_lote
     LEFT JOIN ong b ON b.id_ong = dist.id_ong
     WHERE u.email = ?
     ORDER BY d.data_doacao DESC, d.criado_em DESC"
);
$stmt->execute([$userEmail]);
$doacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Doações — Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/minhas_doacoes.css">
</head>
<body>
<nav>
    <a href="home_usuario.php" class="logo" style="text-decoration:none;color:#fff;">🤝 Cruz Azul</a>
    <div>
        <a href="home_usuario.php">Início</a>
        <a href="doar.php">Fazer doação</a>
        <a href="servicos_publicos.php">Serviços Públicos</a>
        <a href="ongs.php">ONGs</a>
        <a href="logout.php">Sair</a>
    </div>
</nav>

<nav aria-label="breadcrumb" style="background:#e9ecef;border-bottom:1px solid #dee2e6;padding:8px 20px;font-size:13px;">
    <ol style="list-style:none;margin:0 auto;padding:0;display:flex;flex-wrap:wrap;align-items:center;max-width:900px;">
        <li><a href="home_usuario.php" style="color:#007BFF;text-decoration:none;">Início</a></li>
        <li><span style="margin:0 6px;color:#aaa;">›</span></li>
        <li style="color:#555;">Minhas Doações</li>
    </ol>
</nav>

<div class="container">
    <div class="header">
        <h1>Minhas doações</h1>
        <p>Olá, <?php echo htmlspecialchars($primeiroNome); ?>! 👋 Veja o histórico das suas contribuições.</p>
    </div>
    <?php if (empty($doacoes)): ?>
        <div class="box">Nenhuma doação registrada ainda. <a href="doar.php">Faça sua primeira doação!</a></div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Categoria</th>
                    <th>Item</th>
                    <th>Quantidade</th>
                    <th>ONG Beneficiária</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($doacoes as $doacao): ?>
                    <tr>
                        <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($doacao['data_doacao']))); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($doacao['categoria'])); ?></td>
                        <td><?php echo htmlspecialchars($doacao['item']); ?></td>
                        <td><?php echo htmlspecialchars($doacao['quantidade'] + 0); ?> <?php echo htmlspecialchars($doacao['unidade_medida']); ?></td>
                        <td><?php echo $doacao['nome'] ? htmlspecialchars($doacao['nome']) : '<span style="color:#aaa;">Aguardando distribuição</span>'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
