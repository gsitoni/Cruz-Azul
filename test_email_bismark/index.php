<?php

require "PHPMailer/src/PHPMailer.php";
require "PHPMailer/src/Exception.php";
require "PHPMailer/src/SMTP.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

$mail-> addAddress("tomasklinguilgen@gmail.com", "");

while (true){ 

$mail->Subject = "Light";
$mail->msgHTML("<h1>Absolute cinema</h1>");
$mail->send();

if ($mail->send()){
    echo "sucesso";
}
else {
    echo "falha";
}
}
?>