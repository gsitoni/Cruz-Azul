<?php
session_start();
require_once __DIR__ . '/../../src/api/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['usuario_id'];

try {
    $stmtUser = $pdo->prepare("SELECT email FROM usuario WHERE id_usuario = ?");
    $stmtUser->execute([$id_usuario]);
    $user = $stmtUser->fetch();

    // Busca na tabela doador
    $stmtDoador = $pdo->prepare("SELECT * FROM doador WHERE email = ?");
    $stmtDoador->execute([$user['email']]);
    $perfil = $stmtDoador->fetch();
    $tipo = 'doador';

    // Se não for doador, busca beneficiário
    if (!$perfil) {
        $stmtONG = $pdo->prepare("SELECT * FROM beneficiario WHERE id_beneficiario = (SELECT id_vinc_beneficiario FROM usuario WHERE id_usuario = ?)");
        $stmtONG->execute([$id_usuario]);
        $perfil = $stmtONG->fetch();
        $tipo = 'beneficiario';
    }
} catch (PDOException $e) {
    die("Erro ao carregar perfil.");
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Meu Perfil - Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/perfil.css">
</head>
<body>
    <div class="perfil-container" style="max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
        <?php if (isset($_GET['status']) && $_GET['status'] == 'atualizado'): ?>
            <div style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px; border-radius: 5px;">
                Dados atualizados com sucesso!
            </div>
        <?php endif; ?>

        <h1>Meu Perfil</h1>
        <p><strong>E-mail:</strong> <?= htmlspecialchars($user['email']) ?></p>
        
        <?php if ($tipo === 'doador'): ?>
            <p><strong>Nome:</strong> <?= htmlspecialchars($perfil['nome']) ?></p>
            <p><strong>Telefone:</strong> <?= htmlspecialchars($perfil['telefone']) ?></p>
        <?php else: ?>
            <p><strong>Instituição:</strong> <?= htmlspecialchars($perfil['nome_receptor']) ?></p>
            <p><strong>Localização:</strong> <?= htmlspecialchars($perfil['localizacao']) ?></p>
        <?php endif; ?>

        <div style="margin-top: 30px; display: flex; gap: 10px;">
            <a href="editar_perfil.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Editar Informações</a>
            <a href="logout.php" style="color: #666; padding: 10px;">Sair</a>
        </div>
    </div>
</body>
</html>