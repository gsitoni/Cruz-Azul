<?php
header('Content-Type: text/plain; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Método não permitido';
    exit;
}

$raw = file_get_contents('php://input');
if ($raw === false) {
    http_response_code(400);
    echo 'Falha ao ler o corpo da requisição';
    exit;
}

$in = json_decode($raw, true);
if (!is_array($in) || !isset($in['key'], $in['iv'], $in['data'])) {
    http_response_code(400);
    echo 'JSON inválido ou campos ausentes';
    exit;
}

$key = base64_decode($in['key'], true);
$iv = base64_decode($in['iv'], true);
$data = base64_decode($in['data'], true);
if ($key === false || $iv === false || $data === false) {
    http_response_code(400);
    echo 'Base64 inválido';
    exit;
}

$privPath = __DIR__ . '/../../private.pem';
$priv = file_get_contents($privPath);
if ($priv === false) {
    http_response_code(500);
    echo 'Não foi possível ler a chave privada';
    exit;
}

if (!openssl_private_decrypt($key, $aes, $priv, OPENSSL_PKCS1_OAEP_PADDING)) {
    http_response_code(500);
    echo 'Falha ao abrir a chave AES com RSA';
    exit;
}

if (strlen($data) < 16) {
    http_response_code(400);
    echo 'Dados AES-GCM inválidos';
    exit;
}

$tag = substr($data, -16);
$ciphertext = substr($data, 0, -16);

$texto = openssl_decrypt(
    $ciphertext,
    'aes-256-gcm',
    $aes,
    OPENSSL_RAW_DATA,
    $iv,
    $tag
);

if ($texto === false) {
    http_response_code(500);
    echo 'Falha ao abrir a mensagem AES-GCM';
    exit;
}

echo "texto aberto: $texto\n";

$aes_banco = random_bytes(32);
$iv_banco = random_bytes(12);
$banco = openssl_encrypt(
    $texto,
    'aes-256-gcm',
    $aes_banco,
    OPENSSL_RAW_DATA,
    $iv_banco,
    $tag_banco
);

if ($banco === false) {
    http_response_code(500);
    echo 'Falha ao recifrar o texto para armazenamento';
    exit;
}

$registro = base64_encode($iv_banco . $tag_banco . $banco);
echo "texto recifrado: $registro";
