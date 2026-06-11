<?php
ob_start();
session_start();

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

require '../../vendor/autoload.php';
require '../../src/api/database.php';
require '../../src/api/valida_senha.php';
require '../../src/api/mailer.php';
require_once '../../src/crypto/crypto_helpers.php';
require_once '../../config/recaptcha.php';

function validarCNPJ(string $cnpj): bool {
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    if (strlen($cnpj) !== 14) return false;
    if (preg_match('/(\d)\1{13}/', $cnpj)) return false;
    $soma = 0;
    $peso = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    for ($i = 0; $i < 12; $i++) $soma += $cnpj[$i] * $peso[$i];
    $resto = $soma % 11; $digito1 = $resto < 2 ? 0 : 11 - $resto;
    $soma = 0;
    $peso = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    for ($i = 0; $i < 13; $i++) $soma += $cnpj[$i] * $peso[$i];
    $resto = $soma % 11; $digito2 = $resto < 2 ? 0 : 11 - $resto;
    return ($cnpj[12] == $digito1 && $cnpj[13] == $digito2);
}

$REGEX_EMAIL = '/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/';
$REGEX_SENHA = '/^.{12,}$/';
$REGEX_CNPJ  = '/^\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}$/';
$REGEX_CEP   = '/^\d{5}-?\d{3}$/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $ehJSON      = str_contains($contentType, 'application/json');

    // CAPTCHA via GET — fora do payload cifrado
    $captcha = trim($_GET['captcha'] ?? '');

    if (empty($captcha)) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'msg' => 'Confirme o CAPTCHA.']);
        exit;
    }

    $verificacao     = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" . $RECAPTCHA_SECRET_KEY . "&response=" . $captcha);
    $respostaCaptcha = json_decode($verificacao);

    if (!$respostaCaptcha || !$respostaCaptcha->success) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'msg' => 'CAPTCHA inválido. Tente novamente.']);
        exit;
    }

    if ($ehJSON) {
        logCrypto('[CRYPTO] recebendo dados cifrados ONG');
        $dados = decifrarPostHibrido();
        if ($dados === false) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'msg' => 'Erro ao decifrar pacote.']);
            exit;
        }
        logCrypto('[CRYPTO] dados decifrados com sucesso');

        $nome      = trim($dados['nome']      ?? '');
        $cnpj      = trim($dados['cnpj']      ?? '');
        $email     = trim($dados['email']     ?? '');
        $cep       = trim($dados['cep']       ?? '');
        $endereco  = trim($dados['endereco']  ?? '');
        $cidade    = trim($dados['cidade']    ?? '');
        $estado    = trim($dados['estado']    ?? '');
        $area      = trim($dados['area']      ?? '');
        $descricao = trim($dados['descricao'] ?? '');
        $senha     = $dados['senha']          ?? '';
        $senha2    = $dados['senha2']         ?? '';

        logCrypto("[CADASTRO_ONG] nome={$nome} | email={$email} | cnpj={$cnpj}");
    } else {
        $nome      = trim($_POST['nome']      ?? '');
        $cnpj      = trim($_POST['cnpj']      ?? '');
        $email     = trim($_POST['email']     ?? '');
        $cep       = trim($_POST['cep']       ?? '');
        $endereco  = trim($_POST['endereco']  ?? '');
        $cidade    = trim($_POST['cidade']    ?? '');
        $estado    = trim($_POST['estado']    ?? '');
        $area      = trim($_POST['area']      ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $senha     = $_POST['senha']          ?? '';
        $senha2    = $_POST['senha2']         ?? '';
    }

    if (strlen($nome) < 3) {
        $r = ['ok' => false, 'campo' => 'nome', 'msg' => 'Nome deve ter pelo menos 3 caracteres.'];
    } elseif (!preg_match($REGEX_CNPJ, $cnpj)) {
        $r = ['ok' => false, 'campo' => 'cnpj', 'msg' => 'CNPJ inválido. Use: 00.000.000/0000-00'];
    } elseif (!validarCNPJ($cnpj)) {
        $r = ['ok' => false, 'campo' => 'cnpj', 'msg' => 'CNPJ inválido. Os dígitos verificadores não conferem.'];
    } elseif (!preg_match($REGEX_EMAIL, $email)) {
        $r = ['ok' => false, 'campo' => 'email', 'msg' => 'E-mail inválido.'];
    } elseif (!preg_match($REGEX_CEP, $cep)) {
        $r = ['ok' => false, 'campo' => 'cep', 'msg' => 'CEP inválido. Ex: 80000-000'];
    } elseif (empty($endereco)) {
        $r = ['ok' => false, 'campo' => 'endereco', 'msg' => 'Informe o endereço.'];
    } elseif (empty($cidade)) {
        $r = ['ok' => false, 'campo' => 'cidade', 'msg' => 'Informe a cidade.'];
    } elseif (empty($estado)) {
        $r = ['ok' => false, 'campo' => 'estado', 'msg' => 'Selecione o estado.'];
    } elseif (empty($area)) {
        $r = ['ok' => false, 'campo' => 'area', 'msg' => 'Selecione a área de atuação.'];
    } elseif (!preg_match($REGEX_SENHA, $senha)) {
        $r = ['ok' => false, 'campo' => 'senha', 'msg' => 'Senha deve ter pelo menos 12 caracteres.'];
    } elseif (validarSenhaForte($senha) !== true) {
        $r = ['ok' => false, 'campo' => 'senha', 'msg' => validarSenhaForte($senha)];
    } elseif ($senha !== $senha2) {
        $r = ['ok' => false, 'campo' => 'senha2', 'msg' => 'As senhas não coincidem.'];
    } else {
        $cnpj_limpo = preg_replace('/\D/', '', $cnpj);
        $cep_limpo  = preg_replace('/\D/', '', $cep);

        // Verifica duplicata por email em usuario OU cnpj em ong
        $stmtDup = $pdo->prepare("SELECT id_usuario FROM usuario WHERE email = ?");
        $stmtDup->execute([$email]);
        $dupUsuario = $stmtDup->fetch();

        $stmtDupCnpj = $pdo->prepare("SELECT id_ong FROM ong WHERE cnpj = ?");
        $stmtDupCnpj->execute([$cnpj_limpo]);
        $dupCnpj = $stmtDupCnpj->fetch();

        if ($dupUsuario || $dupCnpj) {
            $r = ['ok' => false, 'msg' => 'Este e-mail ou CNPJ já está cadastrado.'];
        } else {
            $hash  = password_hash($senha, PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(32));

            try {
                $pdo->beginTransaction();

                // 1. Insere em usuario (tabela mãe) — igual ao doador
                $pdo->prepare("
                    INSERT INTO usuario (nome, email, senha_hash, token_confirmacao, status_cadastro, tipo)
                    VALUES (?, ?, ?, ?, 'pendente', 'ong')
                ")->execute([$nome, $email, $hash, $token]);

                $idUsuario = $pdo->lastInsertId();

                // 2. Cifra campos sensíveis
                $enc = cifrarOng([
                    'nome'         => $nome,
                    'email'        => $email,
                    'area_atuacao' => $area,
                    'cidade'       => $cidade,
                    'endereco'     => $endereco,
                    'descricao'    => $descricao,
                    'localizacao'  => $cep_limpo,
                ]);

                logCrypto("[STORAGE] iv_dados={$enc['iv_dados']}");

                // 3. Insere em ong vinculado ao usuario
                $pdo->prepare("
                    INSERT INTO ong
                        (id_usuario, nome, cnpj, email, localizacao, endereco, cidade,
                         sigla_estado, area_atuacao, descricao,
                         classificacao_risco, status_elegibilidade,
                         iv_dados, chave_aes_cifrada)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'continuo', 'pendente', ?, ?)
                ")->execute([
                    $idUsuario,
                    $enc['nome'],
                    $cnpj_limpo,
                    $enc['email'],
                    $enc['localizacao'],
                    $enc['endereco'],
                    $enc['cidade'],
                    $estado,
                    $enc['area_atuacao'],
                    $enc['descricao'],
                    $enc['iv_dados'],
                    $enc['chave_aes_cifrada'],
                ]);

                $pdo->commit();
                logCrypto("[OK] ONG inserida id_usuario={$idUsuario} com dados cifrados");

                if (enviarEmailConfirmacao($email, $nome, $token)) {
                    $r = ['ok' => true, 'msg' => "ONG <strong>$nome</strong> cadastrada! Verifique seu e-mail para confirmar a conta."];
                } else {
                    $r = ['ok' => false, 'msg' => 'Cadastro salvo, mas falha ao enviar e-mail.'];
                }

            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log("cadastro_ong.php PDOException: " . $e->getMessage());
                $r = ['ok' => false, 'msg' => 'Erro interno ao cadastrar. Tente novamente.'];
            }
        }
    }

    header('Content-Type: application/json');
    echo json_encode($r);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de ONG — Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/cadastro_ong.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
