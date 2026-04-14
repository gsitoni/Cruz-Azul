<?php
// ============================================================
//  redefinicao_de_senha.php  –  public/pages/
//  Segurança: CSRF, sessão verificada, XSS, PDO
// ============================================================
$secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => $secure,
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

require_once __DIR__ . '/../../src/api/database.php';
require_once __DIR__ . '/../../src/api/valida_senha.php';

// Só permite acesso se o código foi verificado
if (empty($_SESSION['verificado']) || $_SESSION['verificado'] !== true) {
    header('Location: recuperacao_de_senha.php');
    exit();
}

$email = $_SESSION['email_recuperacao'] ?? null;
if (!$email) {
    header('Location: recuperacao_de_senha.php');
    exit();
}

// Gera CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF
    if (
        empty($_SESSION['csrf_token']) ||
        empty($_POST['csrf_token'])    ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        die("Requisição inválida.");
    }

    $senha       = trim($_POST['nova_senha']       ?? '');
    $confirmacao = trim($_POST['confirmacao_senha'] ?? '');

    if ($senha !== $confirmacao) {
        $erro = 'As senhas não coincidem.';

    } else {
        $validacao = validarSenhaForte($senha);

        if ($validacao !== true) {
            $erro = $validacao;

        } else {
            // Verifica que a nova senha é diferente da atual
            $stmtAtual = $pdo->prepare("SELECT senha_hash FROM usuario WHERE email = ? LIMIT 1");
            $stmtAtual->execute([$email]);
            $row = $stmtAtual->fetch(PDO::FETCH_ASSOC);

            if ($row && password_verify($senha, $row['senha_hash'])) {
                $erro = 'A nova senha não pode ser igual à senha atual.';
            } else {
                $hash = password_hash($senha, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare(
                    "UPDATE usuario SET senha_hash = ? WHERE email = ?"
                );
                $stmt->execute([$hash, $email]);

                // Limpa sessão de recuperação
                unset(
                    $_SESSION['verificado'],
                    $_SESSION['email_recuperacao'],
                    $_SESSION['codigo_recuperacao'],
                    $_SESSION['expira_codigo'],
                    $_SESSION['csrf_token']
                );

                header('Location: login.php?status=senha_redefinida');
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinição de senha – Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/redefinicao_de_senha.css">
</head>
<body>

<header>
    <h1>CRUZ-AZUL ✙</h1>
</header>

<div class="container">

    <h2 class="subtitulo">Redefinição de senha</h2>

    <div class="condicoes">
        <b><p class="condicao">Sua nova senha deve ter:</p></b>
        <p class="condicao" id="condicao_tamanho">Mínimo 10 caracteres</p>
        <p class="condicao" id="condicao_especial">1 caractere especial (@, #, $, %)</p>
        <p class="condicao" id="condicao_numero">Deve conter números</p>
        <p class="condicao" id="condicao_sequencia">Sem sequências (abcd, 1234)</p>
    </div>

    <?php if (!empty($erro)): ?>
        <p style="color:red;text-align:center;">
            <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form method="POST" class="form">

        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

        <div class="inputs">

            <fieldset class="email_box">
                <legend class="legend">Nova senha</legend>
                <input class="input" type="password" id="nova_senha"
                       name="nova_senha" required maxlength="255"
                       autocomplete="new-password">
            </fieldset>

            <fieldset class="email_box">
                <legend class="legend">Confirmar nova senha</legend>
                <input class="input" type="password" id="confirmacao_senha"
                       name="confirmacao_senha" required maxlength="255"
                       autocomplete="new-password">
            </fieldset>

            <p id="erro" style="color:red;display:none;">As senhas devem ser iguais.</p>

        </div>

        <div class="botoes">
            <button class="botao" type="button" onclick="history.back()">Voltar</button>
            <button class="botao" type="submit">Salvar nova senha</button>
        </div>

    </form>
</div>

<script src="../js/redefinicao_de_senha.js"></script>
</body>
</html>
