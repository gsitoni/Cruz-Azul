<?php
require_once __DIR__ . '/qrcode.php';
require_once __DIR__ . '/../database.php';
session_start();

if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['id_usuario'])) {
    http_response_code(401);
    exit;
}

$app = 'cruzazul';
$id = (int) $_SESSION['usuario']['id_usuario'];

$sql = "SELECT email FROM usuario WHERE id_usuario = ? LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

$dados = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dados || empty($dados['email'])) {
    http_response_code(404);
    exit;
}

$email = $dados['email'];
$secret = $_GET['secret'] ?? '';

if ($secret === '') {
    http_response_code(400);
    exit;
}

$url = 'otpauth://totp/' . urlencode($app . ':' . $email)
    . '?secret=' . rawurlencode($secret)
    . '&issuer=' . urlencode($app);

$options = [
    'w' => 200,
    'h' => 200
];

$generator = new QRCode($url, $options);
$generator->output_image();
?>
