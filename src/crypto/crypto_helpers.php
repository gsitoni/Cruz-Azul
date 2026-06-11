<?php
// ============================================================
//  crypto_helpers.php  —  src/crypto/crypto_helpers.php
//  Funções de cifragem/decifragem AES-GCM + RSA-OAEP
// ============================================================

function getPrivKey(): string {
    return file_get_contents(__DIR__ . '/private.pem');
}

function getPubKey(): string {
    $priv = openssl_pkey_get_private(getPrivKey());
    return openssl_pkey_get_details($priv)['key'];
}

// Cifra string com AES-256-GCM. Retorna base64(cifrado+tag) e preenche $iv (base64)
function aesCifrar(string $dado, string $chave, string &$iv): string {
    $ivBytes = random_bytes(12);
    $tag     = '';
    $enc     = openssl_encrypt($dado, 'aes-256-gcm', $chave, OPENSSL_RAW_DATA, $ivBytes, $tag);
    $iv      = base64_encode($ivBytes);
    return base64_encode($enc . $tag);
}

// Decifra base64(cifrado+tag) com AES-256-GCM
function aesDecifrar(string $cifradoB64, string $chave, string $ivB64): string|false {
    $raw     = base64_decode($cifradoB64);
    $iv      = base64_decode($ivB64);
    $tag     = substr($raw, -16);
    $cifrado = substr($raw, 0, -16);
    if (strlen($cifrado) === 0) return false;
    return openssl_decrypt($cifrado, 'aes-256-gcm', $chave, OPENSSL_RAW_DATA, $iv, $tag);
}

// Cifra chave AES (32 bytes raw) com RSA público
function rsaCifrarChave(string $chaveRaw): string {
    $enc = '';
    openssl_public_encrypt($chaveRaw, $enc, getPubKey(), OPENSSL_PKCS1_OAEP_PADDING);
    return base64_encode($enc);
}

// Decifra chave AES com RSA privado
function rsaDecifrarChave(string $chaveB64): string|false {
    $enc   = base64_decode($chaveB64);
    $plain = '';
    $ok    = openssl_private_decrypt($enc, $plain, getPrivKey(), OPENSSL_PKCS1_OAEP_PADDING);
    return $ok ? $plain : false;
}

// Cifra todos os campos de um doador e retorna array com dados cifrados
function cifrarDoador(array $dados): array {
    $chave  = random_bytes(32);
    $campos = ['nome', 'cpf', 'telefone', 'data_nascimento'];
    $result = [];
    $ivs    = [];

    foreach ($campos as $campo) {
        $valor = (string)($dados[$campo] ?? '');
        if ($valor !== '') {
            $iv             = '';
            $result[$campo] = aesCifrar($valor, $chave, $iv);
            $ivs[]          = $iv;
        } else {
            $result[$campo] = '';
            $ivs[]          = 'x';
        }
    }

    // Sempre 4 partes: iv_nome|iv_cpf|iv_telefone|iv_data_nascimento
    $result['iv_dados']          = implode('|', $ivs);
    $result['chave_aes_cifrada'] = rsaCifrarChave($chave);

    logCrypto('[cifrarDoador] iv_dados=' . $result['iv_dados']);

    return $result;
}

// Decifra campos de um doador (modifica array in-place)
function decifrarDoador(array &$doador): void {
    if (empty($doador['chave_aes_cifrada']) || empty($doador['iv_dados'])) {
        logCrypto('[decifrarDoador] ABORTADO: chave ou iv_dados vazio');
        return;
    }

    $chave = rsaDecifrarChave($doador['chave_aes_cifrada']);
    if ($chave === false) {
        logCrypto('[decifrarDoador] ABORTADO: RSA falhou ao decifrar chave');
        return;
    }

    $ivs    = explode('|', $doador['iv_dados']);
    $campos = ['nome', 'cpf', 'telefone', 'data_nascimento'];

    logCrypto('[decifrarDoador] iv_count=' . count($ivs) . ' iv_dados=' . $doador['iv_dados']);

    foreach ($campos as $i => $campo) {
        $iv  = $ivs[$i] ?? 'x';
        $val = $doador[$campo] ?? '';
        if ($iv === 'x' || $val === '' || $val === null) {
            logCrypto("[decifrarDoador] campo=$campo PULADO (iv=$iv)");
            continue;
        }
        $plain = aesDecifrar($val, $chave, $iv);
        if ($plain !== false) {
            $doador[$campo] = $plain;
            logCrypto("[decifrarDoador] campo=$campo OK");
        } else {
            logCrypto("[decifrarDoador] campo=$campo FALHOU decifragem");
        }
    }
}

