<?php
require '../../src/api/database.php';
require_once '../../src/crypto/crypto_helpers.php';
require_once '../../config/recaptcha.php';

session_set_cookie_params([
    'lifetime' => 0, 'path' => '/', 'domain' => '',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true, 'samesite' => 'Lax',
]);
session_start();

if (isset($_SESSION['ong'])) { header('Location: home_ong.php'); exit; }

$REGEX_EMAIL = '/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/';
$REGEX_SENHA = '/^.{12,}$/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email   = trim($_POST['email'] ?? '');
    $senha   = $_POST['senha']      ?? '';
    $captcha = $_POST['g-recaptcha-response'] ?? '';

    if (empty($captcha)) {
        echo json_encode(['ok' => false, 'msg' => 'Confirme o CAPTCHA.']); exit();
    }

    $verificacao     = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" . $RECAPTCHA_SECRET_KEY . "&response=" . $captcha);
    $respostaCaptcha = json_decode($verificacao);

    if (!$respostaCaptcha || !$respostaCaptcha->success) {
        echo json_encode(['ok' => false, 'msg' => 'CAPTCHA inválido.']); exit();
    }

    if (empty($email)) {
        $resposta = ['ok' => false, 'campo' => 'email', 'msg' => 'Informe o e-mail.'];
    } elseif (!preg_match($REGEX_EMAIL, $email)) {
        $resposta = ['ok' => false, 'campo' => 'email', 'msg' => 'E-mail inválido.'];
    } elseif (empty($senha)) {
        $resposta = ['ok' => false, 'campo' => 'senha', 'msg' => 'Informe a senha.'];
    } elseif (!preg_match($REGEX_SENHA, $senha)) {
        $resposta = ['ok' => false, 'campo' => 'senha', 'msg' => 'A senha deve ter pelo menos 12 caracteres.'];
    } else {
        // Busca em usuario (tabela mãe) igual ao doador
        $stmt = $pdo->prepare("
            SELECT u.id_usuario, u.nome, u.email, u.senha_hash, u.status_cadastro,
                   o.id_ong, o.status_elegibilidade, o.iv_dados, o.chave_aes_cifrada,
                   o.nome AS nome_ong, o.email AS email_ong,
                   o.area_atuacao, o.cidade, o.sigla_estado
            FROM usuario u
            INNER JOIN ong o ON o.id_usuario = u.id_usuario
            WHERE u.email = ? AND u.tipo = 'ong'
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || !password_verify($senha, $row['senha_hash'])) {
            $resposta = ['ok' => false, 'msg' => 'E-mail ou senha incorretos.'];
        } elseif ($row['status_cadastro'] === 'pendente') {
            $resposta = [
                'ok'  => true,
                'msg' => 'Conta pendente de confirmação. Redirecionando...',
                'redirect' => 'cadastro_concluido.php?email=' . urlencode($email) . '&tipo=ong&origem=login'
            ];
        } elseif ($row['status_cadastro'] === 'bloqueado') {
            $resposta = ['ok' => false, 'msg' => 'Sua conta está bloqueada. Entre em contato com o suporte.'];
        } elseif ($row['status_elegibilidade'] === 'rejeitado') {
            $resposta = ['ok' => false, 'msg' => 'Sua ONG foi rejeitada pelo administrador.'];
        } elseif ($row['status_elegibilidade'] === 'pendente') {
            $resposta = ['ok' => false, 'msg' => 'Sua ONG ainda não foi aprovada pelo administrador.'];
        } else {
            // Decifra nome e email para a sessão
            $ongDecifrada = [
                'nome'              => $row['nome_ong'],
                'email'             => $row['email_ong'],
                'area_atuacao'      => $row['area_atuacao'],
                'cidade'            => $row['cidade'],
                'sigla_estado'      => $row['sigla_estado'],
                'iv_dados'          => $row['iv_dados'],
                'chave_aes_cifrada' => $row['chave_aes_cifrada'],
            ];
            if (!empty($row['chave_aes_cifrada']) && !empty($row['iv_dados'])) {
                decifrarOng($ongDecifrada);
            }

            logCrypto("[LOGIN_ONG] id_usuario={$row['id_usuario']} | id_ong={$row['id_ong']}");

            session_regenerate_id(true);
            $_SESSION['ong'] = [
                'id'           => $row['id_ong'],
                'id_usuario'   => $row['id_usuario'],
                'nome'         => $ongDecifrada['nome'],
                'email'        => $ongDecifrada['email'],
                'area_atuacao' => $ongDecifrada['area_atuacao'],
                'status'       => $row['status_elegibilidade'],
                'cidade'       => $ongDecifrada['cidade'],
                'estado'       => $row['sigla_estado'],
            ];

            $resposta = ['ok' => true, 'msg' => 'Login realizado! Redirecionando...', 'redirect' => 'home_ong.php'];
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
        <input type="text" id="email" name="email" placeholder="contato@ong.org.br">
        <div class="erro-campo" id="erroEmail"></div>
        <label for="senha">Senha</label>
        <div class="campo-senha">
            <input type="password" id="senha" name="senha" placeholder="Mínimo 12 caracteres">
            <button type="button" class="btn-olho" id="btnOlho">Mostrar</button>
        </div>
        <div class="erro-campo" id="erroSenha"></div>
        <div class="g-recaptcha" data-sitekey="<?php echo $RECAPTCHA_SITE_KEY; ?>"></div>
        <div class="mensagem" id="mensagem"></div>
        <button type="submit" id="btnEntrar">Entrar</button>
    </form>
    <hr class="separador">
    <div class="links">Não tem cadastro? <a href="cadastro_ong.php">Cadastre sua ONG</a></div>
    <div class="links" style="margin-top:8px;">É um doador? <a href="login.php">Entrar aqui</a></div>
    <div class="links" style="margin-top:8px;"><a href="recuperacao_de_senha.php">Esqueci minha senha</a></div>
</div>
<script>
var REGEX_EMAIL = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/;
var REGEX_SENHA = /^.{12,}$/;
var campoEmail  = document.getElementById('email');
var campoSenha  = document.getElementById('senha');
var mensagem    = document.getElementById('mensagem');

document.getElementById('btnOlho').addEventListener('click', function() {
    campoSenha.type = campoSenha.type === 'password' ? 'text' : 'password';
    this.textContent = campoSenha.type === 'password' ? 'Mostrar' : 'Ocultar';
});

function mostrarErroCampo(id, erroId, msg) {
    document.getElementById(id).classList.add('erro');
    var el = document.getElementById(erroId); el.textContent = '❌ ' + msg; el.style.display = 'block';
}
function limparErroCampo(id, erroId) {
    document.getElementById(id).classList.remove('erro');
    document.getElementById(erroId).style.display = 'none';
}

campoEmail.addEventListener('input', () => limparErroCampo('email', 'erroEmail'));
campoSenha.addEventListener('input', () => limparErroCampo('senha', 'erroSenha'));

document.getElementById('formLogin').addEventListener('submit', async function(e) {
    e.preventDefault();
    var emailOk = true, senhaOk = true;
    if (!REGEX_EMAIL.test(campoEmail.value.trim())) { mostrarErroCampo('email','erroEmail','E-mail inválido.'); emailOk=false; }
    if (!REGEX_SENHA.test(campoSenha.value))        { mostrarErroCampo('senha','erroSenha','Mínimo 12 caracteres.'); senhaOk=false; }
    if (!emailOk || !senhaOk) return;
    if (grecaptcha.getResponse() === '') { mensagem.textContent='Confirme o CAPTCHA.'; mensagem.className='mensagem erro'; return; }
    mensagem.className = 'mensagem';
    var btn = document.getElementById('btnEntrar'); btn.disabled=true; btn.textContent='Aguarde...';
    var dados = new FormData();
    dados.append('email', campoEmail.value.trim());
    dados.append('senha', campoSenha.value);
    dados.append('g-recaptcha-response', grecaptcha.getResponse());
    try {
        var res  = await fetch('login_ong.php', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:dados });
        var json = await res.json();
        mensagem.textContent = json.msg; mensagem.className = 'mensagem ' + (json.ok ? 'sucesso' : 'erro');
        if (json.ok) setTimeout(() => { window.location.href = json.redirect; }, 1000);
    } catch (erro) { mensagem.textContent='Erro de conexão.'; mensagem.className='mensagem erro'; }
    finally { btn.disabled=false; btn.textContent='Entrar'; grecaptcha.reset(); }
});
</script>
</body>
</html>