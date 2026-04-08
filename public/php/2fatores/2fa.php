<?php

// GERAR SECRET
function gerarSecret($tamanho = 16) {
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";
    $secret = '';
    
    for ($i = 0; $i < $tamanho; $i++) {
        $secret .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $secret;
}

$secret = gerarSecret();


// CONVERTER BASE32 (CORRIGIDO)
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

// VERIFICAÇÃO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = $_POST['codigo'];

    if (verificarTOTP($secret, $codigo)) {
        echo "Código válido - login autorizado<br>";
    } else {
        echo "Código inválido<br>";
    }
}
?>

<img src="geraqr.php?secret=<?php echo $secret; ?>">

<form method="POST">
    <input type="text" name="codigo" placeholder="Digite o código">
    <button type="submit">Verificar</button>
</form>
