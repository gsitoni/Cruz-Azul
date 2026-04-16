<?php
require_once __DIR__ . '/qrcode.php';
require_once __DIR__ . '/../../database.php';
session_start();

$app = "cruzazul";

if (!isset($_SESSION['usuario']['id_usuario'])) {
    http_response_code(401);
    exit;
}

$id = $_SESSION['usuario']['id_usuario'];

$sql = "SELECT email FROM usuario WHERE id_usuario = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

$dados = $stmt->fetch(PDO::FETCH_ASSOC);
$email = $dados['email'];

$secret = $_GET['secret'] ?? '';

if ($secret === '') {
    http_response_code(400);
    exit;
}

$url = 'otpauth://totp/' . urlencode($app . ":" . $email)
    . "?secret=" . $secret
    . "&issuer=" . urlencode($app);

$options = [
    'w' => 200,
    'h' => 200
];

$generator = new QRCode($url, $options);
$generator->output_image();
?>