<div class="container">

    <div class="cabecalho">
        <div class="icone">🏢</div>
        <h2>Cadastro de ONG</h2>
        <p>Registre sua organização para receber doações de suprimentos</p>
    </div>

    <div class="aviso">
        ⚠️ Após o cadastro, sua ONG passará por <strong>validação do administrador</strong> antes de ser ativada.
    </div>

    <div class="mensagem" id="mensagem"></div>

    <form id="formOng">

        <div class="titulo-secao">1. Dados da organização</div>

        <div class="grupo">
            <label for="nome">Nome da ONG *</label>
            <input type="text" id="nome" name="nome" placeholder="Nome completo da organização">
            <div class="erro-campo" id="erroNome"></div>
        </div>

        <div class="grid-2">
            <div class="grupo">
                <label for="cnpj">CNPJ *</label>
                <input type="text" id="cnpj" name="cnpj" placeholder="00.000.000/0000-00" maxlength="18">
                <div class="erro-campo" id="erroCnpj"></div>
            </div>
            <div class="grupo">
                <label for="area">Área de atuação *</label>
                <select id="area" name="area">
                    <option value="">Selecione...</option>
                    <option>Alimentação</option>
                    <option>Saúde</option>
                    <option>Moradia</option>
                    <option>Educação</option>
                    <option>Desastres naturais</option>
                    <option>Assistência social</option>
                    <option>Criança e adolescente</option>
                    <option>Idosos</option>
                    <option>Refugiados</option>
                    <option>Outros</option>
                </select>
                <div class="erro-campo" id="erroArea"></div>
            </div>
        </div>

        <div class="grupo">
            <label for="descricao">Descrição (opcional)</label>
            <textarea id="descricao" name="descricao" placeholder="Descreva brevemente o trabalho da organização..."></textarea>
        </div>

        <div class="titulo-secao">2. Contato</div>

        <div class="grid-2">
            <div class="grupo">
                <label for="email">E-mail *</label>
                <input type="text" id="email" name="email" placeholder="contato@ong.org.br">
                <div class="erro-campo" id="erroEmail"></div>
            </div>
        </div>

        <div class="titulo-secao">3. Endereço</div>

        <div class="grid-2">
            <div class="grupo">
                <label for="cep">CEP *</label>
                <input type="text" id="cep" name="cep" placeholder="00000-000" maxlength="9">
                <div class="erro-campo" id="erroCep"></div>
            </div>
            <div class="grupo">
                <label for="estado">Estado *</label>
                <select id="estado" name="estado">
                    <option value="">Selecione...</option>
                    <option>AC</option><option>AL</option><option>AM</option><option>AP</option>
                    <option>BA</option><option>CE</option><option>DF</option><option>ES</option>
                    <option>GO</option><option>MA</option><option>MG</option><option>MS</option>
                    <option>MT</option><option>PA</option><option>PB</option><option>PE</option>
                    <option>PI</option><option>PR</option><option>RJ</option><option>RN</option>
                    <option>RO</option><option>RR</option><option>RS</option><option>SC</option>
                    <option>SE</option><option>SP</option><option>TO</option>
                </select>
                <div class="erro-campo" id="erroEstado"></div>
            </div>
        </div>

        <div class="grupo">
            <label for="endereco">Endereço completo *</label>
            <input type="text" id="endereco" name="endereco" placeholder="Rua, número, bairro">
            <div class="erro-campo" id="erroEndereco"></div>
        </div>

        <div class="grupo">
            <label for="cidade">Cidade *</label>
            <input type="text" id="cidade" name="cidade" placeholder="Nome da cidade">
            <div class="erro-campo" id="erroCidade"></div>
        </div>

        <div class="titulo-secao">4. Senha de acesso</div>

        <div class="grid-2">
            <div class="grupo">
                <label for="senha">Senha *</label>
                <div class="campo-senha">
                    <input type="password" id="senha" name="senha" placeholder="Mínimo 12 caracteres">
                    <button type="button" class="btn-olho" id="olho1">Mostrar</button>
                </div>
                <div class="erro-campo" id="erroSenha"></div>
            </div>
            <div class="grupo">
                <label for="senha2">Confirmar senha *</label>
                <div class="campo-senha">
                    <input type="password" id="senha2" name="senha2" placeholder="Repita a senha">
                    <button type="button" class="btn-olho" id="olho2">Mostrar</button>
                </div>
                <div class="g-recaptcha" data-sitekey="<?php echo $RECAPTCHA_SITE_KEY; ?>"></div>
                <div class="erro-campo" id="erroSenha2"></div>
            </div>
        </div>

        <button type="submit" id="btnCadastrar">Cadastrar ONG</button>

    </form>
