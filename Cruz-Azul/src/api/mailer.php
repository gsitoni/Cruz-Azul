<?php
// ============================================================
//  mailer.php  –  src/api/mailer.php
//  Sem ?> no final para evitar output acidental
// ============================================================
 
require __DIR__ . '/../../vendor/phpmailer/PHPMailer-master/src/PHPMailer.php';
require __DIR__ . '/../../vendor/phpmailer/PHPMailer-master/src/Exception.php';
require __DIR__ . '/../../vendor/phpmailer/PHPMailer-master/src/SMTP.php';
 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
 
function criarMailer(): PHPMailer {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->CharSet    = 'UTF-8';
    $mail->SMTPDebug  = 0;
    $mail->SMTPAuth   = true;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // 'ssl'
    $mail->Host       = 'smtp.gmail.com';
    $mail->Port       = 465;
    $mail->Username   = 'cruz.azulttggb@gmail.com';
    $mail->Password   = 'qfen qtww axcx teqm';
    $mail->setFrom('cruz.azulttggb@gmail.com', 'Cruz Azul');
    return $mail;
}
 
function enviarEmailConfirmacao(string $emailDestino, string $nomeDestino, string $token): bool {
    try {
        $mail = criarMailer();
 
        $link = 'http://localhost/Cruz-Azul/src/api/confirmar.php?token=' . urlencode($token);
 
        $mail->addAddress($emailDestino, $nomeDestino);
        $mail->Subject = 'Confirme seu cadastro – Cruz Azul';
        $mail->msgHTML(
            "<h2>Bem-vindo à Cruz Azul!</h2>
             <p>Olá, <strong>" . htmlspecialchars($nomeDestino, ENT_QUOTES, 'UTF-8') . "</strong>!</p>
             <p>Clique no botão abaixo para confirmar seu e-mail e ativar sua conta:</p>
             <p><a href='{$link}' style='background:#007bff;color:#fff;padding:10px 20px;
                text-decoration:none;border-radius:5px;display:inline-block;'>
                Confirmar cadastro
             </a></p>
             <p style='color:#999;font-size:12px;'>Se você não se cadastrou, ignore este e-mail.</p>"
        );
 
        $mail->send();
        return true;
 
    } catch (Exception $e) {
        error_log("mailer.php erro ao enviar para {$emailDestino}: " . $e->getMessage());
        return false;
    }
}
 
function enviarEmailRecuperacao(string $emailDestino, string $nomeDestino, string $token): bool {
    try {
        $mail = criarMailer();
 
        $link = 'http://localhost/Cruz-Azul/public/pages/redefinicao_de_senha.php?token=' . urlencode($token);
 
        $mail->addAddress($emailDestino, $nomeDestino);
        $mail->Subject = 'Redefinição de senha – Cruz Azul';
        $mail->msgHTML(
            "<h2>Redefinição de senha</h2>
             <p>Olá, <strong>" . htmlspecialchars($nomeDestino, ENT_QUOTES, 'UTF-8') . "</strong>!</p>
             <p>Clique no botão abaixo para redefinir sua senha. O link expira em <strong>1 hora</strong>.</p>
             <p><a href='{$link}' style='background:#dc3545;color:#fff;padding:10px 20px;
                text-decoration:none;border-radius:5px;display:inline-block;'>
                Redefinir senha
             </a></p>
             <p style='color:#999;font-size:12px;'>Se você não solicitou isso, ignore este e-mail.</p>"
        );
 
        $mail->send();
        return true;
 
    } catch (Exception $e) {
        error_log("mailer.php erro recuperação para {$emailDestino}: " . $e->getMessage());
        return false;
    }
}
 