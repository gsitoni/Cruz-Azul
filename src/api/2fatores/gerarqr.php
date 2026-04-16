<?php
require __DIR__ . '/qrcode.php';
require __DIR__ . '/../database.php';

session_start();

if (empty($_SESSION['usuario']['id_usuario'])) {
    http_response_code(403);
    exit('Sessão inválida.');
}

$secret = strtoupper(trim((string) ($_GET['secret'] ?? '')));
if ($secret === '' || !preg_match('/^[A-Z2-7]+$/', $secret)) {
    http_response_code(400);
    exit('Secret inválido.');
}

$app = 'Cruz Azul';
$idUsuario = (int) $_SESSION['usuario']['id_usuario'];
$email = (string) ($_SESSION['usuario']['email'] ?? '');

if ($email === '') {
    $stmt = $pdo->prepare('SELECT email FROM usuario WHERE id_usuario = ? LIMIT 1');
    $stmt->execute([$idUsuario]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);
    $email = (string) ($dados['email'] ?? '');
}

if ($email === '') {
    http_response_code(404);
    exit('Usuário não encontrado.');
}

$url = 'otpauth://totp/' . rawurlencode($app . ':' . $email)
    . '?secret=' . rawurlencode($secret)
    . '&issuer=' . rawurlencode($app);

$generator = new QRCode($url, [
    'w' => 200,
    'h' => 200,
]);

$generator->output_image();
?>
