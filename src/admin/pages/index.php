<?php
require __DIR__ . '/../../api/database.php';
require_once '../../../config/recaptcha.php';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Strict',
]);

session_start();

/** @var PDO $pdo */
require_once __DIR__ . '/../../api/logs_sistema.php';
require_once __DIR__ . '/admin_config.php';
$adminConfig = adminConfigCarregar();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');


if (!empty($_GET['reset'])) {

    unset(
        $_SESSION['usuario'],
        $_SESSION['admin_autenticado'],
        $_SESSION['csrf_token']
    );
}

if (
    !empty($_SESSION['usuario']) &&
    !empty($_SESSION['admin_autenticado'])
) {
    header('Location: ./dashboard.php');
    exit();
}

$status = (string) ($_GET['status'] ?? '');
$mensagemStatus = '';

if ($status === 'senha_redefinida') {
    $mensagemStatus = 'Senha redefinida com sucesso. Faça login com a nova senha quando solicitado.';
}

// =====================================
// REGEX
// =====================================

define(
    'REGEX_EMAIL_ADMIN',
    '/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/'
);

define(
    'REGEX_CHAT_ID',
    '/^[0-9]{6,}$/'
);

function registrarTentativaAdmin(): void
{
    $_SESSION['admin_login_tentativas'] = (int) ($_SESSION['admin_login_tentativas'] ?? 0) + 1;
}

