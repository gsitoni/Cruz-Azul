<?php
ob_start();
// ============================================================
//  cadastro.php  —  public/pages/cadastro.php
// ============================================================
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

require '../../vendor/autoload.php';
require '../../src/api/database.php';
require '../../src/api/mailer.php';
require '../../src/api/valida_senha.php';
require '../../src/crypto/crypto_helpers.php';
require_once '../../config/recaptcha.php';

function validarCPF(string $cpf): bool {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) !== 11 || preg_match('/(\d)\1{10}/', $cpf)) return false;
    $soma = 0;
    for ($i = 0; $i < 9; $i++) $soma += $cpf[$i] * (10 - $i);
    $r = $soma % 11; $d1 = $r < 2 ? 0 : 11 - $r;
    $soma = 0;
    for ($i = 0; $i < 10; $i++) $soma += $cpf[$i] * (11 - $i);
    $r = $soma % 11; $d2 = $r < 2 ? 0 : 11 - $r;
    return ((int)$cpf[9] === $d1 && (int)$cpf[10] === $d2);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ct     = $_SERVER['CONTENT_TYPE'] ?? '';
    $isJSON = str_contains($ct, 'application/json');

    if ($isJSON) {
        logCrypto('[CRYPTO] recebendo dados cifrados');
        $dados = decifrarPostHibrido();
        if ($dados === false) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => 'Erro ao decifrar pacote.']);
            exit;
        }
        logCrypto('[CRYPTO] dados decifrados com sucesso');
    } else {
        $dados = $_POST;
    }

    $nome            = trim(strip_tags($dados['nome']            ?? ''));
    $email           = filter_var(trim($dados['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $senha           = $dados['senha']           ?? '';
    $lgpd            = $dados['lgpd']            ?? '';
    $cpf             = preg_replace('/[^0-9]/', '', $dados['cpf'] ?? '');
    $telefone        = preg_replace('/[^0-9]/', '', $dados['telefone'] ?? '');
    $data_nascimento = trim($dados['data_nascimento'] ?? '');

    logCrypto("[CADASTRO] nome=$nome | email=$email | cpf=$cpf | tel=$telefone | nasc=$data_nascimento");

    $idade = 0;
    if (!empty($data_nascimento)) {
        $idade = (new DateTime())->diff(new DateTime($data_nascimento))->y;
    }

    $resultadoSenha = validarSenhaForte($senha);

    if (empty($nome) || empty($email) || empty($senha) || empty($cpf) || empty($telefone) || empty($data_nascimento)) {
        $resposta = ['ok' => false, 'msg' => 'Preencha todos os campos.'];
    } elseif (!validarCPF($cpf)) {
        $resposta = ['ok' => false, 'msg' => 'CPF inválido.'];
    } elseif ($lgpd !== 'true') {
        $resposta = ['ok' => false, 'msg' => 'Aceite os termos da LGPD.'];
    } elseif (!preg_match('/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/', $email)) {
        $resposta = ['ok' => false, 'msg' => 'E-mail inválido.'];
    } elseif ($idade < 18) {
        $resposta = ['ok' => false, 'msg' => 'Necessário 18 anos ou mais.'];
    } elseif ($resultadoSenha !== true) {
        $resposta = ['ok' => false, 'msg' => $resultadoSenha];
    } else {
        $stmt = $pdo->prepare("SELECT id_usuario FROM usuario WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $resposta = ['ok' => false, 'msg' => 'E-mail já cadastrado.'];
        } else {
            $token = bin2hex(random_bytes(32));
            $hash  = password_hash($senha, PASSWORD_DEFAULT);

            // ── Cifra dados sensíveis para o banco ───────────
            $enc = cifrarDoador([
                'nome'            => $nome,
                'cpf'             => $cpf,
                'telefone'        => $telefone,
                'data_nascimento' => $data_nascimento,
            ]);

            logCrypto("[STORAGE] cifrado: iv_dados={$enc['iv_dados']}");

            $pdo->beginTransaction();

            $pdo->prepare("
                INSERT INTO usuario (nome, email, senha_hash, token_confirmacao, status_cadastro, tipo)
                VALUES (?, ?, ?, ?, 'pendente', 'doador')
            ")->execute([$nome, $email, $hash, $token]);

            $idUsuario = $pdo->lastInsertId();

            $pdo->prepare("
                INSERT INTO doador (id_usuario, cpf, nome, telefone, data_nascimento, iv_dados, chave_aes_cifrada)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $idUsuario,
                $enc['cpf'],
                $enc['nome'],
                $enc['telefone'],
                $enc['data_nascimento'],
                $enc['iv_dados'],
                $enc['chave_aes_cifrada'],
            ]);

            $pdo->commit();
            logCrypto("[OK] usuario id=$idUsuario inserido com dados cifrados");

            if (enviarEmailConfirmacao($email, $nome, $token)) {
                $resposta = ['ok' => true, 'msg' => "Cadastro realizado! Verifique seu e-mail <strong>$email</strong>."];
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
    <title>Cadastro — Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/cadastro.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
<div class="container">
    <h2>Cadastro</h2>
    <form id="formCadastro">
        <label>Nome</label>
        <input type="text" id="nome" name="nome" required>
        <label>CPF (Apenas números)</label>
        <input type="text" id="cpf" name="cpf" maxlength="14" required>
        <label>E-mail</label>
        <input type="email" id="email" name="email" required>
        <label>Telefone / WhatsApp</label>
        <input type="text" id="telefone" name="telefone" maxlength="15" required>
        <label>Data de Nascimento</label>
        <input type="date" id="data_nascimento" name="data_nascimento" required>
        <label>Senha</label>
        <input type="password" id="senha" name="senha" placeholder="Mínimo 12 caracteres" required>
        <label>Confirmar Senha</label>
        <input type="password" id="confirmarSenha" placeholder="Repita a senha" required>
        <div class="lgpd-box">
            <input type="checkbox" id="lgpd" required>
            <label for="lgpd">Aceito os <a href="privacidade.php" target="_blank">Termos de Privacidade</a>.</label>
        </div>
        <div class="g-recaptcha" data-sitekey="<?php echo $RECAPTCHA_SITE_KEY; ?>"></div>
        <div class="msg" id="mensagem"></div>
        <button type="submit" id="btnCadastrar">Cadastrar</button>
    </form>
</div>
<script type="module">
const pubDer = await fetch('../../src/crypto/public.der').then(r => r.arrayBuffer());
const pub    = await crypto.subtle.importKey('spki', pubDer, { name: 'RSA-OAEP', hash: 'SHA-1' }, false, ['encrypt']);

document.getElementById('formCadastro').addEventListener('submit', async function(e) {
    e.preventDefault();
    const msgDiv = document.getElementById('mensagem');
    const btn    = document.getElementById('btnCadastrar');
    const senha  = document.getElementById('senha').value;
    const conf   = document.getElementById('confirmarSenha').value;
    const lgpd   = document.getElementById('lgpd').checked;
    const nasc   = document.getElementById('data_nascimento').value;

    if (senha !== conf)  { mostrar('As senhas não coincidem!', 'erro'); return; }
    if (!lgpd)           { mostrar('Aceite a LGPD.', 'erro'); return; }
    if (nasc) {
        const hoje = new Date(), dn = new Date(nasc);
        let age = hoje.getFullYear() - dn.getFullYear();
        if (hoje.getMonth() - dn.getMonth() < 0 || (hoje.getMonth() === dn.getMonth() && hoje.getDate() < dn.getDate())) age--;
        if (age < 18) { mostrar('Necessário 18 anos ou mais.', 'erro'); return; }
    }

    btn.disabled = true; btn.textContent = 'Aguarde...';

    try {
        const aes    = await crypto.subtle.generateKey({ name: 'AES-GCM', length: 256 }, true, ['encrypt']);
        const aesRaw = await crypto.subtle.exportKey('raw', aes);
        const iv     = crypto.getRandomValues(new Uint8Array(12));

        const obj = {
            nome:            document.getElementById('nome').value.trim(),
            email:           document.getElementById('email').value.trim(),
            cpf:             document.getElementById('cpf').value.trim(),
            telefone:        document.getElementById('telefone').value.trim(),
            senha,
            lgpd:            String(lgpd),
            data_nascimento: nasc,
        };

        const enc  = await crypto.subtle.encrypt({ name: 'AES-GCM', iv }, aes, new TextEncoder().encode(JSON.stringify(obj)));
        const key  = await crypto.subtle.encrypt({ name: 'RSA-OAEP' }, pub, aesRaw);
        const b64  = buf => btoa(String.fromCharCode(...new Uint8Array(buf)));

        console.log('[S.3.1b] Chave AES gerada:', aes);
        console.log('[S.3.1c] Chave cifrada com RSA');
        console.log('[S.3.1d] Dados cifrados com AES-GCM');

        const res  = await fetch('cadastro.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ key: b64(key), iv: b64(iv), data: b64(enc) })
        });
        const json = await res.json();
        if (json.ok) {
            window.location.href = 'cadastro_concluido.php?email=' + encodeURIComponent(obj.email) + '&tipo=usuario';
        } else {
            mostrar(json.msg, 'erro');
        }
    } catch(err) {
        mostrar('Erro: ' + err.message, 'erro');
    } finally {
        btn.disabled = false; btn.textContent = 'Cadastrar';
    }
});

function mostrar(t, c) { const m = document.getElementById('mensagem'); m.innerHTML = t; m.className = 'msg ' + c; }
</script>
</body>
</html>