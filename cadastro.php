<?php
// cadastro.php - Processa o formulário de cadastro
require 'test_email_bismark/database.php';
require 'test_email_bismark/mailer.php';

// Responde requisições AJAX em JSON
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome  = trim($_POST['nome']  ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    // --- Validações ---
    if (empty($nome) || empty($email) || empty($senha)) {
        $resposta = ['ok' => false, 'msg' => 'Preencha todos os campos.'];
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $resposta = ['ok' => false, 'msg' => 'E-mail inválido.'];
    } elseif (strlen($senha) < 6) {
        $resposta = ['ok' => false, 'msg' => 'A senha deve ter pelo menos 6 caracteres.'];
    } else {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $resposta = ['ok' => false, 'msg' => 'Este e-mail já está cadastrado.'];
        } else {
            $token = bin2hex(random_bytes(32));
            $hash  = password_hash($senha, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO usuarios (nome, email, senha, token_confirmacao)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$nome, $email, $hash, $token]);

            if (enviarEmailConfirmacao($email, $nome, $token)) {
                $resposta = [
                    'ok'  => true,
                    'msg' => "Cadastro realizado! Verifique seu e-mail <strong>{$email}</strong> para confirmar a conta."
                ];
            } else {
                $resposta = ['ok' => false, 'msg' => 'Cadastro salvo, mas falha ao enviar e-mail.'];
            }
        }
    }

    header('Content-Type: application/json');
    echo json_encode($resposta);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Usuário</title>
    <style>
        * { box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f8;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .container {
            background: #fff;
            padding: 30px 25px;
            border-radius: 10px;
            width: 360px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        h2 { text-align: center; margin-bottom: 20px; }

        label {
            display: block;
            margin-top: 12px;
            font-weight: bold;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 9px 10px;
            margin-top: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 14px;
            transition: border-color .2s;
        }

        input:focus { outline: none; border-color: #007BFF; }

        button {
            margin-top: 18px;
            width: 100%;
            padding: 11px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 15px;
            transition: background .2s;
        }

        button:hover:not(:disabled) { background-color: #0056b3; }
        button:disabled { opacity: .6; cursor: not-allowed; }

        .msg {
            margin-top: 14px;
            padding: 10px 12px;
            border-radius: 5px;
            font-size: 13px;
            display: none;
        }

        .msg.erro    { background: #fdecea; color: #c0392b; display: block; }
        .msg.sucesso { background: #eafaf1; color: #1e7e34; display: block; }
    </style>
</head>
<body>

<div class="container">
    <h2>Cadastro</h2>

    <form id="formCadastro">
        <label>Nome</label>
        <input type="text" id="nome" name="nome" required>

        <label>E-mail</label>
        <input type="email" id="email" name="email" required>

        <label>Senha</label>
        <input type="password" id="senha" name="senha" required minlength="6">

        <label>Confirmar Senha</label>
        <input type="password" id="confirmarSenha" required>

        <div class="msg" id="mensagem"></div>

        <button type="submit" id="btnCadastrar">Cadastrar</button>
    </form>
</div>

<script>
    const form    = document.getElementById('formCadastro');
    const msgDiv  = document.getElementById('mensagem');
    const btnCad  = document.getElementById('btnCadastrar');

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        // Limpa mensagem anterior
        msgDiv.className = 'msg';
        msgDiv.innerHTML = '';

        const senha          = document.getElementById('senha').value;
        const confirmarSenha = document.getElementById('confirmarSenha').value;

        if (senha !== confirmarSenha) {
            mostrarMsg('As senhas não coincidem!', 'erro');
            return;
        }

        btnCad.disabled    = true;
        btnCad.textContent = 'Aguarde...';

        // Monta FormData para envio ao PHP
        const dados = new FormData();
        dados.append('nome',  document.getElementById('nome').value.trim());
        dados.append('email', document.getElementById('email').value.trim());
        dados.append('senha', senha);

        try {
            const res  = await fetch('cadastro.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: dados
            });

            const json = await res.json();

            if (json.ok) {
                mostrarMsg(json.msg, 'sucesso');
                form.reset();
            } else {
                mostrarMsg(json.msg, 'erro');
            }
        } catch (err) {
            mostrarMsg('Erro de conexão. Tente novamente.', 'erro');
        } finally {
            btnCad.disabled    = false;
            btnCad.textContent = 'Cadastrar';
        }
    });

    function mostrarMsg(texto, tipo) {
        msgDiv.innerHTML  = texto;
        msgDiv.className  = 'msg ' + tipo;
    }
</script>

</body>
</html>