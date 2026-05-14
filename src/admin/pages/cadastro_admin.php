<?php
ob_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

require __DIR__ . '/../../../vendor/autoload.php';
require __DIR__ . '/../../api/database.php';
require_once '../../../config/recaptcha.php';

/** @var PDO $pdo */
require __DIR__ . '/../../api/mailer.php';
require __DIR__ . '/../../api/valida_senha.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    unset($_SESSION['usuario'], $_SESSION['2fa_ok'], $_SESSION['2fa_pendente'], $_SESSION['2fa_secret_temp'], $_SESSION['csrf_token']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim(strip_tags($_POST['nome'] ?? ''));
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $chat_id = trim($_POST['chat_id'] ?? '');
    $lgpd = $_POST['lgpd'] ?? '';

    if ($nome === '' || $email === '' || $chat_id === '') {
        $resposta = ['ok' => false, 'msg' => 'Preencha todos os campos.'];
    } elseif ($lgpd !== 'true') {
        $resposta = ['ok' => false, 'msg' => 'Voce deve aceitar os termos da LGPD.'];
    } elseif (!preg_match('/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/', $email)) {
        $resposta = ['ok' => false, 'msg' => 'E-mail invalido.'];
    } elseif (!preg_match('/^\d+$/', $chat_id) || strlen($chat_id) < 5) {
        $resposta = ['ok' => false, 'msg' => 'Chat ID do Telegram invalido.'];
    } else {
        $colunaPerfil = obterColunaPerfilUsuario($pdo);
        $valorAdmin = obterValorPerfilAdmin($pdo);
        $token = bin2hex(random_bytes(32));

        $stmt = $pdo->prepare(
            sprintf(
                'SELECT id_usuario, nome, email, status_cadastro, token_confirmacao, %s FROM usuario WHERE email = ? LIMIT 1',
                obterSelecaoPerfilUsuario($pdo, '')
            )
        );
        $stmt->execute([$email]);
        $usuarioExistente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuarioExistente) {
            if (($usuarioExistente['tipo'] ?? '') !== $valorAdmin) {
                $stmt = $pdo->prepare("UPDATE usuario SET {$colunaPerfil} = ?, telegram_chat_id = ?, telegram_2fa_ativo = TRUE WHERE id_usuario = ?");
                $stmt->execute([$valorAdmin, $chat_id, $usuarioExistente['id_usuario']]);
            } else {
                $stmt = $pdo->prepare("UPDATE usuario SET telegram_chat_id = ?, telegram_2fa_ativo = TRUE WHERE id_usuario = ?");
                $stmt->execute([$chat_id, $usuarioExistente['id_usuario']]);
            }

            $statusCadastro = (string) ($usuarioExistente['status_cadastro'] ?? '');
            $tokenConfirmacao = (string) ($usuarioExistente['token_confirmacao'] ?? '');
            $nomeDestino = $nome !== '' ? $nome : (string) ($usuarioExistente['nome'] ?? 'Administrador');

            if ($statusCadastro !== 'confirmado') {
                if ($tokenConfirmacao === '') {
                    $tokenConfirmacao = $token;
                    $stmt = $pdo->prepare('UPDATE usuario SET token_confirmacao = ? WHERE id_usuario = ?');
                    $stmt->execute([$tokenConfirmacao, $usuarioExistente['id_usuario']]);
                }

                if (enviarEmailConfirmacao($email, $nomeDestino, $tokenConfirmacao, 'admin')) {
                    $resposta = [
                        'ok' => true,
                        'msg' => "Conta administrativa atualizada. Enviamos um e-mail de confirmacao para <strong>{$email}</strong>."
                    ];
                } else {
                    $resposta = ['ok' => false, 'msg' => 'Conta atualizada, mas houve falha ao enviar o e-mail de confirmacao.'];
                }
            } else {
                $resposta = [
                    'ok' => true,
                    'msg' => 'Esta conta ja esta confirmada e agora tem acesso como administrador.'
                ];
            }
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO usuario (nome, email, senha_hash, telegram_chat_id, telegram_2fa_ativo, token_confirmacao, {$colunaPerfil})
                VALUES (?, ?, ?, ?, TRUE, ?, ?)
            ");
            $stmt->execute([$nome, $email, '', $chat_id, $token, $valorAdmin]);

            if (enviarEmailConfirmacao($email, $nome, $token, 'admin')) {
                $resposta = [
                    'ok' => true,
                    'msg' => "Cadastro de administrador realizado! Verifique seu e-mail <strong>{$email}</strong> para confirmar a conta."
                ];
            } else {
                $resposta = ['ok' => false, 'msg' => 'Cadastro salvo, mas falha ao enviar e-mail.'];
            }
        }
    }

    header('Content-Type: application/json; charset=UTF-8');
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
    <link rel="stylesheet" href="../assets/css/cadastro_admin.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <div class="container">
        <h2>Cadastro de Administrador</h2>

        <form id="formCadastro">
            <label for="nome">Nome completo</label>
            <input type="text" id="nome" name="nome" placeholder="Seu nome completo" required>
            <div class="erro-campo" id="erroNome">Digite seu nome completo.</div>

            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" placeholder="seu@email.com" required>
            <div class="erro-campo" id="erroEmail">Informe um e-mail válido.</div>

            <label for="chat_id">Chat ID do Telegram</label>
            <input type="text" id="chat_id" name="chat_id" placeholder="Digite seu Chat ID (apenas números)" pattern="[0-9]+" required>
            <div class="erro-campo" id="erroChatId">Digite um Chat ID válido (apenas números).</div>
            <small style="color: #666; font-size: 0.85rem; margin-top: 5px; display: block;">
                Para obter seu Chat ID, envie uma mensagem para <a href="https://t.me/botfather" target="_blank" style="color: var(--primary);">@botfather</a> no Telegram.
            </small>

            <div style="margin: 20px 0;">
                <label style="display: flex; align-items: center; cursor: pointer;">
                    <input type="checkbox" id="lgpd" name="lgpd" value="true" style="width: auto; margin-right: 8px;">
                    <span style="font-size: 0.9rem;">Aceito os termos da LGPD e confirmo que sou administrador autorizado.</span>
                </label>
                <div class="erro-campo" id="erroLgpd">Você precisa aceitar os termos da LGPD.</div>
            </div>

            <!-- CAPTCHA -->
            <div class="g-recaptcha"
                data-sitekey="<?php echo $RECAPTCHA_SITE_KEY; ?>">
            </div>

            <div class="msg" id="mensagem"></div>

            <button type="submit" id="btnCadastrar">Cadastrar Administrador</button>
        </form>
    </div>

    <script>
        const form = document.getElementById('formCadastro');
        const msgDiv = document.getElementById('mensagem');
        const btnCad = document.getElementById('btnCadastrar');
        const campoNome = document.getElementById('nome');
        const campoEmail = document.getElementById('email');
        const campoChatId = document.getElementById('chat_id');
        const campoLgpd = document.getElementById('lgpd');
        const erroNome = document.getElementById('erroNome');
        const erroEmail = document.getElementById('erroEmail');
        const erroChatId = document.getElementById('erroChatId');
        const erroLgpd = document.getElementById('erroLgpd');

        // Validação em tempo real
        campoNome.addEventListener('input', () => validarNome());
        campoEmail.addEventListener('input', () => validarEmail());
        campoChatId.addEventListener('input', () => validarChatId());
        campoLgpd.addEventListener('change', () => validarLgpd());

        function validarNome() {
            if (campoNome.value.trim().length < 3) {
                campoNome.classList.add('invalido');
                erroNome.classList.add('visivel');
                return false;
            } else {
                campoNome.classList.remove('invalido');
                erroNome.classList.remove('visivel');
                return true;
            }
        }

        function validarEmail() {
            const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (!emailRegex.test(campoEmail.value)) {
                campoEmail.classList.add('invalido');
                erroEmail.classList.add('visivel');
                return false;
            } else {
                campoEmail.classList.remove('invalido');
                erroEmail.classList.remove('visivel');
                return true;
            }
        }

        function validarChatId() {
            const chatIdRegex = /^\d+$/;
            if (!chatIdRegex.test(campoChatId.value) || campoChatId.value.length < 5) {
                campoChatId.classList.add('invalido');
                erroChatId.classList.add('visivel');
                return false;
            } else {
                campoChatId.classList.remove('invalido');
                erroChatId.classList.remove('visivel');
                return true;
            }
        }

        function validarLgpd() {
            if (!campoLgpd.checked) {
                erroLgpd.classList.add('visivel');
                return false;
            } else {
                erroLgpd.classList.remove('visivel');
                return true;
            }
        }

        function mostrarMsg(texto, tipo) {
            msgDiv.innerHTML = texto;
            msgDiv.className = 'msg ' + tipo;
        }

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            msgDiv.className = 'msg';
            msgDiv.innerHTML = '';

            const nomeValido = validarNome();
            const emailValido = validarEmail();
            const chatIdValido = validarChatId();
            const lgpdValida = validarLgpd();

            if (!nomeValido || !emailValido || !chatIdValido || !lgpdValida) {
                mostrarMsg('Por favor, corrija os campos destacados.', 'erro');
                return;
            }

            btnCad.disabled = true;
            btnCad.textContent = 'Cadastrando...';

            const dados = new FormData();
            dados.append('nome', campoNome.value.trim());
            dados.append('email', campoEmail.value.trim());
            dados.append('chat_id', campoChatId.value.trim());
            dados.append('lgpd', campoLgpd.checked ? 'true' : '');

            try {
                const res = await fetch('cadastro_admin.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: dados
                });

                const json = await res.json();

                if (json.ok) {
                    mostrarMsg(json.msg, 'sucesso');
                    setTimeout(() => {
                        window.location.href = 'cadastro_concluido.php?email=' + encodeURIComponent(campoEmail.value.trim()) + '&tipo=admin';
                    }, 2000);
                } else {
                    mostrarMsg(json.msg, 'erro');
                }
            } catch (err) {
                mostrarMsg('Erro de conexao. Tente novamente.', 'erro');
            } finally {
                btnCad.disabled = false;
                btnCad.textContent = 'Cadastrar Administrador';
            }
        });
    </script>
</body>
</html>
