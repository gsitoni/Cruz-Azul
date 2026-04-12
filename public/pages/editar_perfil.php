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

    $stmtDoador = $pdo->prepare("SELECT * FROM doador WHERE email = ?");
    $stmtDoador->execute([$user['email']]);
    $perfil = $stmtDoador->fetch();
    $tipo = 'doador';

    if (!$perfil) {
        $stmtONG = $pdo->prepare("SELECT * FROM beneficiario WHERE id_beneficiario = (SELECT id_vinc_beneficiario FROM usuario WHERE id_usuario = ?)");
        $stmtONG->execute([$id_usuario]);
        $perfil = $stmtONG->fetch();
        $tipo = 'beneficiario';
    }
} catch (PDOException $e) {
    die("Erro ao carregar dados.");
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Perfil - Cruz Azul</title>
</head>
<body>
    <div style="max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
        <h1>Editar Perfil</h1>
        <form action="../../src/api/atualizar_usuario.php" method="POST">
            
            <?php if ($tipo === 'doador'): ?>
                <div style="margin-bottom: 15px;">
                    <label>Nome:</label><br>
                    <input type="text" name="nome" value="<?= htmlspecialchars($perfil['nome']) ?>" required style="width: 100%; padding: 8px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label>Telefone:</label><br>
                    <input type="text" name="telefone" value="<?= htmlspecialchars($perfil['telefone']) ?>" style="width: 100%; padding: 8px;">
                </div>
            <?php else: ?>
                <div style="margin-bottom: 15px;">
                    <label>Nome da Instituição:</label><br>
                    <input type="text" name="nome_receptor" value="<?= htmlspecialchars($perfil['nome_receptor']) ?>" required style="width: 100%; padding: 8px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label>Localização:</label><br>
                    <input type="text" name="localizacao" value="<?= htmlspecialchars($perfil['localizacao']) ?>" style="width: 100%; padding: 8px;">
                </div>
            <?php endif; ?>

            <button type="submit" style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">Salvar Alterações</button>
            <a href="perfil.php" style="margin-left: 10px; color: #666;">Cancelar</a>
        </form>
    </div>
</body>
</html>