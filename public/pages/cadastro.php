<?php
    // cadastro.php - Processa o formulário de cadastro
    
    require '../../src/api/database.php';
    require '../../src/api/mailer.php';
    require 'valida_senha.php'; 

    // Responde requisições AJAX em JSON
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nome  = trim(strip_tags($_POST['nome']  ?? ''));
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $senha = trim($_POST['senha'] ?? '');
        $lgpd  = $_POST['lgpd'] ?? ''; // Captura o aceite da LGPD

        // --- Validações ---
        $resultadoSenha = validarSenhaForte($senha); // Chame a função aqui

        if (empty($nome) || empty($email) || empty($senha)) {
            $resposta = ['ok' => false, 'msg' => 'Preencha todos os campos.'];
        } elseif ($lgpd !== 'true') { 
            $resposta = ['ok' => false, 'msg' => 'Você deve aceitar os termos da LGPD.'];
        } elseif (!preg_match('/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/', $email)) {
            $resposta = ['ok' => false, 'msg' => 'E-mail inválido.'];
        } elseif ($resultadoSenha !== true) { 
            $resposta = ['ok' => false, 'msg' => $resultadoSenha];
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
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Cadastro de Usuário</title>
        <link rel="stylesheet" href="../assets/css/cadastro.css">
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
            <input type="password" id="senha" name="senha" required>

            <label>Confirmar Senha</label>
            <input type="password" id="confirmarSenha" required>

            <div class="lgpd-box">
                <input type="checkbox" id="lgpd" required>
                <label for="lgpd">Aceito os <a href="privacidade.php" target="_blank">Termos de Privacidade</a>.</label>
            </div>

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

            msgDiv.className = 'msg';
            msgDiv.innerHTML = '';

            const senha          = document.getElementById('senha').value;
            const confirmarSenha = document.getElementById('confirmarSenha').value;
            const lgpdChecked    = document.getElementById('lgpd').checked; // Verifica o checkbox

            if (senha !== confirmarSenha) {
                mostrarMsg('As senhas não coincidem!', 'erro');
                return;
            }

            if (!lgpdChecked) { // Validação visual do aceite
                mostrarMsg('Você precisa aceitar a LGPD.', 'erro');
                return;
            }

            btnCad.disabled    = true;
            btnCad.textContent = 'Aguarde...';

            const dados = new FormData();
            dados.append('nome',  document.getElementById('nome').value.trim());
            dados.append('email', document.getElementById('email').value.trim());
            dados.append('senha', senha);
            dados.append('lgpd',  lgpdChecked); // Envia o estado do checkbox

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
