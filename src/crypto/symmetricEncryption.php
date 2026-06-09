<?php

function encryptData($data, $key)
{
    $iv = random_bytes(16);

    $encrypted = openssl_encrypt(
        $data,
        'AES-256-CBC',
        hex2bin($key),
        OPENSSL_RAW_DATA,
        $iv
    );

    return base64_encode($iv . $encrypted);
}

function decryptData($encryptedData, $key)
{
    $data = base64_decode($encryptedData);

    $iv = substr($data, 0, 16);

    $ciphertext = substr($data, 16);

    return openssl_decrypt(
        $ciphertext,
        'AES-256-CBC',
        hex2bin($key),
        OPENSSL_RAW_DATA,
        $iv
    );
}