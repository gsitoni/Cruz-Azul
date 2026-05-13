<?php

require_once __DIR__ . '/admin_config.php';

$adminConfig = adminConfigCarregar();

// =====================================
// CONFIG SESSÃO SEGURA
// =====================================

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
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

    return stripos((string) ($_SESSION['usuario']['tipo'] ?? ''), 'admin') !== false;
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

$timeoutSessao = (int) ($adminConfig['timeout_sessao'] ?? 3600);
$ultimoAcesso = (int) ($_SESSION['admin_ultimo_acesso'] ?? time());

if ((time() - $ultimoAcesso) > $timeoutSessao) {

    destruirSessao();

    header('Location: ' . urlLoginAdmin());

    exit();
}

$_SESSION['admin_ultimo_acesso'] = time();

if (!usuarioEhAdmin()) {

    destruirSessao();

    header('Location: ' . urlLoginAdmin());

    exit();
}

// =====================================
// NÃO AUTENTICOU TELEGRAM
// =====================================

if (!empty($adminConfig['autenticacao_2fa']) && !adminAutenticadoTelegram()) {

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
