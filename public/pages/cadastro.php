<?php
ob_start();
// ============================================================
//  cadastro.php — public/pages/cadastro.php
//  Criptografia híbrida seguindo o PDF do professor:
//  - JS lê public.der como arrayBuffer (binário)
//  - PHP decifra com private.pem via openssl_private_decrypt
// ============================================================

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

require '../../vendor/autoload.php';
require '../../src/api/database.php';
require '../../src/api/mailer.php';
require '../../src/api/valida_senha.php';
require_once '../../config/recaptcha.php';

// ── Log no formato usuario:hostname>mensagem (S.3.1f) ────────
function logCrypto(string $msg): void {
    $usuario  = get_current_user();
    $hostname = gethostname();
    error_log("{$usuario}:{$hostname}>{$msg}");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── Detecta se veio como JSON cifrado ────────────────────
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $ehJSON      = str_contains($contentType, 'application/json');

    if ($ehJSON) {
        // ── Fluxo criptografado (PDF do professor) ────────────
        $in = json_decode(file_get_contents('php://input'), true);

        if (!isset($in['key'], $in['iv'], $in['data'])) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => 'Pacote cifrado inválido.']);
            exit;
        }

        logCrypto('[CRYPTO] recebendo dados cifrados');

        // 1. Decodifica base64
        $keyBytes  = base64_decode($in['key']);   // AES cifrada com RSA
        $ivBytes   = base64_decode($in['iv']);    // IV do AES-GCM
        $dataBytes = base64_decode($in['data']);  // texto cifrado com AES

        // 2. Abre a chave AES com RSA privada (igual ao receber.php do PDF)
        $privPath = __DIR__ . '/../../src/crypto/private.pem';
        if (!file_exists($privPath)) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => 'Chave privada não encontrada.']);
            exit;
        }

        $priv = file_get_contents($privPath);
        $aes  = '';
        $ok   = openssl_private_decrypt(
            $keyBytes,
            $aes,
            $priv,
            OPENSSL_PKCS1_OAEP_PADDING
        );

        if (!$ok) {
            logCrypto('[ERRO] falha ao decifrar chave AES com RSA');
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => 'Erro ao decifrar chave de sessão.']);
            exit;
        }

        logCrypto('[CRYPTO] chave AES decifrada com RSA-OAEP');

        // 3. Separa tag GCM (últimos 16 bytes) e decifra dados com AES
        $tag     = substr($dataBytes, -16);
        $cifrado = substr($dataBytes, 0, -16);

        $texto = openssl_decrypt(
            $cifrado,
            'aes-256-gcm',
            $aes,
            OPENSSL_RAW_DATA,
            $ivBytes,
            $tag
        );

        if ($texto === false) {
            logCrypto('[ERRO] falha ao decifrar dados com AES-GCM');
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => 'Erro ao decifrar dados do formulário.']);
            exit;
        }

        logCrypto('[CRYPTO] dados decifrados com AES-GCM');

        $dados = json_decode($texto, true);

        $nome            = trim(strip_tags($dados['nome']           ?? ''));
        $email           = filter_var(trim($dados['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $senha           = $dados['senha']           ?? '';
        $lgpd            = $dados['lgpd']            ?? '';
        $cpf             = preg_replace('/[^0-9]/', '', $dados['cpf'] ?? '');
        $telefone        = preg_replace('/[^0-9]/', '', $dados['telefone'] ?? '');
        $data_nascimento = $dados['data_nascimento'] ?? '';

        logCrypto("[CADASTRO] nome={$nome}");
        logCrypto("[CADASTRO] email={$email}");
        logCrypto("[CADASTRO] cpf={$cpf}");
        logCrypto("[CADASTRO] telefone={$telefone}");
        logCrypto("[CADASTRO] data_nascimento={$data_nascimento}");

    } else {
        // ── Fluxo normal sem criptografia ────────────────────
        $nome            = trim(strip_tags($_POST['nome']           ?? ''));
        $email           = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $senha           = trim($_POST['senha']          ?? '');
        $lgpd            = $_POST['lgpd']               ?? '';
        $cpf             = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
        $telefone        = preg_replace('/[^0-9]/', '', $_POST['telefone'] ?? '');
        $data_nascimento = $_POST['data_nascimento']     ?? '';
    }

    // ── Validações ────────────────────────────────────────────
    $idade = 0;
    if (!empty($data_nascimento)) {
        $dataNascObj = new DateTime($data_nascimento);
        $hoje        = new DateTime();
        $idade       = $hoje->diff($dataNascObj)->y;
    }

    function validarCPF($cpf) {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        if (strlen($cpf) != 11) return false;
        if (preg_match('/(\d)\1{10}/', $cpf)) return false;
        $soma = 0;
        for ($i = 0; $i < 9; $i++) $soma += $cpf[$i] * (10 - $i);
        $resto   = $soma % 11;
        $digito1 = ($resto < 2) ? 0 : 11 - $resto;
        $soma = 0;
        for ($i = 0; $i < 10; $i++) $soma += $cpf[$i] * (11 - $i);
        $resto   = $soma % 11;
        $digito2 = ($resto < 2) ? 0 : 11 - $resto;
        return ($cpf[9] == $digito1 && $cpf[10] == $digito2);
    }

    $resultadoSenha = validarSenhaForte($senha);
    $cpfInvalido    = !validarCPF($cpf);

    if (empty($nome) || empty($email) || empty($senha) || empty($cpf) || empty($telefone) || empty($data_nascimento)) {
        $resposta = ['ok' => false, 'msg' => 'Preencha todos os campos.'];
    } elseif ($cpfInvalido) {
        $resposta = ['ok' => false, 'msg' => 'O CPF informado não é válido.'];
    } elseif ($lgpd !== 'true') {
        $resposta = ['ok' => false, 'msg' => 'Você deve aceitar os termos da LGPD.'];
    } elseif (!preg_match('/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/', $email)) {
        $resposta = ['ok' => false, 'msg' => 'E-mail inválido.'];
    } elseif ($idade < 18) {
        $resposta = ['ok' => false, 'msg' => 'Acesso negado. Necessário 18 anos ou mais.'];
    } elseif ($resultadoSenha !== true) {
        $resposta = ['ok' => false, 'msg' => $resultadoSenha];
    } else {
        $stmt = $pdo->prepare("SELECT id_usuario FROM usuario WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $resposta = ['ok' => false, 'msg' => 'Este e-mail já está cadastrado.'];
        } else {
            $token = bin2hex(random_bytes(32));
            $hash  = password_hash($senha, PASSWORD_DEFAULT);

            $pdo->beginTransaction();

            $stmtUsuario = $pdo->prepare("
                INSERT INTO usuario (nome, email, senha_hash, token_confirmacao, status_cadastro, tipo)
                VALUES (?, ?, ?, ?, 'pendente', 'doador')
            ");
            $stmtUsuario->execute([$nome, $email, $hash, $token]);
            $idUsuario = $pdo->lastInsertId();

            $stmtDoador = $pdo->prepare("
                INSERT INTO doador (id_usuario, cpf, nome, telefone, data_nascimento)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmtDoador->execute([$idUsuario, $cpf, $nome, $telefone, $data_nascimento]);

            $pdo->commit();

            logCrypto("[OK] usuario inserido id={$idUsuario}");

            if (enviarEmailConfirmacao($email, $nome, $token)) {
                $resposta = [
                    'ok'  => true,
                    'msg' => "Cadastro realizado! Verifique seu e-mail <strong>{$email}</strong> para confirmar a conta."
                ];
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
    <title>Cadastro de Usuário</title>
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
// ── Lê public.der como binário (igual ao PDF do professor) ───
const pubDer = await fetch('../../src/crypto/public.der')
    .then(r => r.arrayBuffer());

// ── Importa chave pública RSA (formato DER/SPKI) ─────────────
const pub = await crypto.subtle.importKey(
    'spki',
    pubDer,
    { name: 'RSA-OAEP', hash: 'SHA-1' },
    false,
    ['encrypt']
);

console.log('[S.3.1a] Chave pública obtida do servidor (public.der)');

const form   = document.getElementById('formCadastro');
const msgDiv = document.getElementById('mensagem');
const btnCad = document.getElementById('btnCadastrar');

form.addEventListener('submit', async function(e) {
    e.preventDefault();

    msgDiv.className = 'msg';
    msgDiv.innerHTML = '';

    const senha          = document.getElementById('senha').value;
    const confirmarSenha = document.getElementById('confirmarSenha').value;
    const lgpdChecked    = document.getElementById('lgpd').checked;
    const dataNascimento = document.getElementById('data_nascimento').value;

    if (senha !== confirmarSenha) { mostrarMsg('As senhas não coincidem!', 'erro'); return; }
    if (!lgpdChecked)             { mostrarMsg('Você precisa aceitar a LGPD.', 'erro'); return; }

    if (dataNascimento) {
        const hoje     = new Date();
        const dataNasc = new Date(dataNascimento);
        let idade = hoje.getFullYear() - dataNasc.getFullYear();
        const m = hoje.getMonth() - dataNasc.getMonth();
        if (m < 0 || (m === 0 && hoje.getDate() < dataNasc.getDate())) idade--;
        if (idade < 18) { mostrarMsg('É necessário ter 18 anos ou mais.', 'erro'); return; }
    }

    btnCad.disabled    = true;
    btnCad.textContent = 'Aguarde...';

    try {
        // 1. Gera chave AES-256-GCM (S.3.1b)
        const aes = await crypto.subtle.generateKey(
            { name: 'AES-GCM', length: 256 },
            true,
            ['encrypt']
        );
        const aesRaw = await crypto.subtle.exportKey('raw', aes);
        console.log('[S.3.1b] Chave AES-256 gerada:', aes);
        console.log('[S.3.1b] Chave AES (base64):', btoa(String.fromCharCode(...new Uint8Array(aesRaw))));

        // 2. Monta os dados do formulário como JSON
        const dadosObj = {
            nome:            document.getElementById('nome').value.trim(),
            email:           document.getElementById('email').value.trim(),
            cpf:             document.getElementById('cpf').value.trim(),
            telefone:        document.getElementById('telefone').value.trim(),
            senha:           senha,
            lgpd:            String(lgpdChecked),
            data_nascimento: dataNascimento
        };

        // 3. IV e cifra os dados com AES-GCM (S.3.1d)
        const iv  = crypto.getRandomValues(new Uint8Array(12));
        const msg = new TextEncoder().encode(JSON.stringify(dadosObj));
        const data = await crypto.subtle.encrypt(
            { name: 'AES-GCM', iv },
            aes,
            msg
        );

        // 4. Cifra a chave AES com RSA-OAEP (S.3.1c)
        const key = await crypto.subtle.encrypt(
            { name: 'RSA-OAEP' },
            pub,
            aesRaw
        );

        console.log('[S.3.1c] Chave AES cifrada com RSA-OAEP:', btoa(String.fromCharCode(...new Uint8Array(key))));
        console.log('[S.3.1d] Dados cifrados com AES-GCM:', btoa(String.fromCharCode(...new Uint8Array(data))));
        console.log('[S.3.1e] IV:', btoa(String.fromCharCode(...new Uint8Array(iv))));

        // 5. Monta pacote e envia como JSON (igual ao PDF)
        const pacote = {
            key:  new Uint8Array(key).toBase64(),
            iv:   iv.toBase64(),
            data: new Uint8Array(data).toBase64()
        };

        const res  = await fetch('cadastro.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(pacote)
        });

        const json = await res.json();

        if (json.ok) {
            window.location.href = 'cadastro_concluido.php?email='
                + encodeURIComponent(dadosObj.email) + '&tipo=usuario';
        } else {
            mostrarMsg(json.msg, 'erro');
        }

    } catch (err) {
        console.error('[CRYPTO] Erro:', err);
        mostrarMsg('Erro de conexão ou criptografia: ' + err.message, 'erro');
    } finally {
        btnCad.disabled    = false;
        btnCad.textContent = 'Cadastrar';
    }
});

function mostrarMsg(texto, tipo) {
    msgDiv.innerHTML = texto;
    msgDiv.className = 'msg ' + tipo;
}
</script>

</body>
</html>