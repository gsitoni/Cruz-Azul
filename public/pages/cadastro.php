<?php
    // cadastro.php - Processa o formulário de cadastro
    
    require '../../src/api/database.php';
    require '../../test_email_bismark/mailer.php';
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

            /* Estilo do HUD/Checkbox LGPD */
            .lgpd-box {
                margin-top: 15px;
                display: flex;
                align-items: flex-start;
                gap: 8px;
            }
            .lgpd-box input { width: auto; margin-top: 3px; }
            .lgpd-box label { margin-top: 0; font-weight: normal; font-size: 12px; }

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

            /* RESPONSIVIDADE */
            @media (max-width: 480px) {
                .container {
                    width: 90%;
                    padding: 20px 15px;
                }

                h2 {
                    font-size: 1.5em;
                }

                input {
                    padding: 8px;
                    font-size: 16px; /* Previne zoom no iOS */
                }

                button {
                    padding: 10px;
                    font-size: 14px;
                }
            }
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
