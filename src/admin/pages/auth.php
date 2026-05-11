<?php

// =====================================
// CONFIG SESSÃO SEGURA
// =====================================

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// =====================================
// HEADERS SEGURANÇA
// =====================================

header('X-Content-Type-Options: nosniff');

header('X-Frame-Options: DENY');

header('X-XSS-Protection: 1; mode=block');

header(
    'Strict-Transport-Security: max-age=31536000; includeSubDomains'
);

// =====================================
// URL LOGIN
// =====================================

function urlLoginAdmin(): string {

    return '../index.php';
}

// =====================================
// VERIFICA LOGIN
// =====================================

function usuarioLogado(): bool {

    return !empty($_SESSION['usuario']);
}

// =====================================
// VERIFICA ADMIN
// =====================================

function usuarioEhAdmin(): bool {

    if (
        empty($_SESSION['usuario']) ||
        !is_array($_SESSION['usuario'])
    ) {

        return false;
    }

    return (
        ($_SESSION['usuario']['tipo'] ?? '') === 'admin'
    );
}

// =====================================
// VERIFICA TELEGRAM
// =====================================

function adminAutenticadoTelegram(): bool {

    return !empty($_SESSION['admin_autenticado']);
}

// =====================================
// LIMPA SESSÃO
// =====================================

function destruirSessao(): void {

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}

// =====================================
// NÃO LOGADO
// =====================================

if (!usuarioLogado()) {

    destruirSessao();

    header('Location: ' . urlLoginAdmin());

    exit();
}

// =====================================
// NÃO ADMIN
// =====================================

if (!usuarioEhAdmin()) {

    destruirSessao();

    header('Location: ' . urlLoginAdmin());

    exit();
}

// =====================================
// NÃO AUTENTICOU TELEGRAM
// =====================================

if (!adminAutenticadoTelegram()) {

    destruirSessao();

    header('Location: ' . urlLoginAdmin());

    exit();
}

// =====================================
// CSRF TOKEN
// =====================================

if (empty($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] =
        bin2hex(random_bytes(32));
}