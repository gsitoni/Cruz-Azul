<?php
session_start();

require_once __DIR__ . '/../../src/api/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

header("X-Frame-Options: DENY");
header("Content-Security-Policy: frame-ancestors 'none'");

$id_usuario = $_SESSION['usuario_id'];

try {
    $stmtUser = $pdo->prepare("SELECT email, data_criacao, status_cadastro FROM usuario WHERE id_usuario = ?");
    $stmtUser->execute([$id_usuario]);
    $dadosUsuario = $stmtUser->fetch();

    if (!$dadosUsuario) {
        session_destroy();
        header("Location: login.php");
        exit();
    }

    $stmtDoador = $pdo->prepare("SELECT * FROM doador WHERE email = ?");
    $stmtDoador->execute([$dadosUsuario['email']]);
    $perfilDoador = $stmtDoador->fetch();

    $perfilONG = null;
    if (!$perfilDoador) {
        $stmtONG = $pdo->prepare("SELECT * FROM beneficiario WHERE id_beneficiario = (SELECT id_vinc_beneficiario FROM usuario WHERE id_usuario = ?)");
        $stmtONG->execute([$id_usuario]);
        $perfilONG = $stmtONG->fetch();
    }

} catch (PDOException $e) {
    die("Erro crítico de segurança ao carregar dados.");
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/perfil.css"> 
</head>
<body>
    <div class="perfil-container">
        <h1>Meu Perfil</h1>
        
        <section class="info-secao">
            <h3>Informações da Conta</h3>
            <p><strong>E-mail:</strong> <?= htmlspecialchars($dadosUsuario['email']) ?></p>
            <p><strong>Membro desde:</strong> <?= date('d/m/Y', strtotime($dadosUsuario['data_criacao'])) ?></p>
            <p><strong>Status da Conta:</strong> <?= htmlspecialchars(ucfirst($dadosUsuario['status_cadastro'])) ?></p>
        </section>

        <hr>

        <?php if ($perfilDoador): ?>
            <section class="info-secao">
                <h3>Dados do Doador</h3>
                <p><strong>Nome:</strong> <?= htmlspecialchars($perfilDoador['nome']) ?></p>
                <p><strong>CPF/CNPJ:</strong> <?= htmlspecialchars($perfilDoador['cpf_cnpj']) ?></p>
                <p><strong>Telefone:</strong> <?= htmlspecialchars($perfilDoador['telefone']) ?></p>
            </section>
        <?php elseif ($perfilONG): ?>
            <section class="info-secao">
                <h3>Dados da ONG (Beneficiário)</h3>
                <p><strong>Nome da Instituição:</strong> <?= htmlspecialchars($perfilONG['nome_receptor']) ?></p>
                <p><strong>CNPJ:</strong> <?= htmlspecialchars($perfilONG['cnpj']) ?></p>
                <p><strong>Localização:</strong> <?= htmlspecialchars($perfilONG['localizacao']) ?></p>
                <p><strong>Status de Elegibilidade:</strong> <?= htmlspecialchars(ucfirst($perfilONG['status_elegibilidade'])) ?></p>
            </section>
        <?php else: ?>
            <p>Perfil detalhado não encontrado. Por favor, complete o seu cadastro.</p>
        <?php endif; ?>

        <div class="acoes-perfil" style="margin-top: 30px;">
            <a href="editar_perfil.php" class="btn-editar">Editar Dados</a>
            
            <button onclick="solicitarExclusao()" class="btn-excluir" style="background-color: #ff4d4d; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 5px;">
                Excluir Minha Conta
            </button>
        </div>
    </div>

    <script>
    function solicitarExclusao() {
        if (confirm("ATENÇÃO: Deseja apagar todos os seus dados permanentemente? Esta ação cumpre os requisitos da LGPD e não pode ser desfeita.")) {
            
            fetch('../../api/excluir_dados.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ 'acao': 'total' })
            })
            .then(response => response.json())
            .then(data => {
                if (data.ok) {
                    alert(data.msg);
                    window.location.href = 'login.php';
                } else {
                    alert("Erro: " + data.msg);
                }
            })
            .catch(error => console.error('Erro na requisição:', error));
        }
    }
    </script>
</body>
</html>