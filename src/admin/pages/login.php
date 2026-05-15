<?php

session_start();

require __DIR__ . '/../../api/database.php';
require __DIR__ . '/../../api/telegram.php';
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
// HELPERS
// =====================================

function json_out(bool $ok, string $msg, array $extra = []): never
{
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['ok' => $ok, 'msg' => $msg] + $extra);
    exit();
}

function buscar_admin(PDO $pdo, string $email): array|false
{
    $stmt = $pdo->prepare("
        SELECT id_usuario, nome, email, tipo,
               status_cadastro, telegram_chat_id, telegram_2fa_ativo
        FROM usuario WHERE email = ? LIMIT 1
    ");
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function validar_admin(array|false $u): ?string
{
    if (!$u)                                                        return 'Administrador não encontrado.';
    if (stripos((string)($u['tipo'] ?? ''), 'admin') === false)     return 'Acesso permitido apenas para administradores.';
    if (($u['status_cadastro'] ?? '') === 'pendente')               return 'Conta pendente de confirmação.';
    if (($u['status_cadastro'] ?? '') === 'bloqueado')              return 'Conta bloqueada.';
    if (empty($u['telegram_chat_id']))                              return 'Nenhum Chat ID cadastrado para esta conta.';
    if (!(bool)$u['telegram_2fa_ativo'])                            return 'Telegram 2FA não ativado para esta conta.';
    return null;
}

// =====================================
// ETAPA 1 — envia OTP pelo Telegram
// =====================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['etapa'] ?? '') === '1') {

    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);

    if (!preg_match(REGEX_EMAIL_ADMIN, $email))
        json_out(false, 'Informe um e-mail válido.');

    try {
        $usuario = buscar_admin($pdo, $email);
        $erro    = validar_admin($usuario);
        if ($erro) json_out(false, $erro);

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $pdo->prepare("UPDATE otp_telegram SET usado = 1 WHERE id_usuario = ? AND usado = 0")
            ->execute([$usuario['id_usuario']]);

        $pdo->prepare("
        INSERT INTO otp_telegram (id_usuario, telegram_chat_id, codigo, expira_em)
        VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE))
        ")->execute([$usuario['id_usuario'], $usuario['telegram_chat_id'], $otp]);

        $enviado = telegram_enviar_otp((string)$usuario['telegram_chat_id'], $otp);
        if (!$enviado) json_out(false, 'Erro ao enviar código pelo Telegram. Tente novamente.');

        $_SESSION['otp_email'] = $email;

        json_out(true, 'Código enviado! Verifique o Telegram.');

    } catch (PDOException $e) {
        error_log('admin otp etapa1: ' . $e->getMessage());
        json_out(false, 'Erro interno. Tente novamente.');
    }
}

