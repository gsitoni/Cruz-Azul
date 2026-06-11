<?php
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

require __DIR__ . '/database.php';
require_once __DIR__ . '/../../src/crypto/crypto_helpers.php';
/** @var PDO $pdo */

$token          = trim($_GET['token'] ?? '');
$tipoSolicitado = strtolower(trim($_GET['tipo'] ?? ''));
$status         = 'erro';
$mensagem       = 'Link de confirmacao invalido ou expirado.';
$email          = '';
$tipoConta      = $tipoSolicitado;

if ($token === '' || strlen($token) > 255) {
    $mensagem = 'Token de confirmacao invalido ou ausente.';
} else {
    try {
        // Busca em usuario (cobre doadores E ONGs — ambos usam usuario como tabela mãe)
        $stmt = $pdo->prepare(
            sprintf(
                'SELECT id_usuario, email, status_cadastro, %s FROM usuario WHERE token_confirmacao = ? LIMIT 1',
                obterSelecaoPerfilUsuario($pdo, '')
            )
        );
        $stmt->execute([$token]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            // Token não encontrado — pega último email confirmado para exibir
            $stmtEmail = $pdo->prepare(
                'SELECT email FROM usuario WHERE token_confirmacao IS NULL AND status_cadastro = ? ORDER BY id_usuario DESC LIMIT 1'
            );
            $stmtEmail->execute(['confirmado']);
            $email = (string) ($stmtEmail->fetchColumn() ?: '');

        } elseif (($usuario['status_cadastro'] ?? '') === 'confirmado') {
            $status    = 'info';
            $mensagem  = 'Sua conta ja estava confirmada. Agora voce pode entrar normalmente.';
            $email     = (string) ($usuario['email'] ?? '');
            $tipoConta = (string) ($usuario['tipo']  ?? $tipoConta);

        } else {
            $upd = $pdo->prepare(
                "UPDATE usuario SET status_cadastro = 'confirmado', token_confirmacao = NULL WHERE id_usuario = ?"
            );
            $upd->execute([$usuario['id_usuario']]);

            $status    = 'sucesso';
            $tipoConta = (string) ($usuario['tipo'] ?? $tipoConta);
            $email     = (string) ($usuario['email'] ?? '');

            if ($tipoConta === 'ong') {
                $mensagem = 'E-mail confirmado! Sua ONG sera analisada pelo administrador antes de ser ativada.';
            } else {
                $mensagem = 'Cadastro confirmado com sucesso. Sua conta ja pode acessar a plataforma.';
            }
        }
    } catch (PDOException $e) {
        error_log('confirmar.php PDOException: ' . $e->getMessage());
        $mensagem = 'Erro interno ao confirmar o cadastro. Tente novamente.';
    }
}

$ehAdmin = stripos($tipoConta, 'admin') !== false;

$query = http_build_query([
    'status' => $status,
    'msg'    => $mensagem,
    'email'  => $email,
    'tipo'   => $ehAdmin ? 'admin' : ($tipoConta ?: 'usuario'),
]);

$destino = $ehAdmin
    ? '../../src/admin/pages/confirmacao_realizada.php'
    : '../../public/pages/confirmacao_realizada.php';

header('Location: ' . $destino . '?' . $query);
exit;