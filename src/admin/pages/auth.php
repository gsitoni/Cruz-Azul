<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

function usuarioEhAdmin(): bool {
    if (empty($_SESSION['usuario']) || !is_array($_SESSION['usuario'])) {
        return false;
    }

    $tipo = (string) ($_SESSION['usuario']['tipo'] ?? '');

    return stripos($tipo, 'admin') !== false;
}

if (!usuarioEhAdmin()) {
    header('Location: ../../../public/pages/login.php');
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
