<?php
require __DIR__ . '/../../api/database.php';
require __DIR__ . '/../../api/valida_senha.php';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Strict',
]);

session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

if (
    empty($_SESSION['admin_recovery_verified']) ||
    empty($_SESSION['admin_recovery_user_id'])
) {
    header('Location: recuperar_senha.php');
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$usuarioId = (int) $_SESSION['admin_recovery_user_id'];
$stmt = $pdo->prepare(
    "SELECT id_usuario, email, tipo, senha_hash
     FROM usuario
     WHERE id_usuario = ?
     LIMIT 1"
);
$stmt->execute([$usuarioId]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

$ehAdmin =
    $usuario &&
    stripos((string) ($usuario['tipo'] ?? ''), 'admin') !== false;

if (!$ehAdmin) {
    unset(
        $_SESSION['admin_recovery_user_id'],
        $_SESSION['admin_recovery_question'],
        $_SESSION['admin_recovery_email'],
        $_SESSION['admin_recovery_verified']
    );

    header('Location: recuperar_senha.php');
    exit();
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        empty($_SESSION['csrf_token']) ||
        empty($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        die('Requisicao invalida.');
    }

    $novaSenha = trim((string) ($_POST['nova_senha'] ?? ''));
    $confirmacao = trim((string) ($_POST['confirmacao_senha'] ?? ''));

    if ($novaSenha === '' || $confirmacao === '') {
        $erro = 'Preencha os dois campos de senha.';
    } elseif ($novaSenha !== $confirmacao) {
        $erro = 'As senhas nao coincidem.';
    } else {
        $validacao = validarSenhaForte($novaSenha);

        if ($validacao !== true) {
            $erro = (string) $validacao;
        } elseif (temSequencia($novaSenha)) {
            $erro = 'A senha nao pode conter sequencias simples (ex: 1234, abcd).';
        } elseif (!empty($usuario['senha_hash']) && password_verify($novaSenha, (string) $usuario['senha_hash'])) {
            $erro = 'A nova senha nao pode ser igual a senha atual.';
        } else {
            $hash = password_hash($novaSenha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE usuario SET senha_hash = ? WHERE id_usuario = ? LIMIT 1');
            $stmt->execute([$hash, $usuarioId]);

            unset(
                $_SESSION['admin_recovery_user_id'],
                $_SESSION['admin_recovery_question'],
                $_SESSION['admin_recovery_email'],
                $_SESSION['admin_recovery_verified'],
                $_SESSION['admin_login_tentativas']
            );

            header('Location: ./index.php?status=senha_redefinida');
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir senha admin - Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
    <main class="login-shell">
        <section class="login-panel login-panel--brand">
            <span class="eyebrow">Nova senha</span>
            <h1>Administrador</h1>
            <p>
                Defina uma nova senha forte para recuperar o acesso administrativo de forma segura.
            </p>
            <ul class="feature-list">
                <li>Minimo de 12 caracteres</li>
                <li>Letras maiusculas, minusculas, numeros e simbolos</li>
                <li>Sem sequencias simples</li>
            </ul>
        </section>

        <section class="login-panel login-panel--form">
            <div class="form-header">
                <h2>Redefinir senha</h2>
                <p>Conta: <?= htmlspecialchars((string) ($usuario['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <div class="msg <?= $erro !== '' ? 'erro' : '' ?>">
                <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
            </div>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                <label for="nova_senha">Nova senha</label>
                <input type="password" id="nova_senha" name="nova_senha" required minlength="12" maxlength="255" autocomplete="new-password">

                <label for="confirmacao_senha">Confirmar nova senha</label>
                <input type="password" id="confirmacao_senha" name="confirmacao_senha" required minlength="12" maxlength="255" autocomplete="new-password">

                <button type="submit" id="btnEntrar">Salvar nova senha</button>
            </form>

            <div class="support-links">
                <a href="./recuperar_senha.php?reset=1">Voltar para recuperacao</a>
                <a href="./index.php">Voltar ao login</a>
            </div>
        </section>
    </main>
</body>
</html>
