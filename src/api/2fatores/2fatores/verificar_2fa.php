<?php
session_start();
require_once __DIR__ . '/../../database.php';

// CONVERTER BASE32
function b32Decode($b32) {
    $alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";
    $b32 = strtoupper($b32);

    $binary = '';
    foreach (str_split($b32) as $char) {
        $pos = strpos($alphabet, $char);
        if ($pos !== false) {
            $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
    }

    $result = '';
    foreach (str_split($binary, 8) as $byte) {
        if(strlen($byte) == 8) {
            $result .= chr(bindec($byte));
        }
    }
    return $result;
}

// GERAR TOTP
function gerarTOTP($secret) {
    $key = b32Decode($secret);
    $timeSlice = floor(time() / 30);

    $time = pack('N*', 0) . pack('N*', $timeSlice);

    $hash = hash_hmac('sha1', $time, $key, true);
    $offset = ord(substr($hash, -1)) & 0x0F;

    $truncatedHash =
        ((ord($hash[$offset]) & 0x7F) << 24) |
        ((ord($hash[$offset + 1]) & 0xFF) << 16) |
        ((ord($hash[$offset + 2]) & 0xFF) << 8) |
        (ord($hash[$offset + 3]) & 0xFF);

    $code = $truncatedHash % 1000000;

    return str_pad($code, 6, '0', STR_PAD_LEFT);
}

// VERIFICAR TOTP
function verificarTOTP($secret, $codigoUsuario, $tolerancia = 1) {
    $timeSlice = floor(time() / 30);

    for ($i = -$tolerancia; $i <= $tolerancia; $i++) {

        $key = b32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlice + $i);

        $hash = hash_hmac('sha1', $time, $key, true);
        $offset = ord(substr($hash, -1)) & 0x0F;

        $truncatedHash =
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF);

        $code = str_pad($truncatedHash % 1000000, 6, '0', STR_PAD_LEFT);

        if ($code === $codigoUsuario) {
            return true;
        }
    }

    return false;
}

if (!isset($_SESSION['usuario']['id_usuario'])) {
    header("Location: ../../../public/pages/login.php");
    exit;
}

//  Pegar secret do banco de dados
$id = $_SESSION['usuario']['id_usuario'];

$sql = "SELECT chave_2fa FROM usuario WHERE id_usuario = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

$dados = $stmt->fetch(PDO::FETCH_ASSOC);

$secret = $dados['chave_2fa'];


//  VERIFICAÇÃO

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = $_POST['codigo'];

    if (verificarTOTP($secret, $codigo)) {

        // marca que passou no 2FA
        $_SESSION['2fa_ok'] = true;

        header("Location: ../../../public/pages/home_usuario.php");
        exit;

    } else {
        echo "Código inválido<br>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verificar 2FA</title>
</head>
<body>

<h2>Digite o código do autenticador</h2>

<form method="POST">
    <input type="text" name="codigo" placeholder="Digite o código" required>
    <button type="submit">Verificar</button>
</form>

</body>
</html>