<?php

require __DIR__ . '/../../vendor/phpmailer/PHPMailer-master/src/PHPMailer.php';
require __DIR__ . '/../../vendor/phpmailer/PHPMailer-master/src/Exception.php';
require __DIR__ . '/../../vendor/phpmailer/PHPMailer-master/src/SMTP.php';

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
    $mail->Port = 465;
    $mail->Username = "cruz.azulttggb@gmail.com";
    $mail->Password = "qfen qtww axcx teqm";
    $mail->SetFrom('cruz.azulttggb@gmail.com', "Cruz Azul");
    return $mail;
}
function enviarEmailConfirmacao(string $emailDestino, string $nomeDestino, string $token) : bool{ 
    
    $mail = criarMailer();

    $link = "http://localhost/Cruz-Azul/src/api/confirmar.php?token=" . urlencode($token);

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

function enviarEmailConfirmacao(string $para, string $nome, string $token): bool {
    $link = 'http://localhost/Cruz-Azul/public/pages/confirmacao_cadastro.php?token=' . urlencode($token);
    $nome_safe = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');

    $corpo = "
        <h2>Bem-vindo à Cruz Azul!</h2>
        <p>Olá, <strong>{$nome_safe}</strong>!</p>
        <p>Clique no botão abaixo para confirmar seu e-mail e ativar sua conta:</p>
        <p>
            <a href='{$link}' style='background:#007bff;color:#fff;padding:10px 20px;
               text-decoration:none;border-radius:5px;display:inline-block;'>
               Confirmar cadastro
            </a>
        </p>
        <p style='color:#999;font-size:12px;'>Se você não se cadastrou, ignore este e-mail.</p>
    ";

    return enviarEmail($para, $nome, 'Confirme seu cadastro – Cruz Azul', $corpo);
}

function enviarEmailRecuperacao(string $para, string $nome, string $token): bool {
    $link = 'http://localhost/Cruz-Azul/public/pages/redefinicao_de_senha.php?token=' . urlencode($token);
    $nome_safe = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');

    $corpo = "
        <h2>Redefinição de senha</h2>
        <p>Olá, <strong>{$nome_safe}</strong>!</p>
        <p>Clique no botão abaixo para redefinir sua senha. O link expira em <strong>1 hora</strong>.</p>
        <p>
            <a href='{$link}' style='background:#dc3545;color:#fff;padding:10px 20px;
               text-decoration:none;border-radius:5px;display:inline-block;'>
               Redefinir senha
            </a>
        </p>
        <p style='color:#999;font-size:12px;'>Se você não solicitou, ignore este e-mail.</p>
    ";

    return enviarEmail($para, $nome, 'Redefinição de senha – Cruz Azul', $corpo);
}

function enviarCodigoRecuperacao(string $para, string $nome, int $codigo): bool {
    $nome_safe = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');

    $corpo = "
        <h2>Recuperação de senha</h2>
        <p>Olá, <strong>{$nome_safe}</strong>!</p>
        <p>Seu código de verificação é:</p>
        <h1 style='color:#0d47a1;letter-spacing:8px;'>{$codigo}</h1>
        <p>Este código expira em <strong>5 minutos</strong>.</p>
        <hr>
        <p style='font-size:12px;color:gray;'>Se você não solicitou, ignore este e-mail.</p>
    ";

    return enviarEmail($para, $nome, 'Código de verificação – Cruz Azul', $corpo);
}
