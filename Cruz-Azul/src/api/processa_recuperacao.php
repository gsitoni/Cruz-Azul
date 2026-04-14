<?php
// ============================================================
//  processa_recuperacao.php  –  src/api/
//  Segurança: CSRF, rate-limit, session segura, XSS
// ============================================================
$secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
 
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => $secure,
    'httponly' => true,
    'samesite' => 'Lax'
]);
 
session_start();
 
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
 
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../public/pages/recuperacao_de_senha.php');
    exit();
}
 
// CSRF
if (
    empty($_SESSION['csrf_token']) ||
    empty($_POST['csrf_token'])    ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    http_response_code(403);
    die("Requisição inválida.");
}
 
// Rate-limit: máximo 5 tentativas por 10 minutos
$agora = time();
if (!isset($_SESSION['rec_ts'])) {
    $_SESSION['rec_ts']    = $agora;
    $_SESSION['rec_count'] = 0;
}
if (($agora - $_SESSION['rec_ts']) > 600) {
    $_SESSION['rec_ts']    = $agora;
    $_SESSION['rec_count'] = 0;
}
$_SESSION['rec_count']++;
if ($_SESSION['rec_count'] > 5) {
    header('Location: ../../public/pages/recuperacao_de_senha.php?status=erro');
    exit();
}
 
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/mailer.php';
 
$email = filter_input(INPUT_POST, 'email_recuperacao', FILTER_SANITIZE_EMAIL);
 
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../../public/pages/recuperacao_de_senha.php?status=erro');
    exit();
}
 
// Verifica se email existe — mas sempre redireciona igual (evita enumeração)
$stmt = $pdo->prepare("SELECT id_usuario, nome FROM usuario WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);
 
if ($usuario) {
    $codigo = random_int(100000, 999999);
 
    $_SESSION['codigo_recuperacao'] = $codigo;
    $_SESSION['email_recuperacao']  = $email;
    $_SESSION['expira_codigo']      = $agora + 300; // 5 minutos
 
    // Envia email com código
    enviarCodigoRecuperacao($email, $usuario['nome'] ?? 'Usuário', $codigo);
}
 
// Redireciona sempre para evitar enumeração de emails
header('Location: ../../public/pages/codigo_de_verificacao.php');
exit();