<?php
session_start();
ob_start();
 
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
 
require '../../src/api/database.php';
require '../../src/api/valida_senha.php';
require '../../src/api/mailer.php';
require_once '../../config/recaptcha.php';
 
// regex de validação
$REGEX_EMAIL = '/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/';
$REGEX_SENHA = '/^.{12,}$/';
$REGEX_CNPJ  = '/^\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}$/';
$REGEX_CEP   = '/^\d{5}-?\d{3}$/';
 
// ── Validação de CNPJ (dígitos verificadores) ────────────────
function validarCNPJ(string $cnpj): bool {
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    if (strlen($cnpj) !== 14) return false;
    if (preg_match('/(\d)\1{13}/', $cnpj)) return false;
 
    $soma = 0;
    $peso = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    for ($i = 0; $i < 12; $i++) $soma += $cnpj[$i] * $peso[$i];
    $resto   = $soma % 11;
    $digito1 = $resto < 2 ? 0 : 11 - $resto;
 
    $soma = 0;
    $peso = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    for ($i = 0; $i < 13; $i++) $soma += $cnpj[$i] * $peso[$i];
    $resto   = $soma % 11;
    $digito2 = $resto < 2 ? 0 : 11 - $resto;
 
    return ($cnpj[12] == $digito1 && $cnpj[13] == $digito2);
}
 