// =====================================
// ETAPA 2 — valida OTP digitado
// =====================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['etapa'] ?? '') === '2') {

    $email  = $_SESSION['otp_email'] ?? '';
    $codigo = trim($_POST['codigo'] ?? '');

    if (!$email || !preg_match('/^\d{6}$/', $codigo))
        json_out(false, 'Dados inválidos.');

    try {
        $usuario = buscar_admin($pdo, $email);
        $erro    = validar_admin($usuario);
        if ($erro) json_out(false, $erro);

        $stmt = $pdo->prepare("
            SELECT id, codigo, tentativas FROM otp_telegram
            WHERE id_usuario = ? AND usado = 0 AND expira_em > NOW()
            ORDER BY criado_em DESC LIMIT 1
            ");
        $stmt->execute([$usuario['id_usuario']]);
        $otp_row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$otp_row)
            json_out(false, 'Código expirado. Solicite um novo.');

        if ($otp_row['tentativas'] >= 5) {
            $pdo->prepare("UPDATE otp_telegram SET usado = 1 WHERE id = ?")
                ->execute([$otp_row['id']]);
            json_out(false, 'Muitas tentativas. Solicite um novo código.');
        }

        $pdo->prepare("UPDATE otp_telegram SET tentativas = tentativas + 1 WHERE id = ?")
            ->execute([$otp_row['id']]);

        if (!hash_equals($otp_row['codigo'], $codigo))
            json_out(false, 'Código incorreto. Tente novamente.');

        $pdo->prepare("UPDATE otp_telegram SET usado = 1 WHERE id = ?")
            ->execute([$otp_row['id']]);

        unset($_SESSION['otp_email']);

        $_SESSION['usuario'] = [
            'id_usuario' => $usuario['id_usuario'],
            'nome'       => $usuario['nome'],
            'email'      => $usuario['email'],
            'tipo'       => $usuario['tipo'],
        ];
        $_SESSION['admin_autenticado'] = true;

        json_out(true, 'Login realizado com sucesso.', ['redirect' => './dashboard.php']);

    } catch (PDOException $e) {
        error_log('admin otp etapa2: ' . $e->getMessage());
        json_out(false, 'Erro interno. Tente novamente.');
    }
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

            <!-- ETAPA 1: email -->
            <div id="etapa1">
                <div class="form-header">
                    <h2>Entrar no admin</h2>
                    <p>Informe o e-mail cadastrado. Você receberá um código pelo Telegram.</p>
                </div>
                <form id="formEtapa1" novalidate>
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email"
                           placeholder="admin@dominio.com"
                           autocomplete="email" required>
                    <div class="erro-campo" id="erroEmail">Informe um e-mail válido.</div>
                    <div class="msg" id="msgEtapa1"></div>
                    <button type="submit" id="btnEnviar">Enviar código pelo Telegram</button>
                </form>
            </div>

            <!-- ETAPA 2: código otp -->
            <div id="etapa2" style="display:none">
                <div class="form-header">
                    <h2>Código recebido?</h2>
                    <p>Digite os 6 dígitos enviados pelo Telegram para
                       <strong id="emailExibido"></strong>.
                    </p>
                </div>
                <form id="formEtapa2" novalidate>
                    <label for="codigo">Código de 6 dígitos</label>
                    <input type="text" id="codigo" name="codigo"
                           placeholder="000000" inputmode="numeric"
                           maxlength="6" autocomplete="one-time-code" required>
                    <div class="erro-campo" id="erroCodigo">Informe os 6 dígitos.</div>
                    <p style="font-size:.85rem;color:#666;margin:.25rem 0 1rem">
                        Expira em <span id="countdown">5:00</span>
                    </p>
                    <div class="msg" id="msgEtapa2"></div>
                    <button type="submit" id="btnVerificar" class="btn-primario">Verificar código</button>
                </form>
                <button id="btnReenviar"
                        style="background:none;border:none;color:#1a73e8;cursor:pointer;font-size:.9rem;text-decoration:underline;margin-top:.75rem">
                    Não recebi — reenviar
                </button>
            </div>

            <div class="support-links">
                <a href="./cadastro_admin.php?reset=1">Cadastrar administrador</a>
                <a href="./index.php?reset=1">Limpar sessão</a>
            </div>

        </section>
    </main>

    <script>
        const REGEX_EMAIL = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/;

        const campoEmail  = document.getElementById('email');
        const erroEmail   = document.getElementById('erroEmail');
        const btnEnviar   = document.getElementById('btnEnviar');
        const msgEtapa1   = document.getElementById('msgEtapa1');
        const msgEtapa2   = document.getElementById('msgEtapa2');

        let countdownTimer = null;

        campoEmail.addEventListener('blur',  () => validarEmail());
        campoEmail.addEventListener('input', () => limpar(campoEmail, erroEmail, msgEtapa1));

        function validarEmail() {
            if (!REGEX_EMAIL.test(campoEmail.value.trim())) {
                campoEmail.classList.add('invalido');
                erroEmail.classList.add('visivel');
                return false;
            }
            limpar(campoEmail, erroEmail, msgEtapa1);
            return true;
        }

        function limpar(input, erroDiv, msgDiv) {
            input.classList.remove('invalido');
            erroDiv.classList.remove('visivel');
            if (msgDiv) { msgDiv.className = 'msg'; msgDiv.textContent = ''; }
        }

        function mostrarMsg(div, texto, tipo) {
            div.textContent = texto;
            div.className   = 'msg ' + tipo;
        }

        // ── ETAPA 1 ──────────────────────────────────────────────
        document.getElementById('formEtapa1').addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!validarEmail()) return;

            btnEnviar.disabled    = true;
            btnEnviar.textContent = 'Enviando...';

            const dados = new FormData();
            dados.append('etapa', '1');
            dados.append('email', campoEmail.value.trim());

            try {
                const res  = await fetch('login.php', { method: 'POST', body: dados });
                const json = await res.json();

                if (json.ok) {
                    mostrarMsg(msgEtapa1, json.msg, 'sucesso');
                    document.getElementById('emailExibido').textContent = campoEmail.value.trim();
                    document.getElementById('etapa1').style.display = 'none';
                    document.getElementById('etapa2').style.display = '';
                    iniciarContagem();
                } else {
                    mostrarMsg(msgEtapa1, json.msg || 'Erro ao enviar código.', 'erro');
                }
            } catch {
                mostrarMsg(msgEtapa1, 'Erro de conexão. Tente novamente.', 'erro');
            } finally {
                btnEnviar.disabled    = false;
                btnEnviar.textContent = 'Enviar código pelo Telegram';
            }
        });

        // ── ETAPA 2 ──────────────────────────────────────────────
        document.getElementById('formEtapa2').addEventListener('submit', async (e) => {
            e.preventDefault();

            const campoCodigo = document.getElementById('codigo');
            const erroCodigo  = document.getElementById('erroCodigo');
            const btnVerificar = document.getElementById('btnVerificar');

            if (!/^\d{6}$/.test(campoCodigo.value.trim())) {
                campoCodigo.classList.add('invalido');
                erroCodigo.classList.add('visivel');
                return;
            }

            btnVerificar.disabled    = true;
            btnVerificar.textContent = 'Verificando...';

            const dados = new FormData();
            dados.append('etapa',  '2');
            dados.append('codigo', campoCodigo.value.trim());

            try {
                const res  = await fetch('login.php', { method: 'POST', body: dados });
                const json = await res.json();

                if (json.ok) {
                    mostrarMsg(msgEtapa2, json.msg, 'sucesso');
                    clearInterval(countdownTimer);
                    setTimeout(() => { window.location.href = json.redirect || './dashboard.php'; }, 700);
                } else {
                    mostrarMsg(msgEtapa2, json.msg || 'Código inválido.', 'erro');
                }
            } catch {
                mostrarMsg(msgEtapa2, 'Erro de conexão. Tente novamente.', 'erro');
            } finally {
                btnVerificar.disabled    = false;
                btnVerificar.textContent = 'Verificar código';
            }
        });

        // ── REENVIAR ─────────────────────────────────────────────
        document.getElementById('btnReenviar').addEventListener('click', () => {
            document.getElementById('etapa2').style.display = 'none';
            document.getElementById('etapa1').style.display = '';
            clearInterval(countdownTimer);
        });

        // ── COUNTDOWN ────────────────────────────────────────────
        function iniciarContagem() {
            clearInterval(countdownTimer);
            let secs = 300;
            const el = document.getElementById('countdown');
            countdownTimer = setInterval(() => {
                secs--;
                const m = String(Math.floor(secs / 60)).padStart(1, '0');
                const s = String(secs % 60).padStart(2, '0');
                el.textContent = m + ':' + s;
                if (secs <= 0) {
                    clearInterval(countdownTimer);
                    mostrarMsg(msgEtapa2, 'Código expirado. Clique em "Não recebi" para reenviar.', 'erro');
                }
            }, 1000);
        }
    </script>
</body>
</html>