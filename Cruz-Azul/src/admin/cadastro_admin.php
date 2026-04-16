<?php
    ob_start();
    // cadastro_admin.php - Processa o formulário de cadastro de administrador
    
    // Headers de segurança
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

    require '../../vendor/autoload.php';
    require '../../src/api/database.php';
    require '../../src/api/mailer.php';
    require '../../src/api/valida_senha.php'; 

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
            $stmt = $pdo->prepare("SELECT id_usuario, permissao FROM usuario WHERE email = ?");
            $stmt->execute([$email]);
            $usuario_existente = $stmt->fetch(PDO::FETCH_ASSOC);

            $token = bin2hex(random_bytes(32));
            $hash  = password_hash($senha, PASSWORD_DEFAULT);

            if ($usuario_existente) {
                // Usuário existe, atualizar permissao para incluir Admin
                $nova_permissao = $usuario_existente['permissao'];
                if ($nova_permissao === 'Doador') {
                    $nova_permissao = 'Doador e Admin';
                } elseif ($nova_permissao === 'Admin') {
                    // Já é Admin, nada a fazer
                } elseif ($nova_permissao === 'Doador e Admin') {
                    // Já tem ambas
                }
                if ($nova_permissao !== $usuario_existente['permissao']) {
                    $stmt = $pdo->prepare("UPDATE usuario SET permissao = ? WHERE id_usuario = ?");
                    $stmt->execute([$nova_permissao, $usuario_existente['id_usuario']]);
                }
                $resposta = [
                    'ok'  => true,
                    'msg' => "Permissões atualizadas! Você agora tem acesso como administrador."
                ];
            } else {
                // Novo usuário
                $stmt = $pdo->prepare("
                    INSERT INTO usuario (nome, email, senha_hash, token_confirmacao, permissao)
                    VALUES (?, ?, ?, ?, 'Admin')
                ");
                $stmt->execute([$nome, $email, $hash, $token]);

                if (enviarEmailConfirmacao($email, $nome, $token)) {
                    $resposta = [
                        'ok'  => true,
                        'msg' => "Cadastro de administrador realizado! Verifique seu e-mail <strong>{$email}</strong> para confirmar a conta."
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
        <title>Cadastro de Administrador</title>
        <link rel="stylesheet" href="assets/css/admin_auth.css">
    </head>
    <body>

    <div class="container">
        <h2>Cadastro de Administrador</h2>

        <form id="formCadastro">
            <label>Nome</label>
            <input type="text" id="nome" name="nome" required>

            <label>E-mail</label>
            <input type="email" id="email" name="email" required>

            <label>Senha</label>
            <input type="password" id="senha" name="senha" placeholder="Mínimo 12 caracteres" required>

            <label>Confirmar Senha</label>
            <input type="password" id="confirmarSenha" placeholder="Repita a senha" required>

            <div class="lgpd-box">
                <input type="checkbox" id="lgpd" required>
                <label for="lgpd">Aceito os <a href="privacidade.php" target="_blank">Termos de Privacidade</a>.</label>
            </div>

            <div class="msg" id="mensagem"></div>

            <button type="submit" id="btnCadastrar">Cadastrar Administrador</button>
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
                const res  = await fetch('cadastro_admin.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: dados
                });

                const json = await res.json();

                if (json.ok) {
                    // mostrarMsg(json.msg, 'sucesso');
                    window.location.href = '../../public/pages/cadastro_concluido.php?email=' + encodeURIComponent(document.getElementById('email').value.trim()) + '&tipo=admin';
                } else {
                    mostrarMsg(json.msg, 'erro');
                }
            } catch (err) {
                mostrarMsg('Erro de conexão. Tente novamente.', 'erro');
            } finally {
                btnCad.disabled    = false;
                btnCad.textContent = 'Cadastrar Administrador';
            }
        });

        function mostrarMsg(texto, tipo) {
            msgDiv.innerHTML  = texto;
            msgDiv.className  = 'msg ' + tipo;
        }
    </script>

    </body>
    </html>