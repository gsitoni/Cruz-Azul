<?php

require '../../src/api/database.php';
require_once '../includes/valida_senha.php';
// ==========================
// CONFIG COOKIE SEGURO
// ==========================
$secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();

// ==========================
// PROTEÇÃO: VERIFICAR ACESSO
// ==========================
if (!isset($_SESSION['verificado']) || $_SESSION['verificado'] !== true) {
    die("Acesso não autorizado.");
}

// garante de quem é a conta
$email = $_SESSION['email_recuperacao'] ?? null;

if (!$email) {
    die("Sessão inválida.");
}

// ==========================
// CSRF
// ==========================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ==========================
// ERRO
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

    $senha = trim($_POST['nova_senha'] ?? "");
    $confirmacao = trim($_POST['confirmacao_senha'] ?? "");

    // ==========================
    // VALIDAÇÕES
    // ==========================

    if ($senha !== $confirmacao) {
    $erro = "As senhas não coincidem.";
    } else {

        $validacao = validarSenhaForte($senha);

        if ($validacao !== true) {
            $erro = $validacao; // mensagem vinda do arquivo externo
        } else {

            // ==========================
            // HASH SEGURO COM password_hash()
            // ==========================
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            // Atualiza a senha no banco usando prepared statement
            $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE email = ?");
            $stmt->execute([$senha_hash, $email]);

            // limpar sessão
            unset($_SESSION['verificado']);
            unset($_SESSION['email_recuperacao']);

            header("Location: ../test_email_bismark/PHPMailer/confirmar.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinição de senha</title>
    <link rel="stylesheet" href="../css/redefinicao_de_senha.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Google+Sans:ital,opsz,wght@0,17..18,400..700&display=swap');
    </style>
</head>
<body>

<header>
    <h1>CRUZ-AZUL ✙</h1>
</header>

<div class="container">

    <h2 class="subtitulo"> Redefinição de senha </h2>

    <div class="condicoes">
        <b><p class="condicao"> Sua nova senha: </p></b>
        <p class="condicao" id="condicao_tamanho">Mínimo 10 caracteres</p>
        <p class="condicao" id="condicao_especial">1 caractere especial (@, #, $, %)</p>
        <p class="condicao" id="condicao_numero">Deve conter números</p>
        <p class="condicao" id="condicao_sequencia">Sem sequências (abcd, 1234)</p>
    </div>

    <!-- ERRO -->
    <?php if (!empty($erro)): ?>
        <p style="color:red; text-align:center;"><?php echo htmlspecialchars($erro); ?></p>
    <?php endif; ?>

    <form method="POST" class="form">

        <!-- CSRF -->
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <div class="inputs">

            <fieldset class="email_box">
                <legend class="legend"> Nova senha </legend>
                <input class="input" type="password" id="nova_senha" name="nova_senha" required>
            </fieldset>

            <p>Confirme a nova senha</p>

            <fieldset class="email_box">
                <legend class="legend"> Nova senha </legend>
                <input class="input" type="password" id="confirmacao_senha" name="confirmacao_senha" required>
            </fieldset>

            <p id="erro">As senhas devem ser compatíveis</p>

        </div>

        <div class="botoes">
            <button class="botao" type="button" onclick="voltar()">Voltar</button>
            <button class="botao" type="submit">Avançar</button>
        </div>

    </form>
</div>

<script src="../js/redefinicao_de_senha.js"></script>

</body>
</html>