</div>

<script type="module">
const pubDer = await fetch('../../src/crypto/public.der').then(r => r.arrayBuffer());
const pub = await crypto.subtle.importKey(
    'spki', pubDer, { name: 'RSA-OAEP', hash: 'SHA-1' }, false, ['encrypt']
);
console.log('[S.3.1a] Chave pública obtida do servidor');

document.getElementById('cnpj').addEventListener('input', function() {
    var v = this.value.replace(/\D/g, '').substring(0, 14);
    v = v.replace(/^(\d{2})(\d)/, '$1.$2');
    v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
    v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
    v = v.replace(/(\d{4})(\d)/, '$1-$2');
    this.value = v;
});

document.getElementById('cep').addEventListener('input', function() {
    var v = this.value.replace(/\D/g, '').substring(0, 8);
    v = v.replace(/(\d{5})(\d)/, '$1-$2');
    this.value = v;
});

document.getElementById('olho1').addEventListener('click', function() {
    var i = document.getElementById('senha');
    i.type = i.type === 'password' ? 'text' : 'password';
    this.textContent = i.type === 'password' ? 'Mostrar' : 'Ocultar';
});
document.getElementById('olho2').addEventListener('click', function() {
    var i = document.getElementById('senha2');
    i.type = i.type === 'password' ? 'text' : 'password';
    this.textContent = i.type === 'password' ? 'Mostrar' : 'Ocultar';
});

