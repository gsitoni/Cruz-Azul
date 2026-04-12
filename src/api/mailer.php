<?php

require "PHPMailer/src/PHPMailer.php";
require "PHPMailer/src/Exception.php";
require "PHPMailer/src/SMTP.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function criarMailer(): PHPMailer{ 
    $mail = new PHPMailer();
    $mail->Mailer = "smtp";
    $mail->IsSMTP();
    $mail->CharSet = "UTF-8";
    $mail->SMTPDebug = 0;
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = 'ssl';
    $mail->Host = 'smtp.gmail.com';
    $mail-> Port = 465;
    $mail->Username = "cruz.azulttggb@gmail.com";
    $mail->Password = "qfen qtww axcx teqm";
    $mail->SetFrom('cruz.azulttggb@gmail.com', "Ablublublé");
    return $mail;
}
function enviarEmailConfirmacao(string $emailDestino, string $nomeDestino, string $token) : bool{ 
    
    $mail = criarMailer();

    $link = "http://localhost/site-experiencia-criativa/Cruz-Azul/test_email_bismark/confirmar.php?token=" . urlencode($token);

    $mail ->addAddress($emailDestino, $nomeDestino);
    $mail->Subject = "Confirmacao";
    $mail-> msgHTML("<h1>Validar Conta</h1>
                    <p>Para ativar clique:</p>
                    <a href = '{$link}'> Confirmar cadastro </a>");

    try{
        $mail -> send();
        return true;
    } catch (Exception $e){
        error_log("Erro ao enviar email" . $mail->ErrorInfo);
        return false;
    }


}
?>