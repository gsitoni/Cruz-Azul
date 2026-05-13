<?php

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Strict',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// ======================================
// RESET
// ======================================

if (!empty($_GET['reset'])) {

    session_unset();

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Strict',
        ]);
    }

    session_destroy();

    header('Location: ./login.php');

    exit();
}

// ======================================
// ADMIN LOGADO
// ======================================

$adminLogado =
    !empty($_SESSION['usuario']) &&
    !empty($_SESSION['admin_autenticado']);

// ======================================
// REDIRECIONA
// ======================================

if ($adminLogado) {

    header('Location: ./dashboard.php');

    exit();
}

// ======================================
// LOGIN
// ======================================

header('Location: ./login.php');

exit();
