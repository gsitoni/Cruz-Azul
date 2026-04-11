<?php

$secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Acesso inválido.");
}


if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Requisição inválida.");
}

$email = filter_input(INPUT_POST, 'email_recuperacao', FILTER_SANITIZE_EMAIL);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../pages/recuperacao_de_senha.php?status=erro");
    exit();
}

$codigo = random_int(100000, 999999); // melhor que rand()

$_SESSION['codigo_recuperacao'] = $codigo;
$_SESSION['email_recuperacao'] = $email;
$_SESSION['expira_codigo'] = time() + 300; // 5 minutos

// ==========================
// COOKIE (backup)
// ==========================
setcookie("codigo_recuperacao", $codigo, [
    'expires' => time() + 300,
    'path' => '/',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Strict'
]);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    // ==========================
    // CONFIG SMTP (GMAIL)
    // ==========================
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;

    $mail->Username = 'SEU_EMAIL@gmail.com'; // seu email
    $mail->Password = 'SUA_SENHA_DE_APP';    // senha de app

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // ==========================
    // REMETENTE
    // ==========================
    $mail->setFrom('SEU_EMAIL@gmail.com', 'Cruz Azul');

    // ==========================
    // DESTINATÁRIO
    // ==========================
    $mail->addAddress($email);

    // ==========================
    // CONTEÚDO
    // ==========================
    $mail->isHTML(true);
    $mail->Subject = 'Código de verificação - Cruz Azul';

    $mail->Body = "
        <h2>Recuperação de senha</h2>
        <p>Seu código de verificação é:</p>
        <h1 style='color:#0d47a1;'>$codigo</h1>
        <p>Este código expira em 5 minutos.</p>
        <hr>
        <p style='font-size:12px;color:gray;'>Se você não solicitou, ignore este email.</p>
    ";

    $mail->send();

} catch (Exception $e) {
    // NÃO mostra erro pro usuário (segurança)
    error_log("Erro ao enviar email: " . $mail->ErrorInfo);
}

header("Location: ../pages/codigo_de_verificacao.php");
exit();