var REGEX_EMAIL = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/;
var REGEX_SENHA = /^.{12,}$/;
var REGEX_CNPJ  = /^\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}$/;
var REGEX_CEP   = /^\d{5}-?\d{3}$/;

function mostrarErro(inputId, erroId, msg) {
    document.getElementById(inputId).classList.add('erro');
    var el = document.getElementById(erroId);
    el.textContent = '❌ ' + msg; el.style.display = 'block';
}
function limparErro(inputId, erroId) {
    document.getElementById(inputId).classList.remove('erro');
    document.getElementById(erroId).style.display = 'none';
}
['nome','cnpj','email','cep','endereco','cidade','senha','senha2'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener('input', function() {
        limparErro(id, 'erro' + id.charAt(0).toUpperCase() + id.slice(1));
    });
});

document.getElementById('formOng').addEventListener('submit', async function(e) {
    e.preventDefault();
    var tudo_ok = true;

    if (document.getElementById('nome').value.trim().length < 3)
        { mostrarErro('nome','erroNome','Nome deve ter pelo menos 3 caracteres.'); tudo_ok=false; }
    if (!REGEX_CNPJ.test(document.getElementById('cnpj').value.trim()))
        { mostrarErro('cnpj','erroCnpj','CNPJ inválido. Use: 00.000.000/0000-00'); tudo_ok=false; }
    if (!REGEX_EMAIL.test(document.getElementById('email').value.trim()))
        { mostrarErro('email','erroEmail','E-mail inválido.'); tudo_ok=false; }
    if (!REGEX_CEP.test(document.getElementById('cep').value.trim()))
        { mostrarErro('cep','erroCep','CEP inválido. Ex: 80000-000'); tudo_ok=false; }
    if (!document.getElementById('endereco').value.trim())
        { mostrarErro('endereco','erroEndereco','Informe o endereço.'); tudo_ok=false; }
    if (!document.getElementById('cidade').value.trim())
        { mostrarErro('cidade','erroCidade','Informe a cidade.'); tudo_ok=false; }
    if (!document.getElementById('estado').value)
        { mostrarErro('estado','erroEstado','Selecione o estado.'); tudo_ok=false; }
    if (!document.getElementById('area').value)
        { mostrarErro('area','erroArea','Selecione a área de atuação.'); tudo_ok=false; }
    if (!REGEX_SENHA.test(document.getElementById('senha').value))
        { mostrarErro('senha','erroSenha','Senha deve ter pelo menos 12 caracteres.'); tudo_ok=false; }
    if (document.getElementById('senha').value !== document.getElementById('senha2').value)
        { mostrarErro('senha2','erroSenha2','As senhas não coincidem.'); tudo_ok=false; }

    if (!tudo_ok) {
        var m = document.getElementById('mensagem');
        m.textContent = 'Corrija os campos marcados em vermelho.';
        m.className = 'mensagem erro'; return;
    }

    const captchaToken = window.grecaptcha ? window.grecaptcha.getResponse() : '';
    if (!captchaToken) {
        var m = document.getElementById('mensagem');
        m.textContent = 'Confirme o CAPTCHA.';
        m.className = 'mensagem erro'; return;
    }

    var btn = document.getElementById('btnCadastrar');
    btn.disabled = true; btn.textContent = 'Aguarde...';

    try {
        const aes    = await crypto.subtle.generateKey({ name: 'AES-GCM', length: 256 }, true, ['encrypt']);
        const aesRaw = await crypto.subtle.exportKey('raw', aes);
        console.log('[S.3.1b] Chave AES-256 gerada');

        const dadosObj = {
            nome:      document.getElementById('nome').value.trim(),
            cnpj:      document.getElementById('cnpj').value.trim(),
            email:     document.getElementById('email').value.trim(),
            cep:       document.getElementById('cep').value.trim(),
            endereco:  document.getElementById('endereco').value.trim(),
            cidade:    document.getElementById('cidade').value.trim(),
            estado:    document.getElementById('estado').value,
            area:      document.getElementById('area').value,
            descricao: document.getElementById('descricao').value.trim(),
            senha:     document.getElementById('senha').value,
            senha2:    document.getElementById('senha2').value,
        };

        const iv   = crypto.getRandomValues(new Uint8Array(12));
        const msg  = new TextEncoder().encode(JSON.stringify(dadosObj));
        const data = await crypto.subtle.encrypt({ name: 'AES-GCM', iv }, aes, msg);
        const key  = await crypto.subtle.encrypt({ name: 'RSA-OAEP' }, pub, aesRaw);

        console.log('[S.3.1c] Chave AES cifrada com RSA-OAEP');
        console.log('[S.3.1d] Dados cifrados com AES-GCM');

        const b64 = buf => btoa(String.fromCharCode(...new Uint8Array(buf)));
        const url = 'cadastro_ong.php?captcha=' + encodeURIComponent(captchaToken);

        const res = await fetch(url, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ key: b64(key), iv: b64(iv), data: b64(data) })
        });

        const json = await res.json();
        var m = document.getElementById('mensagem');
        m.innerHTML = json.msg;
        m.className = 'mensagem ' + (json.ok ? 'sucesso' : 'erro');

        if (json.ok) {
            if (window.grecaptcha) window.grecaptcha.reset();
            const emailVal = dadosObj.email;
            this.reset();
            setTimeout(() => {
                window.location.href = 'cadastro_concluido.php?email='
                    + encodeURIComponent(emailVal) + '&tipo=ong';
            }, 2000);
        } else {
            if (window.grecaptcha) window.grecaptcha.reset();
        }

    } catch (err) {
        console.error('[CRYPTO] Erro:', err);
        var m = document.getElementById('mensagem');
        m.textContent = 'Erro de conexão ou criptografia: ' + err.message;
        m.className = 'mensagem erro';
        if (window.grecaptcha) window.grecaptcha.reset();
    } finally {
        btn.disabled = false; btn.textContent = 'Cadastrar ONG';
    }
});
</script>
</body>
</html>