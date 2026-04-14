<?php
include("qrcode.php");
include("../database.php");
session_start();

$app = "cruzazul";
$id = $_SESSION['id_usuario'];

$sql = "SELECT email FROM usuario WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

$dados = $stmt->fetch(PDO::FETCH_ASSOC);
$email = $dados['email'];

$secret = $_GET["secret"];
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
