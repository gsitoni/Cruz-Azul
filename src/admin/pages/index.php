<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// ======================================
// RESET
// ======================================

if (!empty($_GET['reset'])) {

    session_unset();

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