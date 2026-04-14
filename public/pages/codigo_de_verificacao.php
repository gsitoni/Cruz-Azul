<?php
// ==========================
// CONFIG SEGURA DE COOKIE
// ==========================
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
// GERAR CSRF
// ==========================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ==========================
// MENSAGEM
// ==========================
$erro = "";

// ==========================
// PROCESSAR FORM
// ==========================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Requisição inválida.");
    }

    // PEGAR CÓDIGO DIGITADO
    $codigo_digitado = $_POST['codigo'] ?? "";

    // PEGAR CÓDIGO REAL
    $codigo_sessao = $_SESSION['codigo_recuperacao'] ?? "";
    $codigo_cookie = $_COOKIE['codigo_recuperacao'] ?? "";

    // VALIDAÇÃO
    if (
        !empty($codigo_digitado) &&
        ($codigo_digitado == $codigo_sessao || $codigo_digitado == $codigo_cookie)
    ) {

        // marca como verificado
        $_SESSION['verificado'] = true;

        // remove código (boa prática)
        unset($_SESSION['codigo_recuperacao']);
        setcookie("codigo_recuperacao", "", time() - 3600, "/");

        header("Location: redefinicao_de_senha.php");
        exit();

    } else {
        $erro = "Código inválido ou expirado.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de verificação</title>
    <link rel="stylesheet" href="../assets/css/codigo_de_verificacao.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Google+Sans:ital,opsz,wght@0,17..18,400..700&display=swap');
    </style>
</head>
<body>

<header>
    <h1>CRUZ-AZUL ✙</h1>
</header>

<div class="container">

    <h2 class="subtitulo"> Recuperação de senha </h2>
    <h3 class="descricao">Enviamos um código para o seu e-mail</h3>
    <p class="descricao">Digite o código de verificação recebido</p>

    <!-- ERRO -->
    <?php if (!empty($erro)): ?>
        <p style="color:red; text-align:center;"><?php echo htmlspecialchars($erro); ?></p>
    <?php endif; ?>

    <form id="form_codigo" method="POST" class="form">

        <!-- CSRF -->
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <!-- CÓDIGO FINAL -->
        <input type="hidden" name="codigo" id="codigo_final">

        <div class="inputs">
            <input class="input" type="text" maxlength="1">
            <input class="input" type="text" maxlength="1">
            <input class="input" type="text" maxlength="1">
            <input class="input" type="text" maxlength="1">
            <input class="input" type="text" maxlength="1">
            <input class="input" type="text" maxlength="1">
        </div>

        <a href="./recuperacao_de_senha.php" id="link_recuperacao_senha">
            Não recebi o código
        </a>

        <div class="botoes">
            <button class="botao" type="button" onclick="voltar()">Voltar</button>
            <button class="botao" type="submit">Avançar</button>
        </div>

    </form>
</div>

<script src="../assets/js/codigo_de_verificacao.js"></script>

<!-- SCRIPT EXTRA PARA ENVIAR O CÓDIGO -->
<script>
const form = document.getElementById("form_codigo");
const inputs = document.querySelectorAll(".inputs .input");
const hiddenInput = document.getElementById("codigo_final");

form.addEventListener("submit", function(e) {
    let codigo = "";

    inputs.forEach(input => {
        codigo += input.value;
    });

    hiddenInput.value = codigo;
});
</script>

</body>
</html>