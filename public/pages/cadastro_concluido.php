<?php
// ============================================================
//  cadastro_concluido.php  –  public/pages/
//  Página exibida após cadastro bem-sucedido
// ============================================================
session_start();

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

$email = $_GET['email'] ?? '';
$tipo = $_GET['tipo'] ?? 'usuario'; // usuario, admin, ong

if (empty($email)) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro Concluído – Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/cadastro_concluido.css">
</head>
<body>

<header>
    <h1>CRUZ-AZUL ✙</h1>
</header>

<div class="container">
    <div class="card">
        <div class="icone">
            ✅
        </div>

        <h2>Cadastro Realizado com Sucesso!</h2>

        <p class="mensagem">
            Enviamos um e-mail de confirmação para <strong><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></strong>.
            Verifique sua caixa de entrada (e também a pasta de spam) e clique no link para ativar sua conta.
        </p>

        <p class="submensagem">
            Após confirmar seu e-mail, você poderá fazer login e acessar todas as funcionalidades da plataforma.
        </p>

        <div class="botoes">
            <a href="login.php" class="botao">Fazer Login</a>
            <a href="index.php" class="botao secundario">Voltar ao Início</a>
        </div>
    </div>
</div>

</body>
</html></content>