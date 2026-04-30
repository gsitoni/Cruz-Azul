<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!empty($_GET['reset'])) {
    unset($_SESSION['usuario'], $_SESSION['2fa_ok'], $_SESSION['2fa_pendente'], $_SESSION['2fa_secret_temp'], $_SESSION['csrf_token']);
    header('Location: ./pages/login.php?reset=1');
    exit();
}

$ehAdmin = !empty($_SESSION['usuario']['tipo']) && stripos((string) $_SESSION['usuario']['tipo'], 'admin') !== false;
$doisFatoresOk = !empty($_SESSION['2fa_ok']);
$doisFatoresPendente = !empty($_SESSION['2fa_pendente']);

if ($ehAdmin || $doisFatoresOk || $doisFatoresPendente) {
    unset($_SESSION['usuario'], $_SESSION['2fa_ok'], $_SESSION['2fa_pendente'], $_SESSION['2fa_secret_temp'], $_SESSION['csrf_token']);
}

header('Location: ./pages/login.php?reset=1');
exit();
