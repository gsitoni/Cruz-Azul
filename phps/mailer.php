<?php
require "../PHPMailer/src/PHPMailer.php";
require "../PHPMailer/src/SMTP.php";
require "../PHPMailer/src/Exception.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviarEmailRecuperacao($email_destino, $link_recuperacao) {
    $mail = new PHPMailer(true);

    try {
        
        $mail->isSMTP();
        $mail->CharSet    = "UTF-8";
        $mail->Host       = 'smtp.gmail.com';
        $mail->Port       = 465;
        $mail->SMTPSecure = "ssl";
        $mail->SMTPAuth   = true;

        $mail->Username   = "cruzazulttggb@gmail.com";
        $mail->Password   = "ldtl ftxm phru mjpu"; 
        
        $mail->setFrom('cruzazulttggb@gmail.com', "Cruz Azul");
        $mail->addAddress($email_destino);

        $mail->isHTML(true);
        $mail->Subject = "Recuperação de Senha - Cruz Azul";
        $mail->Body    = "
            <div style='font-family: sans-serif; border: 1px solid #ddd; padding: 20px;'>
                <h2>Recuperação de Senha</h2>
                <p>Você solicitou a redefinição de senha para sua conta na <b>Cruz Azul</b>.</p>
                <p>Clique no link abaixo para prosseguir:</p>
                <a href='$link_recuperacao' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Redefinir Senha</a>
                <p>Este link expira em 1 hora.</p>
            </div>";

        return $mail->send();
    } catch (Exception $e) {
        return false;
    }
}