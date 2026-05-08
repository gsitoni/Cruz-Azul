<?php

session_start();

require __DIR__ . '/../../api/database.php';
/** @var PDO $pdo */

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

// =====================================
// LOGIN
// =====================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    header('Content-Type: application/json; charset=UTF-8');

    $email = filter_var(
        trim($_POST['email'] ?? ''),
        FILTER_SANITIZE_EMAIL
    );

    $chat_id = trim($_POST['chat_id'] ?? '');

    // =====================================
    // VALIDAÇÕES
    // =====================================

    if ($email === '' || $chat_id === '') {

        echo json_encode([
            'ok' => false,
            'msg' => 'Preencha todos os campos.'
        ]);

        exit();
    }

    if (!preg_match(REGEX_EMAIL_ADMIN, $email)) {

        echo json_encode([
            'ok' => false,
            'msg' => 'Informe um e-mail válido.'
        ]);

        exit();
    }

    if (!preg_match(REGEX_CHAT_ID, $chat_id)) {

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

            echo json_encode([
                'ok' => false,
                'msg' => 'Conta pendente de confirmação.'
            ]);

            exit();
        }

        if (($usuario['status_cadastro'] ?? '') === 'bloqueado') {

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
            empty($usuario['telegram_chat_id']) ||
            $usuario['telegram_chat_id'] != $chat_id
        ) {

            echo json_encode([
                'ok' => false,
                'msg' => 'Chat ID inválido.'
            ]);

            exit();
        }

        // =====================================
        // VERIFICA TELEGRAM 2FA
        // =====================================

        if (!(bool) $usuario['telegram_2fa_ativo']) {

            echo json_encode([
                'ok' => false,
                'msg' => 'Telegram 2FA não ativado.'
            ]);

            exit();
        }

        // =====================================
        // LOGIN OK
        // =====================================

        $_SESSION['usuario'] = [
            'id_usuario' => $usuario['id_usuario'],
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'tipo' => $usuario['tipo']
        ];

        $_SESSION['admin_autenticado'] = true;

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

            <form id="formLoginAdmin" novalidate>
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" placeholder="admin@dominio.com" autocomplete="email" required>
                <div class="erro-campo" id="erroEmail">Informe um e-mail válido.</div>

                <label for="chat_id">Chat ID do Telegram</label>
                <input type="text" id="chat_id" name="chat_id" placeholder="Somente números" inputmode="numeric" autocomplete="one-time-code" required>
                <div class="erro-campo" id="erroChatId">Informe um Chat ID válido.</div>

                <div class="msg" id="mensagem"></div>

                <button type="submit" id="btnEntrar">Entrar no painel</button>
            </form>

            <div class="support-links">
                <a href="./cadastro_admin.php?reset=1">Cadastrar administrador</a>
                <a href="./index.php?reset=1">Limpar sessão</a>
            </div>
        </section>
    </main>

    <script>
        const REGEX_EMAIL = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/;
        const REGEX_CHAT_ID = /^[0-9]{6,}$/;

        const campoEmail = document.getElementById('email');
        const campoChatId = document.getElementById('chat_id');
        const erroEmail = document.getElementById('erroEmail');
        const erroChatId = document.getElementById('erroChatId');
        const btnEntrar = document.getElementById('btnEntrar');
        const msgDiv = document.getElementById('mensagem');

        campoEmail.addEventListener('blur', () => validar(campoEmail, REGEX_EMAIL, erroEmail));
        campoChatId.addEventListener('blur', () => validar(campoChatId, REGEX_CHAT_ID, erroChatId));

        campoEmail.addEventListener('input', () => limpar(campoEmail, erroEmail));
        campoChatId.addEventListener('input', () => {
            campoChatId.value = campoChatId.value.replace(/\D/g, '');
            limpar(campoChatId, erroChatId);
        });

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
            const chatIdOk = validar(campoChatId, REGEX_CHAT_ID, erroChatId);

            if (!emailOk || !chatIdOk) {
                return;
            }

            btnEntrar.disabled = true;
            btnEntrar.textContent = 'Validando...';

            const dados = new FormData();
            dados.append('email', campoEmail.value.trim());
            dados.append('chat_id', campoChatId.value.trim());

            try {
                const response = await fetch('login.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: dados
                });

                const text = await response.text();
                let json;

                try {
                    json = JSON.parse(text);
                } catch (error) {
                    console.error('Resposta inesperada do servidor:', text);
                    mostrarMsg('O servidor retornou uma resposta inválida. Verifique os dados do banco e tente novamente.', 'erro');
                    return;
                }

                if (json.ok) {
                    mostrarMsg(json.msg, 'sucesso');
                    window.setTimeout(() => {
                        window.location.href = json.redirect || './dashboard.php';
                    }, 700);
                } else {
                    mostrarMsg(json.msg || 'Não foi possível entrar.', 'erro');
                }
            } catch (error) {
                console.error('Falha ao autenticar admin:', error);
                mostrarMsg('Erro de conexão. Tente novamente.', 'erro');
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