// =====================================
// LOGIN
// =====================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    header('Content-Type: application/json; charset=UTF-8');

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

    $resposta = json_decode($verificacao);

    if (!$resposta->success) {

        echo json_encode([
            'ok' => false,
            'msg' => 'CAPTCHA inválido.'
        ]);

        exit();
    }


    $email = filter_var(
        trim($_POST['email'] ?? ''),
        FILTER_SANITIZE_EMAIL
    );

    $chat_id = trim($_POST['chat_id'] ?? '');
    $exige2fa = !empty($adminConfig['autenticacao_2fa']);
    $maxTentativas = (int) ($adminConfig['tentativas_login'] ?? 5);
    $_SESSION['admin_login_tentativas'] = (int) ($_SESSION['admin_login_tentativas'] ?? 0);

    // =====================================
    // VALIDAÇÕES
    // =====================================

    if ($_SESSION['admin_login_tentativas'] >= $maxTentativas) {

        echo json_encode([
            'ok' => false,
            'msg' => 'Limite de tentativas excedido. Limpe a sessao ou tente novamente mais tarde.'
        ]);

        exit();
    }

    if ($email === '' || ($exige2fa && $chat_id === '')) {

        registrarTentativaAdmin();
        echo json_encode([
            'ok' => false,
            'msg' => 'Preencha todos os campos.'
        ]);

        exit();
    }

    if (!preg_match(REGEX_EMAIL_ADMIN, $email)) {

        registrarTentativaAdmin();
        echo json_encode([
            'ok' => false,
            'msg' => 'Informe um e-mail válido.'
        ]);

        exit();
    }

    if ($exige2fa && !preg_match(REGEX_CHAT_ID, $chat_id)) {

        registrarTentativaAdmin();
        echo json_encode([
            'ok' => false,
            'msg' => 'Informe um Chat ID válido.'
        ]);

        exit();
    }

    try {

        // =====================================
        // BUSCA ADMIN
        // =====================================

        $sql = "
            SELECT
                id_usuario,
                nome,
                email,
                tipo,
                status_cadastro,
                telegram_chat_id,
                telegram_2fa_ativo
            FROM usuario
            WHERE email = ?
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([$email]);

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // =====================================
        // USUÁRIO NÃO EXISTE
        // =====================================

        if (!$usuario) {

            registrarTentativaAdmin();
            registrarLogSistema($pdo, 'WARNING', 'LOGIN', 'Login admin falhou', 'Tentativa com administrador inexistente.', 'usuario');
            echo json_encode([
                'ok' => false,
                'msg' => 'Administrador não encontrado.'
            ]);

            exit();
        }

        // =====================================
        // VERIFICA ADMIN
        // =====================================

        $tipoUsuario = (string) ($usuario['tipo'] ?? '');

        $ehAdmin =
            $tipoUsuario !== '' &&
            stripos($tipoUsuario, 'admin') !== false;

        if (!$ehAdmin) {

            registrarTentativaAdmin();
            registrarLogSistema($pdo, 'WARNING', 'SEGURANCA', 'Acesso admin negado', 'Usuario sem perfil admin tentou acessar o painel.', 'usuario', (int) $usuario['id_usuario'], (int) $usuario['id_usuario']);
            echo json_encode([
                'ok' => false,
                'msg' => 'Acesso permitido apenas para administradores.'
            ]);

            exit();
        }

        // =====================================
        // VERIFICA STATUS
        // =====================================

        if (($usuario['status_cadastro'] ?? '') === 'pendente') {

            registrarTentativaAdmin();
            registrarLogSistema($pdo, 'WARNING', 'LOGIN', 'Login admin pendente', 'Conta administrativa pendente tentou acessar o painel.', 'usuario', (int) $usuario['id_usuario'], (int) $usuario['id_usuario']);
            echo json_encode([
                'ok' => false,
                'msg' => 'Conta pendente de confirmação.'
            ]);

            exit();
        }

        if (($usuario['status_cadastro'] ?? '') === 'bloqueado') {

            registrarTentativaAdmin();
            registrarLogSistema($pdo, 'WARNING', 'LOGIN', 'Login admin bloqueado', 'Conta administrativa bloqueada tentou acessar o painel.', 'usuario', (int) $usuario['id_usuario'], (int) $usuario['id_usuario']);
            echo json_encode([
                'ok' => false,
                'msg' => 'Conta bloqueada.'
            ]);

            exit();
        }

        // =====================================
        // VERIFICA CHAT ID
        // =====================================

        if (
            $exige2fa &&
            (empty($usuario['telegram_chat_id']) || $usuario['telegram_chat_id'] != $chat_id)
        ) {

            registrarTentativaAdmin();
            registrarLogSistema($pdo, 'WARNING', 'SEGURANCA', 'Chat ID admin invalido', 'Tentativa de login admin com Chat ID invalido.', 'usuario', (int) $usuario['id_usuario'], (int) $usuario['id_usuario']);
            echo json_encode([
                'ok' => false,
                'msg' => 'Chat ID inválido.'
            ]);

            exit();
        }

        // =====================================
        // VERIFICA TELEGRAM 2FA
        // =====================================

        if ($exige2fa && !(bool) $usuario['telegram_2fa_ativo']) {

            registrarTentativaAdmin();
            registrarLogSistema($pdo, 'WARNING', 'SEGURANCA', '2FA admin inativo', 'Conta admin tentou entrar sem Telegram 2FA ativo.', 'usuario', (int) $usuario['id_usuario'], (int) $usuario['id_usuario']);
            echo json_encode([
                'ok' => false,
                'msg' => 'Telegram 2FA não ativado.'
            ]);

            exit();
        }

        // =====================================
        // LOGIN OK
        // =====================================

        session_regenerate_id(true);

        $_SESSION['usuario'] = [
            'id_usuario' => $usuario['id_usuario'],
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'tipo' => $usuario['tipo']
        ];

        $_SESSION['admin_autenticado'] = true;
        $_SESSION['admin_ultimo_acesso'] = time();
        $_SESSION['admin_login_tentativas'] = 0;
        registrarLogSistema($pdo, 'INFO', 'LOGIN', 'Login admin realizado', 'Administrador acessou o painel.', 'usuario', (int) $usuario['id_usuario'], (int) $usuario['id_usuario']);

        echo json_encode([
            'ok' => true,
            'msg' => 'Login realizado com sucesso.',
            'redirect' => './dashboard.php'
        ]);

    } catch (PDOException $e) {
        error_log(
            'admin login telegram: ' .
            $e->getMessage()
        );

        echo json_encode([
            'ok' => false,
            'msg' => 'Erro interno no servidor.'
        ]);
    }
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
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <main class="login-shell">
        <section class="login-panel login-panel--brand">
            <span class="eyebrow">Painel administrativo</span>
            <h1>Cruz Azul Admin</h1>
            <p>
                Acesse o painel com seu e-mail administrativo e o Chat ID do Telegram vinculado à conta.
            </p>
            <ul class="feature-list">
                <li>Acesso restrito a usuários com perfil administrativo</li>
                <li>Validação do Chat ID cadastrado no Telegram</li>
                <li>Entrada liberada apenas para contas confirmadas e ativas</li>
            </ul>
        </section>

        <section class="login-panel login-panel--form">
            <div class="form-header">
                <h2>Entrar no admin</h2>
                <p>Informe os dados vinculados ao seu cadastro administrativo.</p>
            </div>

            <?php if ($mensagemStatus !== ''): ?>
                <div class="msg sucesso" id="mensagemStatus">
                    <?= htmlspecialchars($mensagemStatus, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form id="formLoginAdmin" novalidate>
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" placeholder="admin@dominio.com" autocomplete="email" required>
                <div class="erro-campo" id="erroEmail">Informe um e-mail válido.</div>

                <?php if (!empty($adminConfig['autenticacao_2fa'])): ?>
                <label for="chat_id">Chat ID do Telegram</label>
                <input type="text" id="chat_id" name="chat_id" placeholder="Somente números" inputmode="numeric" autocomplete="one-time-code" required>
                <div class="erro-campo" id="erroChatId">Informe um Chat ID válido.</div>

                <?php endif; ?>

                <!-- CAPTCHA -->
                <div class="g-recaptcha"
                    data-sitekey="<?php echo $RECAPTCHA_SITE_KEY; ?>">
                </div>

             <div class="msg" id="mensagem"></div>

                <button type="submit" id="btnEntrar">
                    Entrar no painel
                </button>

            <div class="support-links">
                <a href="./cadastro_admin.php?reset=1">Cadastrar administrador</a>
                <a href="./recuperar_senha.php?reset=1">Recuperar senha</a>
                <a href="./index.php?reset=1">Limpar sessão</a>
            </div>
        </section>
    </main>

    <script>
        const EXIGE_2FA = <?= !empty($adminConfig['autenticacao_2fa']) ? 'true' : 'false' ?>;
        const REGEX_EMAIL = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/;
        const REGEX_CHAT_ID = /^[0-9]{6,}$/;

        const campoEmail = document.getElementById('email');
        const campoChatId = document.getElementById('chat_id');
        const erroEmail = document.getElementById('erroEmail');
        const erroChatId = document.getElementById('erroChatId');
        const btnEntrar = document.getElementById('btnEntrar');
        const msgDiv = document.getElementById('mensagem');

        campoEmail.addEventListener('blur', () => validar(campoEmail, REGEX_EMAIL, erroEmail));
        if (EXIGE_2FA) {
            campoChatId.addEventListener('blur', () => validar(campoChatId, REGEX_CHAT_ID, erroChatId));
        }

        campoEmail.addEventListener('input', () => limpar(campoEmail, erroEmail));
        if (EXIGE_2FA) {
            campoChatId.addEventListener('input', () => {
                campoChatId.value = campoChatId.value.replace(/\D/g, '');
                limpar(campoChatId, erroChatId);
            });
        }

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
            msgDiv.className = 'msg';
            msgDiv.textContent = '';
        }

        document.getElementById('formLoginAdmin').addEventListener('submit', async (event) => {
            event.preventDefault();

            const emailOk = validar(campoEmail, REGEX_EMAIL, erroEmail);
            const chatIdOk = !EXIGE_2FA || validar(campoChatId, REGEX_CHAT_ID, erroChatId);

            if (!emailOk || !chatIdOk) {
                return;
            }

            btnEntrar.disabled = true;
            btnEntrar.textContent = 'Validando...';

            if (grecaptcha.getResponse() === '') {
                mostrarMsg('Confirme o CAPTCHA.', 'erro');
                btnEntrar.disabled = false;
                btnEntrar.textContent = 'Entrar no painel';
                return;
            }

            const dados = new FormData();
            dados.append('email', campoEmail.value.trim());
            dados.append('chat_id', EXIGE_2FA ? campoChatId.value.trim() : '');
            dados.append('g-recaptcha-response', grecaptcha.getResponse());

            try {
                const response = await fetch('index.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: dados
                });

                const json = await response.json();

                if (json.ok) {
                    mostrarMsg(json.msg, 'sucesso');
                    window.setTimeout(() => {
                        window.location.href = json.redirect || './dashboard.php';
                    }, 700);
                } else {
                    mostrarMsg(json.msg || 'Não foi possível entrar.', 'erro');
                    grecaptcha.reset();
                }
            } catch (error) {
                console.error('Falha ao autenticar admin:', error);
                mostrarMsg('Erro de conexão. Tente novamente.', 'erro');
                grecaptcha.reset();
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
