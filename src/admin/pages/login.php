<?php

require __DIR__ . '/../../api/database.php';

session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

if (!empty($_GET['reset'])) {
    unset($_SESSION['usuario'], $_SESSION['2fa_ok'], $_SESSION['2fa_pendente'], $_SESSION['2fa_secret_temp'], $_SESSION['csrf_token']);
}

define('REGEX_EMAIL_ADMIN', '/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/');
define('REGEX_SENHA_ADMIN', '/^.{12,}$/');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $senha = trim($_POST['senha'] ?? '');

    if ($email === '' || $senha === '') {
        $resposta = ['ok' => false, 'msg' => 'Preencha e-mail e senha.'];
    } elseif (!preg_match(REGEX_EMAIL_ADMIN, $email)) {
        $resposta = ['ok' => false, 'msg' => 'Informe um e-mail valido.'];
    } elseif (!preg_match(REGEX_SENHA_ADMIN, $senha)) {
        $resposta = ['ok' => false, 'msg' => 'A senha deve ter pelo menos 12 caracteres.'];
    } else {
        try {
            $sql = sprintf(
                'SELECT id_usuario, nome, email, senha_hash, status_cadastro, %s, chave_2fa FROM usuario WHERE email = ? LIMIT 1',
                obterSelecaoPerfilUsuario($pdo, '')
            );
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            $tipoUsuario = (string) ($usuario['tipo'] ?? '');
            $ehAdmin = $tipoUsuario !== '' && stripos($tipoUsuario, 'admin') !== false;

            if (!$usuario || !$ehAdmin || !password_verify($senha, (string) $usuario['senha_hash'])) {
                $resposta = ['ok' => false, 'msg' => 'Credenciais invalidas ou acesso sem permissao de administrador.'];
            } elseif (($usuario['status_cadastro'] ?? '') === 'pendente') {
                $resposta = [
                    'ok' => true,
                    'msg' => 'Conta pendente de confirmacao. Redirecionando...',
                    'redirect' => './cadastro_concluido.php?email=' . urlencode($usuario['email']) . '&tipo=admin&origem=login'
                ];
            } elseif (($usuario['status_cadastro'] ?? '') === 'bloqueado') {
                $resposta = ['ok' => false, 'msg' => 'Sua conta de administrador esta bloqueada.'];
            } else {
                $_SESSION['usuario'] = [
                    'id_usuario' => $usuario['id_usuario'],
                    'nome' => $usuario['nome'],
                    'email' => $usuario['email'],
                    'tipo' => $usuario['tipo'],
                ];

                $_SESSION['2fa_ok'] = false;
                $_SESSION['2fa_pendente'] = true;

                $redirect = empty($usuario['chave_2fa'])
                    ? '../../api/2fatores/ativar_2fa.php'
                    : '../../api/2fatores/verificar_2fa.php';

                $resposta = [
                    'ok' => true,
                    'msg' => 'Credenciais validadas. Continue com a autenticacao em duas etapas.',
                    'redirect' => $redirect,
                ];
            }
        } catch (PDOException $e) {
            error_log('admin login.php PDOException: ' . $e->getMessage());
            $resposta = ['ok' => false, 'msg' => 'Erro interno ao processar o login do admin.'];
        }
    }

    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($resposta);
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login do Admin - Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
    <main class="login-shell">
        <section class="login-panel login-panel--brand">
            <span class="eyebrow">Painel administrativo</span>
            <h1>Cruz Azul Admin</h1>
            <p>
                Acesso restrito para administradores, com verificacao em duas etapas antes da liberacao do painel.
            </p>
            <ul class="feature-list">
                <li>Login exclusivo para contas com permissao administrativa</li>
                <li>Integracao direta com o 2FA ja existente no projeto</li>
                <li>Redirecionamento seguro para o dashboard apenas apos validacao completa</li>
            </ul>
        </section>

        <section class="login-panel login-panel--form">
            <div class="form-header">
                <h2>Entrar no admin</h2>
                <p>Use seu e-mail administrativo e conclua a validacao 2FA para acessar o painel.</p>
            </div>

            <form id="formLoginAdmin" novalidate>
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" placeholder="admin@dominio.com" required>
                <div class="erro-campo" id="erroEmail">Informe um e-mail valido.</div>

                <label for="senha">Senha</label>
                <div class="senha-wrap">
                    <input type="password" id="senha" name="senha" placeholder="Minimo 12 caracteres" required>
                    <button type="button" class="btn-olho" id="btnOlho">Mostrar</button>
                </div>
                <div class="erro-campo" id="erroSenha">A senha deve ter pelo menos 12 caracteres.</div>

                <div class="msg" id="mensagem"></div>

                <button type="submit" id="btnEntrar">Entrar no painel</button>
            </form>

            <div class="support-links">
                <a href="../../../public/pages/recuperacao_de_senha.php">Esqueci minha senha</a>
                <a href="./cadastro_admin.php?reset=1">Cadastrar administrador</a>
            </div>
        </section>
    </main>

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

        document.getElementById('formLoginAdmin').addEventListener('submit', async (event) => {
            event.preventDefault();

            const emailOk = validar(campoEmail, REGEX_EMAIL, erroEmail);
            const senhaOk = validar(campoSenha, REGEX_SENHA, erroSenha);
            if (!emailOk || !senhaOk) {
                return;
            }

            msgDiv.className = 'msg';
            msgDiv.textContent = '';

            btnEntrar.disabled = true;
            btnEntrar.textContent = 'Validando...';

            const dados = new FormData();
            dados.append('email', campoEmail.value.trim());
            dados.append('senha', campoSenha.value);

            try {
                const response = await fetch('login.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: dados
                });

                const json = await response.json();

                if (json.ok) {
                    mostrarMsg(json.msg, 'sucesso');
                    window.setTimeout(() => {
                        window.location.href = json.redirect || '../index.php';
                    }, 900);
                } else {
                    mostrarMsg(json.msg, 'erro');
                }
            } catch (error) {
                console.error('Falha ao autenticar admin:', error);
                mostrarMsg('Erro de conexao. Tente novamente.', 'erro');
            } finally {
                btnEntrar.disabled = false;
                btnEntrar.textContent = 'Entrar no painel';
            }
        });

        function mostrarMsg(texto, tipo) {
            msgDiv.textContent = texto;
            msgDiv.className = 'msg ' + tipo;
        }
    </script>
</body>
</html>
