<?php
// ============================================================
//  logout.php  –  public/pages/logout.php
// ============================================================
session_start();
 
// Apaga todos os dados da sessão
$_SESSION = [];
 
// Destrói o cookie de sessão no navegador
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}
 
session_destroy();
 
header('Location: login.php');
exit;
 