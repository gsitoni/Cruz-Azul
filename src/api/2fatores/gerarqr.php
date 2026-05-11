<?php
require __DIR__ . '/qrcode.php';
session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$secret = strtoupper(trim((string) ($_GET['secret'] ?? ($_SESSION['2fa_secret_temp'] ?? ''))));
$email = trim((string) ($_GET['email'] ?? ($_SESSION['usuario']['email'] ?? '')));

if ($secret === '' || !preg_match('/^[A-Z2-7]+$/', $secret)) {
    http_response_code(400);
    exit('Secret invalido.');
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $email = 'usuario';
}

$app = 'CruzAzul';
$issuer = $app;
$label = rawurlencode($app . ':' . $email);
$url = 'otpauth://totp/' . $label
    . '?secret=' . rawurlencode($secret)
    . '&issuer=' . rawurlencode($issuer)
    . '&algorithm=SHA1&digits=6&period=30';

function outputQrSvg(string $data, int $size = 240): void
{
    $generator = new QRCode($data, ['w' => $size, 'h' => $size, 's' => 'qrl']);
    $reflection = new ReflectionClass($generator);
    $method = $reflection->getMethod('encode_and_calculate_size');
    $method->setAccessible(true);
    [$code] = $method->invoke($generator, $data, ['w' => $size, 'h' => $size, 's' => 'qrl']);

    $modules = $code['b'];
    $moduleCount = count($modules);
    $quietZone = 4;
    $viewBoxSize = $moduleCount + ($quietZone * 2);

    header('Content-Type: image/svg+xml; charset=UTF-8');

    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 ' . $viewBoxSize . ' ' . $viewBoxSize . '" shape-rendering="crispEdges">';
    echo '<rect width="100%" height="100%" fill="#ffffff"/>';

    foreach ($modules as $y => $row) {
        foreach ($row as $x => $value) {
            if ($value) {
                echo '<rect x="' . ($x + $quietZone) . '" y="' . ($y + $quietZone) . '" width="1" height="1" fill="#111827"/>';
            }
        }
    }

    echo '</svg>';
}

try {
    $generator = new QRCode($url, [
        'w' => 240,
        'h' => 240,
    ]);

    if (function_exists('imagecreatetruecolor')) {
        $generator->output_image();
        exit;
    }

    outputQrSvg($url);
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Falha ao gerar QR Code.');
}
