<?php
require '../../src/api/database.php';
require_once '../../config/recaptcha.php';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

if (isset($_SESSION['ong'])) {
    header('Location: home_ong.php');
    exit;
}

// regex de validação //
$REGEX_EMAIL = '/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/';
$REGEX_SENHA = '/^.{12,}$/';

// processa o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $resposta = [];

    $captcha = $_POST['g-recaptcha-response'] ?? '';

    if (empty($captcha)) {

        echo json_encode([
            'ok' => false,
            'msg' => 'Confirme o CAPTCHA.'
        ]);

        exit();
    }

    $verificacao = file_get_contents(
        "https://www.google.com/recaptcha/api/siteverify?secret="
        . $RECAPTCHA_SECRET_KEY
        . "&response="
        . $captcha
    );

    $respostaCaptcha = json_decode($verificacao);

    if (
        !$respostaCaptcha ||
        !$respostaCaptcha->success
    ) {

        echo json_encode([
            'ok' => false,
            'msg' => 'CAPTCHA inválido.'
        ]);

        exit();
    }

    // validação com regex
    if (empty($email)) {
        $resposta = ['ok' => false, 'campo' => 'email', 'msg' => 'Informe o e-mail.'];

    } elseif (!preg_match($REGEX_EMAIL, $email)) {
        $resposta = ['ok' => false, 'campo' => 'email', 'msg' => 'E-mail inválido.'];

    } elseif (empty($senha)) {
        $resposta = ['ok' => false, 'campo' => 'senha', 'msg' => 'Informe a senha.'];

    } elseif (!preg_match($REGEX_SENHA, $senha)) {
        $resposta = ['ok' => false, 'campo' => 'senha', 'msg' => 'A senha deve ter pelo menos 12 caracteres.'];

    } else {
        // busca a ONG no banco pelo e-mail
        $stmt = $pdo->prepare("SELECT * FROM ong WHERE email = ?");
        $stmt->execute([$email]);
        $ong = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ong || !password_verify($senha, $ong['senha_hash'])) {
            $resposta = ['ok' => false, 'msg' => 'E-mail ou senha incorretos.'];

        } elseif (($ong['status_elegibilidade'] ?? '') !== 'ativo') {
            if (($ong['status_elegibilidade'] ?? '') === 'rejeitado') {
                $resposta = ['ok' => false, 'msg' => 'Sua ONG foi rejeitada. Entre em contato com o administrador.'];
            } else {
                $resposta = ['ok' => false, 'msg' => 'Sua ONG ainda não foi aprovada. Aguarde a validação do administrador.'];
            }

        } else {
            // login OK — salva os dados na sessão
            session_regenerate_id(true);

            $_SESSION['ong'] = [
                'id'           => $ong['id_ong'],
                'nome'         => $ong['nome'],
                'email'        => $ong['email'],
                'area_atuacao' => $ong['area_atuacao'],
                'status'       => $ong['status_elegibilidade'],
                'cidade'       => $ong['cidade'],
                'estado'       => $ong['sigla_estado'],
            ];

            $resposta = [
                'ok'       => true,
                'msg'      => 'Login realizado! Redirecionando...',
                'redirect' => 'home_ong.php'
            ];
        }
    }

    // devolve a resposta em JSON pro JavaScript
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
    <title>Login ONG — Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/login_ong.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>

<div class="container">

    <div class="cabecalho">
        <div class="icone">🏢</div>
        <h2>Entrar como ONG</h2>
        <p>Acesso exclusivo para organizações cadastradas</p>
    </div>

    <form id="formLogin">

        <label for="email">E-mail da ONG</label>
        <input
            type="text"
            id="email"
            name="email"
            placeholder="contato@ong.org.br"
        >
        <div class="erro-campo" id="erroEmail"></div>

        <label for="senha">Senha</label>
        <div class="campo-senha">
            <input
                type="password"
                id="senha"
                name="senha"
                placeholder="Mínimo 12 caracteres"
            >
            <button type="button" class="btn-olho" id="btnOlho">Mostrar</button>
        </div>
        <div class="erro-campo" id="erroSenha"></div>

        <!-- CAPTCHA -->
        <div class="g-recaptcha"
            data-sitekey="<?php echo $RECAPTCHA_SITE_KEY; ?>">
        </div>

        <div class="mensagem" id="mensagem"></div>

        <button type="submit" id="btnEntrar">Entrar</button>

    </form>

    <hr class="separador">

    <div class="links">
        Não tem cadastro? <a href="cadastro_ong.php">Cadastre sua ONG</a>
    </div>
    <div class="links" style="margin-top: 8px;">
        É um doador? <a href="login.php">Entrar aqui</a>
    </div>

    <div class="links" style="margin-top: 8px;">
        <a href="recuperacao_de_senha.php">Esqueci minha senha</a>
    </div>

