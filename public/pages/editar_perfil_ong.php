<?php
session_start();

if (!isset($_SESSION['ong'])) {
    header('Location: login_ong.php');
    exit;
}

require '../../src/api/database.php';

$ongId = (int) ($_SESSION['ong']['id'] ?? 0);
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome_receptor'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $area = trim($_POST['area_atuacao'] ?? '');
    $localizacao = trim($_POST['localizacao'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $estado = strtoupper(trim($_POST['sigla_estado'] ?? ''));
    $endereco = trim($_POST['endereco'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');

    if ($nome === '' || $email === '') {
        $erro = 'Preencha pelo menos nome da ONG e e-mail.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido.';
    }

    if ($erro === '') {
        $sql = '
            UPDATE beneficiario
            SET nome_receptor = ?, email = ?, area_atuacao = ?, localizacao = ?, cidade = ?, sigla_estado = ?, endereco = ?, descricao = ?, data_atualizacao = NOW()
            WHERE id_beneficiario = ?';
        $parametros = [$nome, $email, $area, $localizacao, $cidade, $estado, $endereco, $descricao, $ongId];

        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);

        $_SESSION['ong']['nome'] = $nome;
        $_SESSION['ong']['email'] = $email;
        $_SESSION['ong']['area_atuacao'] = $area;
        $_SESSION['ong']['cidade'] = $cidade;
        $_SESSION['ong']['estado'] = $estado;

        header('Location: perfil_ong.php?status=atualizado');
        exit;
    } else {
        $ong = [
            'nome_receptor' => $nome,
            'email' => $email,
            'area_atuacao' => $area,
            'cidade' => $cidade,
            'sigla_estado' => $estado,
            'localizacao' => $localizacao,
            'endereco' => $endereco,
            'descricao' => $descricao,
        ];
    }
}

$stmt = $pdo->prepare('
    SELECT nome_receptor, email, area_atuacao, localizacao, cidade, sigla_estado, endereco, descricao
    FROM beneficiario
    WHERE id_beneficiario = ?
');
$stmt->execute([$ongId]);
$ong = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ong) {
    header('Location: logout.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil da ONG - Cruz Azul</title>
</head>
<body>
<div style="max-width: 800px; margin: 18px auto; padding: 0 16px; font-family: Arial, sans-serif;">
    <div style="background: #fff; border: 1px solid #dfe3ea; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 28px 22px;">
    <h1 style="font-size: 30px; margin-bottom: 20px; color: #111;">Editar Perfil da ONG</h1>

    <?php if ($erro !== ''): ?>
        <div style="background: #fdecea; color: #c0392b; padding: 10px; margin-bottom: 20px; border-radius: 5px;">
            <?= htmlspecialchars($erro) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div style="margin-bottom: 15px;">
            <label for="nome_receptor" style="display:block; font-size: 14px; font-weight: bold; margin-bottom: 6px; color: #444;">Nome da ONG</label>
            <input type="text" id="nome_receptor" name="nome_receptor" value="<?= htmlspecialchars($ong['nome_receptor']) ?>" required style="width: 100%; padding: 9px 10px; border: 1px solid #cfd6df; border-radius: 7px; font-size: 13px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="email" style="display:block; font-size: 14px; font-weight: bold; margin-bottom: 6px; color: #444;">E-mail</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($ong['email']) ?>" required style="width: 100%; padding: 9px 10px; border: 1px solid #cfd6df; border-radius: 7px; font-size: 13px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="area_atuacao" style="display:block; font-size: 14px; font-weight: bold; margin-bottom: 6px; color: #444;">Área de atuação</label>
            <input type="text" id="area_atuacao" name="area_atuacao" value="<?= htmlspecialchars($ong['area_atuacao']) ?>" style="width: 100%; padding: 9px 10px; border: 1px solid #cfd6df; border-radius: 7px; font-size: 13px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="cidade" style="display:block; font-size: 14px; font-weight: bold; margin-bottom: 6px; color: #444;">Cidade</label>
            <input type="text" id="cidade" name="cidade" value="<?= htmlspecialchars($ong['cidade']) ?>" style="width: 100%; padding: 9px 10px; border: 1px solid #cfd6df; border-radius: 7px; font-size: 13px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="sigla_estado" style="display:block; font-size: 14px; font-weight: bold; margin-bottom: 6px; color: #444;">Estado</label>
            <input type="text" id="sigla_estado" name="sigla_estado" maxlength="2" value="<?= htmlspecialchars($ong['sigla_estado']) ?>" style="width: 100%; padding: 9px 10px; border: 1px solid #cfd6df; border-radius: 7px; font-size: 13px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="localizacao" style="display:block; font-size: 14px; font-weight: bold; margin-bottom: 6px; color: #444;">Localização</label>
            <input type="text" id="localizacao" name="localizacao" value="<?= htmlspecialchars($ong['localizacao']) ?>" style="width: 100%; padding: 9px 10px; border: 1px solid #cfd6df; border-radius: 7px; font-size: 13px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="endereco" style="display:block; font-size: 14px; font-weight: bold; margin-bottom: 6px; color: #444;">Endereço</label>
            <input type="text" id="endereco" name="endereco" value="<?= htmlspecialchars($ong['endereco']) ?>" style="width: 100%; padding: 9px 10px; border: 1px solid #cfd6df; border-radius: 7px; font-size: 13px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label for="descricao" style="display:block; font-size: 14px; font-weight: bold; margin-bottom: 6px; color: #444;">Descrição</label>
            <textarea id="descricao" name="descricao" style="width: 100%; padding: 9px 10px; min-height: 88px; border: 1px solid #cfd6df; border-radius: 7px; font-size: 13px;"><?= htmlspecialchars($ong['descricao']) ?></textarea>
        </div>

        <button type="submit" style="background: #0d6efd; color: white; padding: 9px 16px; border: none; border-radius: 7px; cursor: pointer; font-size: 14px;">Salvar Alterações</button>
        <a href="perfil_ong.php" style="margin-left: 14px; color: #777; font-size: 14px; text-decoration: underline;">Cancelar</a>
    </form>
    </div>
</div>
</body>
</html>