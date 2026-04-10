<?php
include("qrcode.php");

$app = "MeuSistema";
$usuario = "user@email.com";
$secret = $_GET["secret"];
$url = 'otpauth://totp/' . urlencode($app . ":" . $usuario)
    . "?secret=" . $secret
    . "&issuer=" . urlencode($app);

$options = [
    'w' => 200,
    'h' => 200
];

$generator = new QRCode($url, $options);
$generator->output_image();
?>
