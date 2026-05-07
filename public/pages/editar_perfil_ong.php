<?php
session_start();
 
// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
 
if (!isset($_SESSION['ong'])) {
    header('Location: login_ong.php');
    exit;
}
 
require '../../src/api/database.php';
 
$ongId = (int) ($_SESSION['ong']['id'] ?? 0);
$erro = '';
// Processa o formulário de edição
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
            UPDATE ong
            SET nome = ?, email = ?, area_atuacao = ?, localizacao = ?, cidade = ?, sigla_estado = ?, endereco = ?, descricao = ?, data_atualizacao = NOW()
            WHERE id_ong = ?';
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
            'nome' => $nome,
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
    SELECT nome, email, area_atuacao, localizacao, cidade, sigla_estado, endereco, descricao
    FROM ong
    WHERE id_ong = ?
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
            <input type="text" id="nome_receptor" name="nome_receptor" value="<?= htmlspecialchars($ong['nome']) ?>" required style="width: 100%; padding: 9px 10px; border: 1px solid #cfd6df; border-radius: 7px; font-size: 13px;">
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
<style>
.zona-perigo {
    margin-top: 40px; padding: 16px;
    border: 1px solid #f5c6cb; border-radius: 8px; background: #fff5f5;
    max-width: 800px; margin: 40px auto 0; 
}
.zona-perigo h3 { color: #dc3545; margin: 0 0 8px 0; font-size: 1rem; }
.zona-perigo p  { color: #555; font-size: .875rem; margin: 0 0 12px 0; }
.btn-danger {
    background: #dc3545; color: #fff; padding: 10px 20px;
    border: none; border-radius: 5px; cursor: pointer; font-size: 1rem;
}
.btn-danger:hover { background: #c82333; }
</style>
 
<div class="zona-perigo" style="max-width:800px;margin:20px auto 0;padding:16px;border:1px solid #f5c6cb;border-radius:8px;background:#fff5f5;">
    <h3 style="color:#dc3545;margin:0 0 8px 0;font-size:1rem;">⚠️ Zona de Perigo</h3>
    <p style="color:#555;font-size:.875rem;margin:0 0 12px 0;">Ao deletar a conta da ONG, todos os dados serão removidos permanentemente. Esta ação não pode ser desfeita.</p>
    <button onclick="confirmarDelecao()" style="background:#dc3545;color:#fff;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;font-size:1rem;">Deletar conta da ONG</button>
</div>
 
<script>
function confirmarDelecao() {
    if (confirm('Tem certeza que deseja deletar a conta da ONG? Esta ação é irreversível.')) {
        if (confirm('Última confirmação: todos os dados serão apagados permanentemente. Continuar?')) {
            fetch('../../src/api/deletar_conta.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'csrf_token=' + encodeURIComponent('<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>')
            })
            .then(r => r.json())
            .then(json => {
                if (json.ok) {
                    alert('Conta deletada com sucesso.');
                    window.location.href = 'login_ong.php';
                } else {
                    alert('Erro: ' + json.msg);
                }
            })
            .catch(() => alert('Erro de conexão. Tente novamente.'));
        }
    }
}
</script>
</body>
</html>