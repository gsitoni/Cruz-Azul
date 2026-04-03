<?php
// ============================================================
//  cadastro_ong.php — Cadastro de ONGs
//  Projeto Cruz Azul — Sistema de Suprimentos
//  Compatível com: database.php, mailer.php, cadastro.php
// ============================================================

require 'test_email_bismark/database.php';

session_start();

// ============================================================
//  REGEX
// ============================================================
define('REGEX_EMAIL',  '/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/');
define('REGEX_SENHA',  '/^.{6,}$/');
define('REGEX_CNPJ',   '/^\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}$/');
define('REGEX_TEL',    '/^\(?\d{2}\)?\s?\d{4,5}-?\d{4}$/');
define('REGEX_CEP',    '/^\d{5}-?\d{3}$/');
define('REGEX_NOME',   '/^.{3,}$/');

// ============================================================
//  AJAX POST
// ============================================================
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome        = trim($_POST['nome']        ?? '');
    $cnpj        = trim($_POST['cnpj']        ?? '');
    $email       = trim($_POST['email']       ?? '');
    $telefone    = trim($_POST['telefone']    ?? '');
    $cep         = trim($_POST['cep']         ?? '');
    $endereco    = trim($_POST['endereco']    ?? '');
    $cidade      = trim($_POST['cidade']      ?? '');
    $estado      = trim($_POST['estado']      ?? '');
    $descricao   = trim($_POST['descricao']   ?? '');
    $area        = trim($_POST['area']        ?? '');
    $senha       = $_POST['senha']            ?? '';
    $senha2      = $_POST['senha2']           ?? '';

    // Validações com regex
    if (!preg_match(REGEX_NOME, $nome)) {
        $r = ['ok' => false, 'campo' => 'nome', 'msg' => 'Nome deve ter pelo menos 3 caracteres.'];
    } elseif (!preg_match(REGEX_CNPJ, $cnpj)) {
        $r = ['ok' => false, 'campo' => 'cnpj', 'msg' => 'CNPJ inválido. Use o formato: 00.000.000/0000-00'];
    } elseif (!preg_match(REGEX_EMAIL, $email)) {
        $r = ['ok' => false, 'campo' => 'email', 'msg' => 'E-mail inválido.'];
    } elseif (!preg_match(REGEX_TEL, $telefone)) {
        $r = ['ok' => false, 'campo' => 'telefone', 'msg' => 'Telefone inválido. Ex: (41) 99999-1234'];
    } elseif (!preg_match(REGEX_CEP, $cep)) {
        $r = ['ok' => false, 'campo' => 'cep', 'msg' => 'CEP inválido. Ex: 80000-000'];
    } elseif (empty($endereco)) {
        $r = ['ok' => false, 'campo' => 'endereco', 'msg' => 'Informe o endereço.'];
    } elseif (empty($cidade)) {
        $r = ['ok' => false, 'campo' => 'cidade', 'msg' => 'Informe a cidade.'];
    } elseif (empty($estado)) {
        $r = ['ok' => false, 'campo' => 'estado', 'msg' => 'Selecione o estado.'];
    } elseif (empty($area)) {
        $r = ['ok' => false, 'campo' => 'area', 'msg' => 'Selecione a área de atuação.'];
    } elseif (!preg_match(REGEX_SENHA, $senha)) {
        $r = ['ok' => false, 'campo' => 'senha', 'msg' => 'Senha deve ter pelo menos 6 caracteres.'];
    } elseif ($senha !== $senha2) {
        $r = ['ok' => false, 'campo' => 'senha2', 'msg' => 'As senhas não coincidem.'];
    } else {
        // Verifica se CNPJ ou e-mail já cadastrado
        $stmt = $pdo->prepare("SELECT id FROM ongs WHERE email = ? OR cnpj = ?");
        $stmt->execute([$email, preg_replace('/\D/', '', $cnpj)]);

        if ($stmt->fetch()) {
            $r = ['ok' => false, 'campo' => 'email', 'msg' => 'E-mail ou CNPJ já cadastrado.'];
        } else {
            $token = bin2hex(random_bytes(32));
            $hash  = password_hash($senha, PASSWORD_DEFAULT);
            $cnpj_limpo = preg_replace('/\D/', '', $cnpj);

            $stmt = $pdo->prepare("
                INSERT INTO ongs
                    (nome, cnpj, email, telefone, cep, endereco, cidade, estado,
                     descricao, area_atuacao, senha, token_confirmacao, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendente')
            ");
            $stmt->execute([
                $nome, $cnpj_limpo, $email, $telefone,
                preg_replace('/\D/', '', $cep),
                $endereco, $cidade, $estado,
                $descricao, $area, $hash, $token
            ]);

            $r = [
                'ok'  => true,
                'msg' => "ONG <strong>{$nome}</strong> cadastrada! Aguarde a validação do administrador."
            ];
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
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --azul:        #1a4fa0;
            --azul-claro:  #2d72d2;
            --azul-bg:     #eaf0fb;
            --cinza-esc:   #1a1f2e;
            --cinza:       #4a5068;
            --cinza-claro: #d0d6e8;
            --branco:      #f7f9fe;
            --card-bg:     #ffffff;
            --vermelho:    #c0392b;
            --vermelho-bg: #fdecea;
            --verde:       #1a7a4a;
            --verde-bg:    #eafaf1;
            --sombra:      0 4px 28px rgba(26,79,160,.11);
        }

        body {
            font-family: 'Sora', sans-serif;
            background: var(--azul-bg);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2.5rem 1rem 3rem;
        }

        /* ===== HEADER ===== */
        header {
            text-align: center;
            margin-bottom: 2rem;
            animation: descer .4s ease both;
        }
        .logo { font-size: 2.8rem; }
        header h1 { font-size: 1.65rem; font-weight: 700; color: var(--azul); margin-top: .3rem; }
        header p  { color: var(--cinza); font-size: .88rem; margin-top: .25rem; }

        /* ===== CARD ===== */
        .card {
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: var(--sombra);
            padding: 2.2rem 2.5rem;
            width: 100%;
            max-width: 620px;
            animation: descer .45s .05s ease both;
        }

        /* ===== SEÇÕES ===== */
        .secao {
            margin-bottom: 1.8rem;
        }
        .secao-titulo {
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--azul);
            border-bottom: 2px solid var(--azul-bg);
            padding-bottom: .5rem;
            margin-bottom: 1.1rem;
        }

        /* ===== GRID ===== */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0 1rem; }
        @media (max-width: 500px) { .grid-2 { grid-template-columns: 1fr; } }

        /* ===== CAMPOS ===== */
        .grupo { margin-bottom: 1rem; }
        .grupo label {
            display: block;
            font-size: .8rem;
            font-weight: 600;
            color: var(--cinza-esc);
            margin-bottom: .35rem;
        }
        .grupo input,
        .grupo select,
        .grupo textarea {
            width: 100%;
            padding: .7rem 1rem;
            border: 1.8px solid var(--cinza-claro);
            border-radius: 10px;
            font-family: 'Sora', sans-serif;
            font-size: .9rem;
            color: var(--cinza-esc);
            background: var(--branco);
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .grupo textarea { resize: vertical; min-height: 80px; }
        .grupo input:focus,
        .grupo select:focus,
        .grupo textarea:focus {
            border-color: var(--azul-claro);
            box-shadow: 0 0 0 3px rgba(45,114,210,.13);
        }
        .grupo input.invalido,
        .grupo select.invalido,
        .grupo textarea.invalido {
            border-color: var(--vermelho);
            box-shadow: 0 0 0 3px rgba(192,57,43,.1);
        }
        .grupo input.valido {
            border-color: var(--verde);
        }

        /* Erro por campo */
        .erro-campo {
            font-size: .74rem;
            font-family: 'DM Mono', monospace;
            color: var(--vermelho);
            margin-top: .28rem;
            display: none;
        }
        .erro-campo.visivel { display: block; }

        /* Wrapper senha + olho */
        .senha-wrap { position: relative; }
        .senha-wrap input { padding-right: 2.6rem; }
        .btn-olho {
            position: absolute; right: .8rem; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; cursor: pointer; font-size: .95rem; padding: 0;
        }

        /* ===== BOTÃO ===== */
        .btn-cadastrar {
            width: 100%;
            padding: .9rem;
            background: var(--azul);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-family: 'Sora', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: .5rem;
            transition: background .2s, transform .1s;
        }
        .btn-cadastrar:hover  { background: var(--azul-claro); }
        .btn-cadastrar:active { transform: scale(.98); }
        .btn-cadastrar:disabled { opacity: .6; cursor: not-allowed; }

        /* ===== MENSAGEM GERAL ===== */
        .msg {
            padding: .9rem 1.1rem;
            border-radius: 10px;
            font-size: .88rem;
            font-weight: 600;
            margin-bottom: 1.3rem;
            display: none;
            align-items: center;
            gap: .5rem;
        }
        .msg.erro    { background: var(--vermelho-bg); color: var(--vermelho); border: 1.5px solid #f0a07a; display: flex; }
        .msg.sucesso { background: var(--verde-bg);    color: var(--verde);    border: 1.5px solid #6dd4a6; display: flex; }

        /* ===== AVISO ADMIN ===== */
        .aviso-admin {
            background: #fffbe6;
            border: 1.5px solid #f5c842;
            border-radius: 10px;
            padding: .8rem 1rem;
            font-size: .82rem;
            color: #7a5c00;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: .5rem;
        }

        /* ===== RODAPÉ ===== */
        .rodape-card {
            text-align: center;
            margin-top: 1.3rem;
            font-size: .83rem;
            color: var(--cinza);
        }
        .rodape-card a { color: var(--azul); font-weight: 700; text-decoration: none; }
        .rodape-card a:hover { text-decoration: underline; }

        footer { margin-top: 2rem; font-size: .76rem; color: var(--cinza); text-align: center; }

        @keyframes descer {
            from { opacity: 0; transform: translateY(-14px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<header>
    <div class="logo">🏢</div>
    <h1>Cadastro de ONG</h1>
    <p>Registre sua organização para receber doações de suprimentos</p>
</header>

<div class="card">

    <!-- Aviso sobre validação do admin -->
    <div class="aviso-admin">
        ⚠️ Após o cadastro, sua ONG passará por <strong>validação do administrador</strong> antes de ser ativada.
    </div>

    <!-- Mensagem geral -->
    <div class="msg" id="mensagem"></div>

    <form id="formOng">

        <!-- 1. DADOS DA ORGANIZAÇÃO -->
        <div class="secao">
            <div class="secao-titulo">1. Dados da organização</div>

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
                <label for="descricao">Descrição da ONG</label>
                <textarea id="descricao" name="descricao" placeholder="Descreva brevemente o trabalho da sua organização..."></textarea>
            </div>
        </div>

        <!-- 2. CONTATO -->
        <div class="secao">
            <div class="secao-titulo">2. Contato</div>

            <div class="grid-2">
                <div class="grupo">
                    <label for="email">E-mail *</label>
                    <input type="text" id="email" name="email" placeholder="contato@ong.org.br">
                    <div class="erro-campo" id="erroEmail"></div>
                </div>
                <div class="grupo">
                    <label for="telefone">Telefone *</label>
                    <input type="text" id="telefone" name="telefone" placeholder="(41) 99999-1234" maxlength="15">
                    <div class="erro-campo" id="erroTelefone"></div>
                </div>
            </div>
        </div>

        <!-- 3. ENDEREÇO -->
        <div class="secao">
            <div class="secao-titulo">3. Endereço</div>

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
        </div>

        <!-- 4. ACESSO -->
        <div class="secao">
            <div class="secao-titulo">4. Senha de acesso</div>

            <div class="grid-2">
                <div class="grupo">
                    <label for="senha">Senha *</label>
                    <div class="senha-wrap">
                        <input type="password" id="senha" name="senha" placeholder="Mínimo 6 caracteres">
                        <button type="button" class="btn-olho" id="olho1">👁️</button>
                    </div>
                    <div class="erro-campo" id="erroSenha"></div>
                </div>
                <div class="grupo">
                    <label for="senha2">Confirmar senha *</label>
                    <div class="senha-wrap">
                        <input type="password" id="senha2" name="senha2" placeholder="Repita a senha">
                        <button type="button" class="btn-olho" id="olho2">👁️</button>
                    </div>
                    <div class="erro-campo" id="erroSenha2"></div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn-cadastrar" id="btnCadastrar">Cadastrar ONG</button>
    </form>

    <div class="rodape-card">
        Já tem cadastro? <a href="login.php">Entrar aqui</a>
    </div>
</div>

<footer>&copy; <?= date('Y') ?> Cruz Azul &mdash; Sistema de Suprimentos</footer>

<script>
// ============================================================
//  REGEX — mesmos do PHP
// ============================================================
const REGEX_EMAIL  = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/;
const REGEX_SENHA  = /^.{6,}$/;
const REGEX_CNPJ   = /^\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}$/;
const REGEX_TEL    = /^\(?\d{2}\)?\s?\d{4,5}-?\d{4}$/;
const REGEX_CEP    = /^\d{5}-?\d{3}$/;
const REGEX_NOME   = /^.{3,}$/;

// ============================================================
//  MÁSCARAS automáticas
// ============================================================
document.getElementById('cnpj').addEventListener('input', function() {
    let v = this.value.replace(/\D/g, '').substring(0, 14);
    v = v.replace(/^(\d{2})(\d)/, '$1.$2');
    v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
    v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
    v = v.replace(/(\d{4})(\d)/, '$1-$2');
    this.value = v;
});

document.getElementById('cep').addEventListener('input', function() {
    let v = this.value.replace(/\D/g, '').substring(0, 8);
    v = v.replace(/(\d{5})(\d)/, '$1-$2');
    this.value = v;
});

document.getElementById('telefone').addEventListener('input', function() {
    let v = this.value.replace(/\D/g, '').substring(0, 11);
    if (v.length <= 10) {
        v = v.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
    } else {
        v = v.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
    }
    this.value = v;
});

// ============================================================
//  VALIDAÇÃO em tempo real (blur + input)
// ============================================================
function mostrarErroCampo(id, msg) {
    const el = document.getElementById(id);
    el.textContent = '❌ ' + msg;
    el.classList.add('visivel');
}
function limparErroCampo(id) {
    const el = document.getElementById(id);
    el.textContent = '';
    el.classList.remove('visivel');
}
function marcarInvalido(inputId) {
    document.getElementById(inputId).classList.add('invalido');
    document.getElementById(inputId).classList.remove('valido');
}
function marcarValido(inputId) {
    document.getElementById(inputId).classList.remove('invalido');
    document.getElementById(inputId).classList.add('valido');
}

const regras = [
    { id: 'nome',      regex: REGEX_NOME,  erro: 'erroNome',     msg: 'Nome deve ter pelo menos 3 caracteres.' },
    { id: 'cnpj',      regex: REGEX_CNPJ,  erro: 'erroCnpj',     msg: 'CNPJ inválido. Use: 00.000.000/0000-00' },
    { id: 'email',     regex: REGEX_EMAIL, erro: 'erroEmail',     msg: 'E-mail inválido. Ex: contato@ong.org.br' },
    { id: 'telefone',  regex: REGEX_TEL,   erro: 'erroTelefone', msg: 'Telefone inválido. Ex: (41) 99999-1234' },
    { id: 'cep',       regex: REGEX_CEP,   erro: 'erroCep',      msg: 'CEP inválido. Ex: 80000-000' },
    { id: 'senha',     regex: REGEX_SENHA, erro: 'erroSenha',    msg: 'Senha deve ter pelo menos 6 caracteres.' },
];

regras.forEach(({ id, regex, erro, msg }) => {
    const el = document.getElementById(id);
    el.addEventListener('blur', () => {
        if (!regex.test(el.value.trim())) {
            marcarInvalido(id); mostrarErroCampo(erro, msg);
        } else {
            marcarValido(id); limparErroCampo(erro);
        }
    });
    el.addEventListener('input', () => { limparErroCampo(erro); el.classList.remove('invalido'); });
});

// Confirmar senha
document.getElementById('senha2').addEventListener('blur', function() {
    if (this.value !== document.getElementById('senha').value) {
        marcarInvalido('senha2'); mostrarErroCampo('erroSenha2', 'As senhas não coincidem.');
    } else {
        marcarValido('senha2'); limparErroCampo('erroSenha2');
    }
});
document.getElementById('senha2').addEventListener('input', function() {
    limparErroCampo('erroSenha2'); this.classList.remove('invalido');
});

// Selects
['area', 'estado'].forEach(id => {
    document.getElementById(id).addEventListener('change', function() {
        if (this.value) { marcarValido(id); limparErroCampo('erro' + id.charAt(0).toUpperCase() + id.slice(1)); }
    });
});

// ============================================================
//  MOSTRAR / OCULTAR SENHA
// ============================================================
function toggleSenha(inputId, btnId) {
    const input = document.getElementById(inputId);
    const btn   = document.getElementById(btnId);
    btn.addEventListener('click', () => {
        input.type = input.type === 'password' ? 'text' : 'password';
        btn.textContent = input.type === 'password' ? '👁️' : '🙈';
    });
}
toggleSenha('senha',  'olho1');
toggleSenha('senha2', 'olho2');

// ============================================================
//  ENVIO AJAX
// ============================================================
const msgDiv = document.getElementById('mensagem');

function mostrarMsg(texto, tipo) {
    msgDiv.innerHTML  = texto;
    msgDiv.className  = 'msg ' + tipo;
}

document.getElementById('formOng').addEventListener('submit', async function(e) {
    e.preventDefault();

    // Valida todos os campos
    let ok = true;

    regras.forEach(({ id, regex, erro, msg }) => {
        const el = document.getElementById(id);
        if (!regex.test(el.value.trim())) {
            marcarInvalido(id); mostrarErroCampo(erro, msg); ok = false;
        }
    });

    // Confirmar senha
    const s1 = document.getElementById('senha').value;
    const s2 = document.getElementById('senha2').value;
    if (s1 !== s2) {
        marcarInvalido('senha2'); mostrarErroCampo('erroSenha2', 'As senhas não coincidem.'); ok = false;
    }

    // Selects obrigatórios
    ['area', 'estado'].forEach(id => {
        const el = document.getElementById(id);
        const erroId = 'erro' + id.charAt(0).toUpperCase() + id.slice(1);
        if (!el.value) { el.classList.add('invalido'); mostrarErroCampo(erroId, 'Campo obrigatório.'); ok = false; }
    });

    // Campos de texto simples
    ['endereco', 'cidade'].forEach(id => {
        const el = document.getElementById(id);
        const erroId = 'erro' + id.charAt(0).toUpperCase() + id.slice(1);
        if (!el.value.trim()) { marcarInvalido(id); mostrarErroCampo(erroId, 'Campo obrigatório.'); ok = false; }
    });

    if (!ok) {
        mostrarMsg('Corrija os campos destacados em vermelho.', 'erro');
        return;
    }

    const btn = document.getElementById('btnCadastrar');
    btn.disabled    = true;
    btn.textContent = 'Aguarde...';
    msgDiv.className = 'msg';

    const dados = new FormData(this);

    try {
        const res  = await fetch('cadastro_ong.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: dados
        });
        const json = await res.json();

        if (json.ok) {
            mostrarMsg(json.msg, 'sucesso');
            this.reset();
            document.querySelectorAll('.valido').forEach(el => el.classList.remove('valido'));
        } else {
            mostrarMsg(json.msg, 'erro');
            if (json.campo) {
                marcarInvalido(json.campo);
                mostrarErroCampo('erro' + json.campo.charAt(0).toUpperCase() + json.campo.slice(1), json.msg);
            }
        }
    } catch (err) {
        mostrarMsg('Erro de conexão. Tente novamente.', 'erro');
    } finally {
        btn.disabled    = false;
        btn.textContent = 'Cadastrar ONG';
    }
});
</script>

</body>
</html>
