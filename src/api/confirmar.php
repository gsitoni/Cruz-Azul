<?php
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

require __DIR__ . '/database.php';

$token = trim($_GET['token'] ?? '');
$status = 'erro';
$mensagem = 'Link de confirmacao invalido ou expirado.';
$email = '';

if ($token === '' || strlen($token) > 255) {
    $mensagem = 'Token de confirmacao invalido ou ausente.';
} else {
    try {
        $stmt = $pdo->prepare(
            'SELECT id_usuario, email, status_cadastro FROM usuario WHERE token_confirmacao = ? LIMIT 1'
        );
        $stmt->execute([$token]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            $stmtEmail = $pdo->prepare(
                'SELECT email FROM usuario WHERE token_confirmacao IS NULL AND status_cadastro = ? ORDER BY id_usuario DESC LIMIT 1'
            );
            $stmtEmail->execute(['confirmado']);
            $email = (string) ($stmtEmail->fetchColumn() ?: '');
        } elseif (($usuario['status_cadastro'] ?? '') === 'confirmado') {
            $status = 'info';
            $mensagem = 'Sua conta ja estava confirmada. Agora voce pode entrar normalmente.';
            $email = (string) ($usuario['email'] ?? '');
        } else {
            $upd = $pdo->prepare(
                "UPDATE usuario SET status_cadastro = 'confirmado', token_confirmacao = NULL WHERE id_usuario = ?"
            );
            $upd->execute([$usuario['id_usuario']]);

            $status = 'sucesso';
            $mensagem = 'Cadastro confirmado com sucesso. Sua conta ja pode acessar a plataforma.';
            $email = (string) ($usuario['email'] ?? '');
        }
    } catch (PDOException $e) {
        error_log('confirmar.php PDOException: ' . $e->getMessage());
        $mensagem = 'Erro interno ao confirmar o cadastro. Tente novamente.';
    }
}

$query = http_build_query([
    'status' => $status,
    'msg' => $mensagem,
    'email' => $email,
]);

header('Location: ../../public/pages/confirmacao_realizada.php?' . $query);
exit;
