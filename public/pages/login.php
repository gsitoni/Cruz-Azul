<?php
require '../../src/api/database.php';
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

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
        $resposta = ['ok' => false, 'msg' => 'Formato de e-mail invalido.'];
    } elseif (!preg_match(REGEX_SENHA, $senha)) {
        $resposta = ['ok' => false, 'msg' => 'Senha deve ter pelo menos 12 caracteres.'];
    } else {
        try {
            $sql = sprintf(
                'SELECT id_usuario, nome, email, senha_hash, status_cadastro, %s, chave_2fa FROM usuario WHERE email = ?',
                obterSelecaoPerfilUsuario($pdo, '')
            );
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                $resposta = ['ok' => false, 'msg' => 'E-mail ou senha incorretos.'];
            } elseif (!password_verify($senha, $usuario['senha_hash'])) {
                $resposta = ['ok' => false, 'msg' => 'E-mail ou senha incorretos.'];
            } elseif (($usuario['status_cadastro'] ?? '') === 'pendente') {
                $resposta = [
                    'ok' => true,
                    'msg' => 'Conta pendente de confirmacao. Redirecionando...',
                    'redirect' => './cadastro_concluido.php?email=' . urlencode($usuario['email']) . '&tipo=usuario&origem=login'
                ];
            } elseif (($usuario['status_cadastro'] ?? '') === 'bloqueado') {
                $resposta = ['ok' => false, 'msg' => 'Sua conta esta bloqueada. Entre em contato com o suporte.'];
            } else {
                session_regenerate_id(true);

                $_SESSION['usuario_temp'] = [
                    'id_usuario' => $usuario['id_usuario'],
                    'nome' => $usuario['nome'],
                    'email' => $usuario['email'],
                    'tipo' => $usuario['tipo'],
                ];
                unset($_SESSION['usuario']);

                $_SESSION['2fa_ok'] = false;
                $_SESSION['2fa_pendente'] = true;

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
        } catch (PDOException $e) {
            error_log('login.php PDOException: ' . $e->getMessage());
            $resposta = ['ok' => false, 'msg' => 'Erro interno ao processar o login.'];
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
    <title>Login - Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/login.css">
</head>

<body>

    <div class="container">
        <h2>Entrar</h2>

        <form id="formLogin">
            <label for="email">E-mail</label>
            <input type="text" id="email" name="email" placeholder="nome@dominio.com" required>
            <div class="erro-campo" id="erroEmail">Formato invalido. Ex: nome@site.com</div>

            <label for="senha">Senha</label>
            <div class="senha-wrap">
                <input type="password" id="senha" name="senha" placeholder="Minimo 12 caracteres" required>
                <button type="button" class="btn-olho" id="btnOlho">Mostrar</button>
            </div>
            <div class="erro-campo" id="erroSenha">A senha deve ter pelo menos 12 caracteres.</div>

            <div class="msg" id="mensagem"></div>

            <button type="submit" id="btnEntrar">Entrar</button>
        </form>

        <div class="link-cadastro">
            Nao tem conta? <a href="./cadastro.php">Cadastre-se aqui</a>
        </div>

        <div class="link-cadastro" style="margin-top: 15px;">
            <a href="./recuperacao_de_senha.php">Esqueci minha senha</a>
        </div>
    </div>

    <script>
        const REGEX_EMAIL = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/;
        const REGEX_SENHA = /^.{12,}$/;

        const campoEmail = document.getElementById('email');
        const campoSenha = document.getElementById('senha');
        const erroEmail = document.getElementById('erroEmail');
        const erroSenha = document.getElementById('erroSenha');
        const btnEntrar = document.getElementById('btnEntrar');
        const btnOlho = document.getElementById('btnOlho');
        const msgDiv = document.getElementById('mensagem');

        campoEmail.addEventListener('blur', () => validar(campoEmail, REGEX_EMAIL, erroEmail));
        campoSenha.addEventListener('blur', () => validar(campoSenha, REGEX_SENHA, erroSenha));

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

        btnOlho.addEventListener('click', () => {
            const tipo = campoSenha.type === 'password' ? 'text' : 'password';
            campoSenha.type = tipo;
            btnOlho.textContent = tipo === 'password' ? 'Mostrar' : 'Ocultar';
        });

        document.getElementById('formLogin').addEventListener('submit', async function (e) {
            e.preventDefault();

            const emailOk = validar(campoEmail, REGEX_EMAIL, erroEmail);
            const senhaOk = validar(campoSenha, REGEX_SENHA, erroSenha);
            if (!emailOk || !senhaOk) return;

            msgDiv.className = 'msg';
            msgDiv.innerHTML = '';

            btnEntrar.disabled = true;
            btnEntrar.textContent = 'Aguarde...';

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
                    setTimeout(() => { window.location.href = json.redirect || 'index.php'; }, 1000);
                } else {
                    mostrarMsg(json.msg, 'erro');
                }
            } catch (err) {
                console.error('Falha de rede/requisicao:', err);
                mostrarMsg('Erro de conexao. Tente novamente.', 'erro');
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
