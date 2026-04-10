<?php
session_start();
include('valida_senha.php');

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
require '../../test_email_bismark/database.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!isset($_SESSION['id'])) {
        echo "Usuário não está logado.";
        exit;
    }

    $id_usuario   = $_SESSION['id'];
    $novo_usuario = $_POST['novo_usuario'] ?? '';
    $senha_atual  = $_POST['senha_atual'] ?? '';
    $nova_senha   = $_POST['nova_senha'] ?? '';
    $confirmar    = $_POST['confirmar_senha'] ?? '';
    $codigo_input = $_POST['codigo'] ?? '';

    $campos = [];
    $tipos  = "";
    $valores = [];

    // Trocar usuário
    if (!empty($novo_usuario)) {

        $sql = "SELECT id FROM usuarios WHERE usuario = ? AND id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $novo_usuario, $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "Usuário já existe.";
            exit;
        }

        $campos[] = "usuario = ?";
        $tipos   .= "s";
        $valores[] = $novo_usuario;

        $_SESSION['usuario'] = $novo_usuario;
    }

    // Trocar senha
    if (!empty($nova_senha)) {

        // buscar senha atual
        $sql = "SELECT senha FROM usuarios WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $usuario = $resultado->fetch_assoc();

        if (!password_verify($senha_atual, $usuario['senha'])) {
            echo "Senha atual incorreta!";
            exit;
        }

        if ($nova_senha !== $confirmar) {
            echo "As senhas não coincidem!";
            exit;
        }

        $validacao = validarSenhaForte($nova_senha);
        if ($validacao !== true) {
            echo $validacao;
            exit;
        }

        if (password_verify($nova_senha, $usuario['senha'])) {
            echo "A nova senha não pode ser igual à antiga.";
            exit;
        }

        $nova_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

        $campos[] = "senha = ?";
        $tipos   .= "s";
        $valores[] = $nova_hash;
    }

    //Atualizar dados
    if (empty($campos)){
        echo "Nada para atualizar.";
        exit;
    }

    // Gerar código

    if (empty($codigo_input)) {

        $codigo = rand(100000, 999999);

        $_SESSION['codigo_confirmacao'] = $codigo;
        $_SESSION['dados_pendentes'] = [
            'campos' => $campos,
            'tipos' => $tipos,
            'valores' => $valores
        ];

        // buscar email
        $sql = "SELECT email FROM usuarios WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $usuario = $resultado->fetch_assoc();
        $email = $usuario['email'];

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'SEU_EMAIL@gmail.com';
            $mail->Password = 'SENHA_DE_APP';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('SEU_EMAIL@gmail.com', 'Sistema');
            $mail->addAddress($email);

            $mail->Subject = 'Código de confirmação';
            $mail->Body = "Seu código é: $codigo";

            $mail->send();

            echo "Código enviado para o email.";
        } catch (Exception $e) {
            echo "Erro ao enviar email.";
        }

        exit;
    }


    if ($codigo_input != ($_SESSION['codigo_confirmacao'] ?? '')) {
        echo "Código inválido!";
        exit;
    }

    $sql = "UPDATE usuarios SET " . implode(", ", $_SESSION['dados_pendentes']['campos']) . " WHERE id = ?";
    $tipos = $_SESSION['dados_pendentes']['tipos'] . "i";
    $valores = $_SESSION['dados_pendentes']['valores'];
    $valores[] = $id_usuario;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($tipos, ...$valores);

    if ($stmt->execute()) {
        echo "Dados atualizados com sucesso!";
        unset($_SESSION['codigo_confirmacao']);
        unset($_SESSION['dados_pendentes']);
    } else {
        echo "Erro ao atualizar.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="css/redefinicao_de_senha.css">
</head>

<body>

<header>
    <h2>Alterar Dados</h2>
</header>

<div class="container">

    <form method="POST" class="form">

        <!-- USUÁRIO -->
        <div class="email_box">
            <label class="legend">Novo usuário</label>

            <div class="inputs">
                <input type="text" name="novo_usuario" class="input" placeholder="Digite o novo usuário">
            </div>
        </div>

        <!-- SENHA -->
        <div class="email_box">
            <label class="legend">Alterar senha</label>

            <div class="inputs">
                <input type="password" name="senha_atual" class="input" placeholder="Senha atual">
                <input type="password" name="nova_senha" class="input" placeholder="Nova senha">
                <input type="password" name="confirmar_senha" class="input" placeholder="Confirmar nova senha">
            </div>
        </div>

        <!-- CÓDIGO -->
        <div class="email_box">
            <label class="legend">Código de confirmação</label>

            <div class="inputs">
                <input type="text" name="codigo" class="input" placeholder="Digite o código recebido no email">
            </div>
        </div>

        <!-- BOTÕES -->
        <div class="botoes">
            <button type="submit">Atualizar</button>
        </div>

    </form>

</div>

</body>
</html>
