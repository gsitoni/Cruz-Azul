<?php
require_once "mailer.php"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $token = bin2hex(random_bytes(32));
    
    $link = "http://seusite.com/pages/redefinicao_de_senha.html?token=" . $token;

    if (enviarEmailRecuperacao($email, $link)) {
        header("Location: ../pages/codigo_de_verificacao.html");
        exit();
    } else {
        echo "Erro ao enviar o e-mail.";
    }
}