// Cifra todos os campos de uma ONG e retorna array com dados cifrados
// Campos: nome, email, area_atuacao, cidade, endereco, descricao, localizacao
function cifrarOng(array $dados): array {
    $chave  = random_bytes(32);
    $campos = ['nome', 'email', 'area_atuacao', 'cidade', 'endereco', 'descricao', 'localizacao'];
    $result = [];
    $ivs    = [];

    foreach ($campos as $campo) {
        $valor = (string)($dados[$campo] ?? '');
        if ($valor !== '') {
            $iv             = '';
            $result[$campo] = aesCifrar($valor, $chave, $iv);
            $ivs[]          = $iv;
        } else {
            $result[$campo] = '';
            $ivs[]          = 'x';
        }
    }

    // 7 partes: iv_nome|iv_email|iv_area|iv_cidade|iv_endereco|iv_descricao|iv_localizacao
    $result['iv_dados']          = implode('|', $ivs);
    $result['chave_aes_cifrada'] = rsaCifrarChave($chave);

    logCrypto('[cifrarOng] iv_dados=' . $result['iv_dados']);

    return $result;
}

// Decifra campos de uma ONG (modifica array in-place)
function decifrarOng(array &$ong): void {
    if (empty($ong['chave_aes_cifrada']) || empty($ong['iv_dados'])) {
        logCrypto('[decifrarOng] ABORTADO: chave ou iv_dados vazio');
        return;
    }

    $chave = rsaDecifrarChave($ong['chave_aes_cifrada']);
    if ($chave === false) {
        logCrypto('[decifrarOng] ABORTADO: RSA falhou ao decifrar chave');
        return;
    }

    $ivs    = explode('|', $ong['iv_dados']);
    $campos = ['nome', 'email', 'area_atuacao', 'cidade', 'endereco', 'descricao', 'localizacao'];

    logCrypto('[decifrarOng] iv_count=' . count($ivs) . ' iv_dados=' . $ong['iv_dados']);

    foreach ($campos as $i => $campo) {
        $iv  = $ivs[$i] ?? 'x';
        $val = $ong[$campo] ?? '';
        if ($iv === 'x' || $val === '' || $val === null) {
            logCrypto("[decifrarOng] campo=$campo PULADO (iv=$iv)");
            continue;
        }
        $plain = aesDecifrar($val, $chave, $iv);
        if ($plain !== false) {
            $ong[$campo] = $plain;
            logCrypto("[decifrarOng] campo=$campo OK");
        } else {
            logCrypto("[decifrarOng] campo=$campo FALHOU decifragem");
        }
    }
}

// Log no formato S.3.1f / S.3.2d
function logCrypto(string $msg): void {
    error_log(get_current_user() . ':' . gethostname() . '>' . $msg);
}

// ── Decifra pacote JSON do front (S.3.1) ────────────────────
function decifrarPostHibrido(?string $rawInput = null): array|false {
    $in = json_decode($rawInput ?? file_get_contents('php://input'), true);
    if (!isset($in['key'], $in['iv'], $in['data'])) return false;

    $keyBytes  = base64_decode($in['key']);
    $ivBytes   = base64_decode($in['iv']);
    $dataBytes = base64_decode($in['data']);

    $aes = '';
    $ok  = openssl_private_decrypt($keyBytes, $aes, getPrivKey(), OPENSSL_PKCS1_OAEP_PADDING);
    if (!$ok) {
        logCrypto('[decifrarPostHibrido] RSA falhou');
        return false;
    }
    logCrypto('[CRYPTO] chave AES decifrada com RSA-OAEP');

    $tag     = substr($dataBytes, -16);
    $cifrado = substr($dataBytes, 0, -16);
    $json    = openssl_decrypt($cifrado, 'aes-256-gcm', $aes, OPENSSL_RAW_DATA, $ivBytes, $tag);
    if ($json === false) {
        logCrypto('[decifrarPostHibrido] AES falhou');
        return false;
    }
    logCrypto('[CRYPTO] dados decifrados com AES-GCM');

    return json_decode($json, true) ?? false;
}