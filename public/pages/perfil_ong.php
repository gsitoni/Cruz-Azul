<?php
session_start();
// Verifica se a ONG está logada
if (!isset($_SESSION['ong'])) {
    header('Location: login_ong.php');
    exit;
}

require '../../src/api/database.php';

$ongId = (int) ($_SESSION['ong']['id'] ?? 0);

$stmt = $pdo->prepare('
    SELECT nome_receptor, email, area_atuacao, localizacao, cidade, sigla_estado, endereco, descricao, status_elegibilidade
    FROM beneficiario
    WHERE id_beneficiario = ?
');
$stmt->execute([$ongId]);
$ong = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ong) {
    header('Location: logout.php');
    exit;
}

$_SESSION['ong']['nome'] = $ong['nome_receptor'];
$_SESSION['ong']['email'] = $ong['email'];
$_SESSION['ong']['area_atuacao'] = $ong['area_atuacao'];
$_SESSION['ong']['status'] = $ong['status_elegibilidade'];
$_SESSION['ong']['cidade'] = $ong['cidade'];
$_SESSION['ong']['estado'] = $ong['sigla_estado'];

$localizacao = trim(implode(' / ', array_filter([$ong['cidade'], $ong['sigla_estado']])));
$status = [
    'pendente' => 'Pendente',
    'aprovada' => 'Aprovada',
    'rejeitada' => 'Rejeitada',
][$ong['status_elegibilidade'] ?? ''] ?? 'Não informado';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil da ONG - Cruz Azul</title>
</head>
<body>
<div style="max-width: 800px; margin: 18px auto; padding: 0 16px; font-family: Arial, sans-serif;">
    <div style="background: #fff; border: 1px solid #dfe3ea; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 28px 22px; min-height: 250px;">
    <?php if (isset($_GET['status']) && $_GET['status'] === 'atualizado'): ?>
        <div style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px; border-radius: 5px;">
            Informações da ONG atualizadas com sucesso.
        </div>
    <?php endif; ?>

    <h1 style="font-size: 30px; margin-bottom: 20px; color: #111;">Perfil da ONG</h1>
    <p style="font-size: 16px; margin-bottom: 12px; color: #444;"><strong>E-mail:</strong> <?= htmlspecialchars($ong['email'] ?: 'Não informado') ?></p>
    <p style="font-size: 16px; margin-bottom: 12px; color: #444;"><strong>Nome:</strong> <?= htmlspecialchars($ong['nome_receptor']) ?></p>
    <p style="font-size: 16px; margin-bottom: 12px; color: #444;"><strong>Status:</strong> <?= htmlspecialchars($status) ?></p>
    <p style="font-size: 16px; margin-bottom: 12px; color: #444;"><strong>Área de atuação:</strong> <?= htmlspecialchars($ong['area_atuacao'] ?: 'Não informada') ?></p>
    <p style="font-size: 16px; margin-bottom: 12px; color: #444;"><strong>Cidade / Estado:</strong> <?= htmlspecialchars($localizacao !== '' ? $localizacao : 'Não informado') ?></p>
    <p style="font-size: 16px; margin-bottom: 12px; color: #444;"><strong>Localização:</strong> <?= htmlspecialchars($ong['localizacao'] ?: 'Não informada') ?></p>
    <p style="font-size: 16px; margin-bottom: 12px; color: #444;"><strong>Endereço:</strong> <?= htmlspecialchars($ong['endereco'] ?: 'Não informado') ?></p>
    <p style="font-size: 16px; margin-bottom: 12px; color: #444;"><strong>Descrição:</strong> <?= nl2br(htmlspecialchars($ong['descricao'] ?: 'Sem descrição cadastrada.')) ?></p>

    <div style="margin-top: 20px; display: flex; gap: 10px;">
        <a href="editar_perfil_ong.php" style="background: #0d6efd; color: white; padding: 9px 16px; text-decoration: none; border-radius: 7px; font-size: 14px;">Editar Informações</a>
        <a href="home_ong.php" style="color: #777; padding: 9px 6px; font-size: 14px; text-decoration: underline;">Voltar</a>
    </div>
    </div>
</div>
</body>
</html>