<?php
// ============================================================
//  codigo_de_verificacao.php  –  public/pages/
// ============================================================
$secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $secure,
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

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

    $codigo_digitado = trim($_POST['codigo'] ?? '');
    $codigo_sessao   = (string) ($_SESSION['codigo_recuperacao'] ?? '');
    $expira          = $_SESSION['expira_codigo'] ?? 0;

    if (empty($codigo_digitado)) {
        $erro = 'Digite o código recebido.';

    } elseif (time() > $expira) {
        // Limpa código expirado
        unset($_SESSION['codigo_recuperacao'], $_SESSION['expira_codigo']);
        $erro = 'Código expirado. Solicite um novo.';

    } elseif (!hash_equals($codigo_sessao, $codigo_digitado)) {
        $erro = 'Código inválido.';

    } else {
        // Código correto — marca sessão como verificada
        $_SESSION['verificado'] = true;
        unset($_SESSION['codigo_recuperacao'], $_SESSION['expira_codigo']);

        header('Location: redefinicao_de_senha.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de verificação – Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/codigo_de_verificacao.css">
</head>
<body>

<header>
    <h1>CRUZ-AZUL ✙</h1>
</header>

<div class="container">

    <h2 class="subtitulo">Recuperação de senha</h2>
    <h3 class="descricao">Enviamos um código para o seu e-mail</h3>
    <p class="descricao">Digite o código de verificação recebido</p>

    <?php if (!empty($erro)): ?>
        <p style="color:red;text-align:center;">
            <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form id="form_codigo" method="POST" class="form">

        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

        <!-- Campo hidden que recebe o código montado pelo JS -->
        <input type="hidden" name="codigo" id="codigo_final">

        <div class="inputs">
            <input class="input" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
            <input class="input" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
            <input class="input" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
            <input class="input" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
            <input class="input" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
            <input class="input" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
        </div>

        <a href="recuperacao_de_senha.php" id="link_recuperacao_senha">
            Não recebi o código
        </a>

        <div class="botoes">
            <button class="botao" type="button" onclick="history.back()">Voltar</button>
            <button class="botao" type="submit">Avançar</button>
        </div>

    </form>
</div>

<script src="../assets/js/codigo_de_verificacao.js"></script>
<script>
const form   = document.getElementById('form_codigo');
const inputs = document.querySelectorAll('.inputs .input');
const hidden = document.getElementById('codigo_final');

// Avança para o próximo campo automaticamente
inputs.forEach((input, i) => {
    input.addEventListener('input', () => {
        input.value = input.value.replace(/\D/g, ''); // só números
        if (input.value && i < inputs.length - 1) {
            inputs[i + 1].focus();
        }
    });
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !input.value && i > 0) {
            inputs[i - 1].focus();
        }
    });
});

// Monta o código completo antes de enviar
form.addEventListener('submit', function() {
    hidden.value = Array.from(inputs).map(i => i.value).join('');
});
</script>

</body>
</html>
