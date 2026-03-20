<?php

require "PHPMailer/src/PHPMailer.php";
require "PHPMailer/src/SMTP.php";
require "PHPMailer/src/Exception.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception; 

$mail = new PHPMailer();

$email = $_Post ["email"];

$token = bin2hex(random_bytes(32));
$expira = date("Y-m-d H:i:s", strtotime("+1 hour"));

//criar uma pagina para que possa rezetar a senha

$mail -> Mailer = "smtp";
$mail -> IsSMTP();
$mail -> CharSet = "UTF-8";
$mail -> SMTPDebug = 0;
$mail -> SMTPSecure = "ssl";
$mail -> SMTPAuth = true;
$mail -> Host = 'smtp.gmail.com';
$mail -> Port = 465;


$mail -> Username = "cruzazulttggb@gmail.com";
$mail -> Password = "ldtl ftxm phru mjpu";
$mail -> SetFrom ('cruzazulttggb@gmail.com', "Cruz Azul");

$mail -> addADDress ("tomasklinguilgen@gamil.com", "");

$mail -> Subject = "Light";
$mail -> msgHTML ("
                <h1>para recuperar a senha clique no link a baixo</h1>
                <h1></h1>
                <h1></h1>
                <h1></h1>
                ");
$mail -> send();

if ($mail-> send()) {
    echo "enviado com sucesso ";
}
else{
    echo "falha ao enviar";
}