</div>

<script>
    // ── mesmos regex do PHP ──
    var REGEX_EMAIL = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/;
    var REGEX_SENHA = /^.{12,}$/;

    var campoEmail = document.getElementById('email');
    var campoSenha = document.getElementById('senha');
    var btnOlho    = document.getElementById('btnOlho');
    var mensagem   = document.getElementById('mensagem');

    // mostrar/ocultar senha
    btnOlho.addEventListener('click', function() {
        if (campoSenha.type === 'password') {
            campoSenha.type = 'text';
            btnOlho.textContent = 'Ocultar';
        } else {
            campoSenha.type = 'password';
            btnOlho.textContent = 'Mostrar';
        }
    });

    // mostra erro embaixo de um campo
    function mostrarErroCampo(inputId, erroId, msg) {
        document.getElementById(inputId).classList.add('erro');
        var el = document.getElementById(erroId);
        el.textContent = '❌ ' + msg;
        el.style.display = 'block';
    }

    // limpa o erro de um campo
    function limparErroCampo(inputId, erroId) {
        document.getElementById(inputId).classList.remove('erro');
        document.getElementById(erroId).style.display = 'none';
    }

    // limpa erro enquanto o usuário digita
    campoEmail.addEventListener('input', function() {
        limparErroCampo('email', 'erroEmail');
    });

    campoSenha.addEventListener('input', function() {
        limparErroCampo('senha', 'erroSenha');
    });

    // valida ao sair do campo
    campoEmail.addEventListener('blur', function() {
        if (!REGEX_EMAIL.test(campoEmail.value.trim())) {
            mostrarErroCampo('email', 'erroEmail', 'E-mail inválido.');
        }
    });

    campoSenha.addEventListener('blur', function() {
        if (!REGEX_SENHA.test(campoSenha.value)) {
            mostrarErroCampo('senha', 'erroSenha', 'Mínimo 12 caracteres.');
        }
    });

    // envio do formulário via AJAX
    document.getElementById('formLogin').addEventListener('submit', async function(e) {
        e.preventDefault();

        // valida antes de enviar
        var emailOk = true;
        var senhaOk = true;

        if (!REGEX_EMAIL.test(campoEmail.value.trim())) {
            mostrarErroCampo('email', 'erroEmail', 'E-mail inválido.');
            emailOk = false;
        }

        if (!REGEX_SENHA.test(campoSenha.value)) {
            mostrarErroCampo('senha', 'erroSenha', 'Mínimo 12 caracteres.');
            senhaOk = false;
        }

        if (!emailOk || !senhaOk) return;

        if (grecaptcha.getResponse() === '') {
            mensagem.textContent = 'Confirme o CAPTCHA.';
            mensagem.className   = 'mensagem erro';
            return;
        }

        // limpa mensagem anterior
        mensagem.className = 'mensagem';

        var btn = document.getElementById('btnEntrar');
        btn.disabled    = true;
        btn.textContent = 'Aguarde...';

        // monta os dados do formulário
        var dados = new FormData();
        dados.append('email', campoEmail.value.trim());
        dados.append('senha', campoSenha.value);
        dados.append('g-recaptcha-response', grecaptcha.getResponse());

        try {
            var res  = await fetch('login_ong.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: dados
            });

            var json = await res.json();

            mensagem.textContent = json.msg;
            mensagem.className   = 'mensagem ' + (json.ok ? 'sucesso' : 'erro');

            // redireciona se o login foi ok
            if (json.ok) {
                setTimeout(function() {
                    window.location.href = json.redirect;
                }, 1000);
            }

        } catch (erro) {
            mensagem.textContent = 'Erro de conexão. Tente novamente.';
            mensagem.className   = 'mensagem erro';
        } finally {
            btn.disabled    = false;
            btn.textContent = 'Entrar';
            grecaptcha.reset();
        }
    });
</script>

</body>
</html>
