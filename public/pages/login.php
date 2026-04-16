<?php

require '../../src/api/database.php';
session_start();

// Headers de segurança
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Se já logado e 2FA concluído, redireciona
if (isset($_SESSION['usuario']) && !empty($_SESSION['2fa_ok'])) {
    $redirect = (stripos((string) $_SESSION['usuario']['tipo'], 'admin') !== false)
        ? '../../src/admin/pages/dashboard.php'
        : './home_usuario.php';
    header('Location: ' . $redirect);
    exit;
}

define('REGEX_EMAIL', '/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/');
define('REGEX_SENHA', '/^.{12,}$/');

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $senha = trim($_POST['senha'] ?? '');

    if (empty($email) || empty($senha)) {
        $resposta = ['ok' => false, 'msg' => 'Preencha todos os campos.'];

    } elseif (!preg_match(REGEX_EMAIL, $email)) {
        $resposta = ['ok' => false, 'msg' => 'Formato de e-mail inválido.'];

    } elseif (!preg_match(REGEX_SENHA, $senha)) {
        $resposta = ['ok' => false, 'msg' => 'Senha deve ter pelo menos 12 caracteres.'];

    } else {
        // O schema importado usa a coluna tipo.
        $stmt = $pdo->prepare("
            SELECT id_usuario, nome, email, senha_hash, status_cadastro, tipo, chave_2fa
            FROM usuario
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            $resposta = ['ok' => false, 'msg' => 'E-mail ou senha incorretos.'];

        } elseif (!password_verify($senha, $usuario['senha_hash'])) {
            $resposta = ['ok' => false, 'msg' => 'E-mail ou senha incorretos.'];

        } elseif (isset($usuario['status_cadastro']) && $usuario['status_cadastro'] === 'pendente') {
            $resposta = ['ok' => false, 'msg' => 'Confirme seu e-mail antes de entrar. Verifique sua caixa de entrada.'];

        } elseif (isset($usuario['status_cadastro']) && $usuario['status_cadastro'] === 'bloqueado') {
            $resposta = ['ok' => false, 'msg' => 'Sua conta está bloqueada. Entre em contato com o suporte.'];

        } else {
            // Salva sessão base
            $_SESSION['usuario'] = [
                'id_usuario' => $usuario['id_usuario'],
                'nome' => $usuario['nome'],
                'email' => $usuario['email'],
                'tipo' => $usuario['tipo'],
            ];

            // Inicializa estados do 2FA
            $_SESSION['2fa_ok'] = false;
            $_SESSION['2fa_pendente'] = true;

            // Redireciona para ativar ou verificar 2FA
            if (empty($usuario['chave_2fa'])) {
                $redirect = '../../src/api/2fatores/ativar_2fa.php';
            } else {
                $redirect = '../../src/api/2fatores/verificar_2fa.php';
            }

            $resposta = [
                'ok' => true,
                'msg' => 'Login realizado! Redirecionando...',
                'redirect' => $redirect,
            ];
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
    <title>Login — Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/login.css">
</head>

<body>

    <div class="container">
        <h2>Entrar</h2>

        <form id="formLogin">

            <!-- E-mail -->
            <label for="email">E-mail</label>
            <input type="text" id="email" name="email" placeholder="nome@dominio.com" required>
            <div class="erro-campo" id="erroEmail">Formato inválido. Ex: nome@site.com</div>
            <!-- <div class="dica">regex: /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/</div> -->

            <!-- Senha -->
            <label for="senha">Senha</label>
            <div class="senha-wrap">
                <input type="password" id="senha" name="senha" placeholder="Mínimo 12 caracteres" required>
                <button type="button" class="btn-olho" id="btnOlho">Mostrar</button>
            </div>
            <div class="erro-campo" id="erroSenha">A senha deve ter pelo menos 12 caracteres.</div>
            <!-- <div class="dica">regex: /^.{12,}$/</div> -->

            <!-- Mensagem geral -->
            <div class="msg" id="mensagem"></div>

            <button type="submit" id="btnEntrar">Entrar</button>
        </form>

        <div class="link-cadastro">
            Não tem conta? <a href="./cadastro.php">Cadastre-se aqui</a>
        </div>

        <div class="link-cadastro" style="margin-top: 15px;">
            <a href="./recuperacao_de_senha.php">Esqueci minha senha</a>
        </div>
    </div>

    <script>

        //  validação em tempo real
        const REGEX_EMAIL = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/;
        const REGEX_SENHA = /^.{12,}$/;

        const campoEmail = document.getElementById('email');
        const campoSenha = document.getElementById('senha');
        const erroEmail = document.getElementById('erroEmail');
        const erroSenha = document.getElementById('erroSenha');
        const btnEntrar = document.getElementById('btnEntrar');
        const btnOlho = document.getElementById('btnOlho');
        const msgDiv = document.getElementById('mensagem');

        // Validação ao sair do campo
        campoEmail.addEventListener('blur', () => validar(campoEmail, REGEX_EMAIL, erroEmail));
        campoSenha.addEventListener('blur', () => validar(campoSenha, REGEX_SENHA, erroSenha));

        // Remove erro enquanto digita
        campoEmail.addEventListener('input', () => limpar(campoEmail, erroEmail));
        campoSenha.addEventListener('input', () => limpar(campoSenha, erroSenha));

        function validar(input, regex, erroDiv) {
            if (!regex.test(input.value.trim())) {
                input.classList.add('invalido');
                erroDiv.classList.add('visivel');
                return false;
            }
            limpar(input, erroDiv);
            return true;
        }

        function limpar(input, erroDiv) {
            input.classList.remove('invalido');
            erroDiv.classList.remove('visivel');
        }

        // Mostrar / ocultar senha
        btnOlho.addEventListener('click', () => {
            const tipo = campoSenha.type === 'password' ? 'text' : 'password';
            campoSenha.type = tipo;
            btnOlho.textContent = tipo === 'password' ? 'Mostrar' : 'Ocultar';
        });


        document.getElementById('formLogin').addEventListener('submit', async function (e) {
            e.preventDefault();

            // Valida campos antes de enviar
            const emailOk = validar(campoEmail, REGEX_EMAIL, erroEmail);
            const senhaOk = validar(campoSenha, REGEX_SENHA, erroSenha);
            if (!emailOk || !senhaOk) return;

            // Limpa mensagem anterior
            msgDiv.className = 'msg';
            msgDiv.innerHTML = '';

            btnEntrar.disabled = true;
            btnEntrar.textContent = 'Aguarde...';

            // Monta FormData 
            const dados = new FormData();
            dados.append('email', campoEmail.value.trim());
            dados.append('senha', campoSenha.value);

            try {
                const res = await fetch('login.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: dados
                });

                const json = await res.json();

                if (json.ok) {
                    mostrarMsg(json.msg, 'sucesso');
                    // Redireciona após 1 segundo
                    setTimeout(() => { window.location.href = json.redirect || 'index.php'; }, 1000);
                } else {
                    mostrarMsg(json.msg, 'erro');
                }

            } catch (err) {
                //ME APAGUE CONSOLE.ERROR
                console.error("Falha de rede/requisição:", err);
                mostrarMsg('Erro de conexão. Tente novamente.', 'erro');
            } finally {
                btnEntrar.disabled = false;
                btnEntrar.textContent = 'Entrar';
            }
        });

        function mostrarMsg(texto, tipo) {
            msgDiv.innerHTML = texto;
            msgDiv.className = 'msg ' + tipo;
        }
    </script>

</body>

</html>