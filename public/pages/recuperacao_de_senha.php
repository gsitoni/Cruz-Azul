<?php
// ==========================
// CONFIGURAÇÕES DE SEGURANÇA
// ==========================

// Cookie seguro (se estiver em HTTPS)
$secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();

// ==========================
// GERAR TOKEN CSRF
// ==========================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ==========================
// MENSAGEM (feedback simples)
// ==========================
$mensagem = "";

if (isset($_GET['status'])) {
    if ($_GET['status'] === 'sucesso') {
        $mensagem = "Se o email existir, um código foi enviado.";
    } elseif ($_GET['status'] === 'erro') {
        $mensagem = "Erro ao processar solicitação.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperação de senha</title>
    <link rel="stylesheet" href="../css/recuperacao_de_senha.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Google+Sans:ital,opsz,wght@0,17..18,400..700;1,17..18,400..700&display=swap');
    </style>
</head>
<body>

    <header>
        <h1>CRUZ-AZUL ✙</h1>
    </header>

    <div class="container">

        <h2 class="subtitulo"> Recuperação de senha </h2>

        <p class="descricao">
            Informe seu email no campo abaixo e aguarde o código de verificação
        </p>

        <!-- MENSAGEM -->
        <?php if (!empty($mensagem)): ?>
            <p style="color: darkblue; text-align:center; margin-bottom:10px;">
                <?php echo htmlspecialchars($mensagem); ?>
            </p>
        <?php endif; ?>

        <form action="../php/processa_recuperacao.php" method="POST" id="form_recuperacao" class="form">

            <!-- TOKEN CSRF -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <fieldset class="email_box">
                <legend class="legend"> E-mail </legend>
                <input class="input" id="email_recuperacao" name="email_recuperacao" type="email" required>
            </fieldset>

            <a href="codigo_de_verificacao.html" id="link_codigo">
                Já possuo o código de verificação
            </a>

            <div class="botoes">
                <button class="botao" type="button" onclick="voltar()">Voltar</button>
                <button class="botao" type="submit">Avançar</button>
            </div>

        </form>

    </div>

    <script src="../js/recuperacao_de_senha.js"></script>

</body>
</html>