// processa o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 
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
 
    $captcha = $_POST['g-recaptcha-response'] ?? '';
 
    if (empty($captcha)) {
        echo json_encode(['ok' => false, 'msg' => 'Confirme o CAPTCHA.']);
        exit();
    }
 
    $verificacao = file_get_contents(
        "https://www.google.com/recaptcha/api/siteverify?secret="
        . $RECAPTCHA_SECRET_KEY
        . "&response="
        . $captcha
    );
 
    $respostaCaptcha = json_decode($verificacao);
 
    if (!$respostaCaptcha || !$respostaCaptcha->success) {
        echo json_encode(['ok' => false, 'msg' => 'CAPTCHA inválido.']);
        exit();
    }
 
    // validação campo a campo
    if (strlen($nome) < 3) {
        $r = ['ok' => false, 'campo' => 'nome', 'msg' => 'Nome deve ter pelo menos 3 caracteres.'];
 
    } elseif (!preg_match($REGEX_CNPJ, $cnpj)) {
        $r = ['ok' => false, 'campo' => 'cnpj', 'msg' => 'CNPJ inválido. Use o formato: 00.000.000/0000-00'];
 
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
        $stmt = $pdo->prepare("SELECT id_ong FROM ong WHERE email = ? OR cnpj = ?");
        $stmt->execute([$email, $cnpj_limpo]);
 
        if ($stmt->fetch()) {
            $r = ['ok' => false, 'msg' => 'Este e-mail ou CNPJ já está cadastrado.'];
        } else {
            $hash      = password_hash($senha, PASSWORD_DEFAULT);
            $token     = bin2hex(random_bytes(16));
            $cep_limpo = preg_replace('/\D/', '', $cep);
 
            try {
                $pdo->beginTransaction();
 
                $stmt = $pdo->prepare("
                    INSERT INTO ong
                        (nome, cnpj, email, localizacao, endereco, cidade,
                         sigla_estado, area_atuacao, descricao, senha_hash, token_confirmacao,
                         classificacao_risco, status_elegibilidade)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'continuo', 'pendente')
                ");
 
                $stmt->execute([
                    $nome, $cnpj_limpo, $email, $cep_limpo,
                    $endereco, $cidade, $estado, $area, $descricao, $hash, $token
                ]);
 
                $id_ong = $pdo->lastInsertId();
 
                if (enviarEmailConfirmacao($email, $nome, $token)) {
                    $r = [
                        'ok'  => true,
                        'msg' => "ONG <strong>$nome</strong> cadastrada com sucesso! Verifique seu e-mail para confirmar a conta."
                    ];
                } else {
                    $r = ['ok' => false, 'msg' => 'Cadastro salvo, mas falha ao enviar e-mail.'];
                }
 
                $pdo->commit();
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
 
        <!-- SEÇÃO 1 — DADOS DA ORGANIZAÇÃO -->
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
 
        <!-- SEÇÃO 2 — CONTATO -->
        <div class="titulo-secao">2. Contato</div>
 
        <div class="grid-2">
            <div class="grupo">
                <label for="email">E-mail *</label>
                <input type="text" id="email" name="email" placeholder="contato@ong.org.br">
                <div class="erro-campo" id="erroEmail"></div>
            </div>
        </div>
 
        <!-- SEÇÃO 3 — ENDEREÇO -->
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
 
        <!-- SEÇÃO 4 — SENHA -->
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
 
                <!-- CAPTCHA -->
                <div class="g-recaptcha"
                    data-sitekey="<?php echo $RECAPTCHA_SITE_KEY; ?>">
                </div>
 
                <div class="erro-campo" id="erroSenha2"></div>
            </div>
        </div>
 
        <button type="submit" id="btnCadastrar">Cadastrar ONG</button>
 
    </form>
 
    </div>
 
</div>
 
<script>
    var REGEX_EMAIL = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/;
    var REGEX_SENHA = /^.{12,}$/;
    var REGEX_CNPJ  = /^\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}$/;
    var REGEX_CEP   = /^\d{5}-?\d{3}$/;
 
    // ── Validação de CNPJ com dígitos verificadores ───────────
    function validarCNPJ(cnpj) {
        cnpj = cnpj.replace(/[^\d]/g, '');
        if (cnpj.length !== 14) return false;
        if (/^(\d)\1+$/.test(cnpj)) return false;
 
        var soma = 0, peso = [5,4,3,2,9,8,7,6,5,4,3,2];
        for (var i = 0; i < 12; i++) soma += parseInt(cnpj[i]) * peso[i];
        var d1 = (soma % 11) < 2 ? 0 : 11 - (soma % 11);
 
        soma = 0; peso = [6,5,4,3,2,9,8,7,6,5,4,3,2];
        for (var i = 0; i < 13; i++) soma += parseInt(cnpj[i]) * peso[i];
        var d2 = (soma % 11) < 2 ? 0 : 11 - (soma % 11);
 
        return parseInt(cnpj[12]) === d1 && parseInt(cnpj[13]) === d2;
    }
 
    // ── Máscara automática do CNPJ ────────────────────────────
    document.getElementById('cnpj').addEventListener('input', function() {
        var v = this.value.replace(/\D/g, '').substring(0, 14);
        v = v.replace(/^(\d{2})(\d)/, '$1.$2');
        v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
        v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
        v = v.replace(/(\d{4})(\d)/, '$1-$2');
        this.value = v;
    });
 
    // ── Feedback inline do CNPJ no blur ───────────────────────
    document.getElementById('cnpj').addEventListener('blur', function() {
        var val = this.value.trim();
        if (val === '') return;
        if (!REGEX_CNPJ.test(val)) {
            mostrarErro('cnpj', 'erroCnpj', 'CNPJ inválido. Use: 00.000.000/0000-00');
        } else if (!validarCNPJ(val)) {
            mostrarErro('cnpj', 'erroCnpj', 'CNPJ inválido. Os dígitos verificadores não conferem.');
        } else {
            limparErro('cnpj', 'erroCnpj');
        }
    });
 
    // ── Máscara automática do CEP ─────────────────────────────
    document.getElementById('cep').addEventListener('input', function() {
        var v = this.value.replace(/\D/g, '').substring(0, 8);
        v = v.replace(/(\d{5})(\d)/, '$1-$2');
        this.value = v;
    });
 
    // ── Mostrar/ocultar senhas ────────────────────────────────
    document.getElementById('olho1').addEventListener('click', function() {
        var input = document.getElementById('senha');
        input.type = input.type === 'password' ? 'text' : 'password';
        this.textContent = input.type === 'password' ? 'Mostrar' : 'Ocultar';
    });
 
    document.getElementById('olho2').addEventListener('click', function() {
        var input = document.getElementById('senha2');
        input.type = input.type === 'password' ? 'text' : 'password';
        this.textContent = input.type === 'password' ? 'Mostrar' : 'Ocultar';
    });
 
    // ── Funções de erro por campo ─────────────────────────────
    function mostrarErro(inputId, erroId, msg) {
        document.getElementById(inputId).classList.add('erro');
        var el = document.getElementById(erroId);
        el.textContent = '❌ ' + msg;
        el.style.display = 'block';
    }
 
    function limparErro(inputId, erroId) {
        document.getElementById(inputId).classList.remove('erro');
        document.getElementById(erroId).style.display = 'none';
    }
 
    // limpa erros enquanto o usuário digita
    var campos = ['nome','cnpj','email','cep','endereco','cidade','senha','senha2'];
    campos.forEach(function(id) {
        var el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', function() {
                var erroId = 'erro' + id.charAt(0).toUpperCase() + id.slice(1);
                limparErro(id, erroId);
            });
        }
    });
 
    // ── Envio do formulário ───────────────────────────────────
    document.getElementById('formOng').addEventListener('submit', async function(e) {
        e.preventDefault();
 
        var tudo_ok = true;
 
        if (document.getElementById('nome').value.trim().length < 3) {
            mostrarErro('nome', 'erroNome', 'Nome deve ter pelo menos 3 caracteres.');
            tudo_ok = false;
        }
 
        var cnpjVal = document.getElementById('cnpj').value.trim();
        if (!REGEX_CNPJ.test(cnpjVal)) {
            mostrarErro('cnpj', 'erroCnpj', 'CNPJ inválido. Use: 00.000.000/0000-00');
            tudo_ok = false;
        } else if (!validarCNPJ(cnpjVal)) {
            mostrarErro('cnpj', 'erroCnpj', 'CNPJ inválido. Os dígitos verificadores não conferem.');
            tudo_ok = false;
        }
 
        if (!REGEX_EMAIL.test(document.getElementById('email').value.trim())) {
            mostrarErro('email', 'erroEmail', 'E-mail inválido.');
            tudo_ok = false;
        }
 
        if (!REGEX_CEP.test(document.getElementById('cep').value.trim())) {
            mostrarErro('cep', 'erroCep', 'CEP inválido. Ex: 80000-000');
            tudo_ok = false;
        }
 
        if (document.getElementById('endereco').value.trim() === '') {
            mostrarErro('endereco', 'erroEndereco', 'Informe o endereço.');
            tudo_ok = false;
        }
 
        if (document.getElementById('cidade').value.trim() === '') {
            mostrarErro('cidade', 'erroCidade', 'Informe a cidade.');
            tudo_ok = false;
        }
 
        if (document.getElementById('estado').value === '') {
            mostrarErro('estado', 'erroEstado', 'Selecione o estado.');
            tudo_ok = false;
        }
 
        if (document.getElementById('area').value === '') {
            mostrarErro('area', 'erroArea', 'Selecione a área de atuação.');
            tudo_ok = false;
        }
 
        if (!REGEX_SENHA.test(document.getElementById('senha').value)) {
            mostrarErro('senha', 'erroSenha', 'Senha deve ter pelo menos 12 caracteres.');
            tudo_ok = false;
        }
 
        if (document.getElementById('senha').value !== document.getElementById('senha2').value) {
            mostrarErro('senha2', 'erroSenha2', 'As senhas não coincidem.');
            tudo_ok = false;
        }
 
        if (!tudo_ok) {
            var msg = document.getElementById('mensagem');
            msg.textContent = 'Corrija os campos marcados em vermelho.';
            msg.className   = 'mensagem erro';
            return;
        }
 
        document.getElementById('mensagem').className = 'mensagem';
 
        var btn = document.getElementById('btnCadastrar');
        btn.disabled    = true;
        btn.textContent = 'Aguarde...';
 
        if (grecaptcha.getResponse() === '') {
            var msg = document.getElementById('mensagem');
            msg.textContent = 'Confirme o CAPTCHA.';
            msg.className = 'mensagem erro';
            btn.disabled = false;
            btn.textContent = 'Cadastrar ONG';
            return;
        }
 
        try {
            var res  = await fetch('cadastro_ong.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(this)
            });
 
            var json = await res.json();
 
            var msg = document.getElementById('mensagem');
            msg.innerHTML  = json.msg;
            msg.className  = 'mensagem ' + (json.ok ? 'sucesso' : 'erro');
 
            if (json.ok) {
                grecaptcha.reset();
                this.reset();
                setTimeout(() => {
                    window.location.href = 'cadastro_concluido.php?email='
                        + encodeURIComponent(document.getElementById('email').value.trim())
                        + '&tipo=ong';
                }, 2000);
            }
 
        } catch (erro) {
            var msg = document.getElementById('mensagem');
            msg.textContent = 'Erro de conexão. Tente novamente.';
            msg.className   = 'mensagem erro';
            grecaptcha.reset();
        } finally {
            btn.disabled    = false;
            btn.textContent = 'Cadastrar ONG';
        }
    });
</script>
 
</body>
</html>
