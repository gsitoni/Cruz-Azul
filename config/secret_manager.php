<?php

/**
 * Gerenciamento simples de segredos com mascara reversivel.
 *
 * Formato suportado:
 * - mask:v1:<base64url(iv|tag|ciphertext)>
 */

if (!function_exists('caSecretSeed')) {
    function caSecretSeed(): string
    {
        $iniSeed = trim((string) ini_get('ca.secret_seed'));
        if ($iniSeed !== '') {
            return $iniSeed;
        }

        $envSeed = trim((string) getenv('CA_SECRET_SEED'));
        if ($envSeed !== '') {
            return $envSeed;
        }

        // Fallback local: evita segredo em texto puro, mas nao substitui um cofre dedicado.
        $fingerprint = implode('|', [
            php_uname('n'),
            php_uname('m'),
            PHP_VERSION,
            __DIR__,
        ]);

        return hash('sha256', 'cruzazul-secret-seed|' . $fingerprint);
    }
}

if (!function_exists('caSecretKey')) {
    function caSecretKey(): string
    {
        return hash('sha256', 'cruzazul-mask-v1|' . caSecretSeed(), true);
    }
}

if (!function_exists('caBase64UrlEncode')) {
    function caBase64UrlEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}

if (!function_exists('caBase64UrlDecode')) {
    function caBase64UrlDecode(string $encoded): string
    {
        $pad = 4 - (strlen($encoded) % 4);
        if ($pad < 4) {
            $encoded .= str_repeat('=', $pad);
        }

        $decoded = base64_decode(strtr($encoded, '-_', '+/'), true);
        if ($decoded === false) {
            throw new RuntimeException('Mascara invalida: base64 corrompido.');
        }

        return $decoded;
    }
}

if (!function_exists('caSecretMask')) {
    function caSecretMask(string $plain): string
    {
        if (!extension_loaded('openssl')) {
            throw new RuntimeException('OpenSSL indisponivel para mascarar segredo.');
        }

        $iv = random_bytes(12);
        $key = caSecretKey();
        $tag = '';

        $cipher = openssl_encrypt(
            $plain,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            'cruzazul:mask:v1'
        );

        if ($cipher === false) {
            throw new RuntimeException('Falha ao mascarar segredo.');
        }

        return 'mask:v1:' . caBase64UrlEncode($iv . $tag . $cipher);
    }
}

if (!function_exists('caSecretUnmask')) {
    function caSecretUnmask(string $masked): string
    {
        if (!extension_loaded('openssl')) {
            throw new RuntimeException('OpenSSL indisponivel para decifrar segredo.');
        }

        if (strpos($masked, 'mask:v1:') !== 0) {
            return $masked;
        }

        $payload = caBase64UrlDecode(substr($masked, 8));

        if (strlen($payload) < 29) {
            throw new RuntimeException('Mascara invalida: payload incompleto.');
        }

        $iv = substr($payload, 0, 12);
        $tag = substr($payload, 12, 16);
        $cipher = substr($payload, 28);

        $plain = openssl_decrypt(
            $cipher,
            'aes-256-gcm',
            caSecretKey(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            'cruzazul:mask:v1'
        );

        if ($plain === false) {
            throw new RuntimeException('Falha ao decifrar segredo. Verifique seed/local da aplicacao.');
        }

        return $plain;
    }
}

if (!function_exists('caSecretResolve')) {
    function caSecretResolve(string $value): string
    {
        if (strpos($value, 'mask:v1:') === 0) {
            return caSecretUnmask($value);
        }

        return $value;
    }
}
