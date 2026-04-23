<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

function urlLoginAdmin(): string {
    return '../index.php';
}

function usuarioEhAdmin(): bool {
    if (empty($_SESSION['usuario']) || !is_array($_SESSION['usuario'])) {
        return false;
    }

    $tipo = (string) ($_SESSION['usuario']['tipo'] ?? '');

    return stripos($tipo, 'admin') !== false;
}

function usuarioConcluiu2FA(): bool {
    return !empty($_SESSION['2fa_ok']);
}

if (!usuarioEhAdmin()) {
    header('Location: ' . urlLoginAdmin());
    exit();
}

if (!usuarioConcluiu2FA()) {
    if (!empty($_SESSION['2fa_pendente'])) {
        header('Location: ../../api/2fatores/verificar_2fa.php');
        exit();
    }

    session_unset();
    session_destroy();
    header('Location: ' . urlLoginAdmin());
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
