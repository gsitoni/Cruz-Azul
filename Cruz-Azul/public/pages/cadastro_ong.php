<?php
session_start();

require '../../src/api/database.php';

// regex de validação
$REGEX_EMAIL = '/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/';
$REGEX_SENHA = '/^.{6,}$/';
$REGEX_CNPJ  = '/^\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}$/';
//$REGEX_TEL   = '/^\(?\d{2}\)?\s?\d{4,5}-?\d{4}$/';
$REGEX_CEP   = '/^\d{5}-?\d{3}$/';

// processa o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome      = trim($_POST['nome']      ?? '');
    $cnpj      = trim($_POST['cnpj']      ?? '');
    $email     = trim($_POST['email']     ?? '');
   // $telefone  = trim($_POST['telefone']  ?? '');
    $cep       = trim($_POST['cep']       ?? '');
    $endereco  = trim($_POST['endereco']  ?? '');
    $cidade    = trim($_POST['cidade']    ?? '');
    $estado    = trim($_POST['estado']    ?? '');
    $area      = trim($_POST['area']      ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $senha     = $_POST['senha']          ?? '';
    $senha2    = $_POST['senha2']         ?? '';

    // validação campo a campo
    if (strlen($nome) < 3) {
        $r = ['ok' => false, 'campo' => 'nome', 'msg' => 'Nome deve ter pelo menos 3 caracteres.'];

    } elseif (!preg_match($REGEX_CNPJ, $cnpj)) {
        $r = ['ok' => false, 'campo' => 'cnpj', 'msg' => 'CNPJ inválido. Use o formato: 00.000.000/0000-00'];

    } elseif (!preg_match($REGEX_EMAIL, $email)) {
        $r = ['ok' => false, 'campo' => 'email', 'msg' => 'E-mail inválido.'];

    }
    //elseif (!preg_match($REGEX_TEL, $telefone)) {
    //    $r = ['ok' => false, 'campo' => 'telefone', 'msg' => 'Telefone inválido. Ex: (41) 99999-1234'];
    //} 
    elseif (!preg_match($REGEX_CEP, $cep)) {
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
        $r = ['ok' => false, 'campo' => 'senha', 'msg' => 'Senha deve ter pelo menos 6 caracteres.'];

    } elseif ($senha !== $senha2) {
        $r = ['ok' => false, 'campo' => 'senha2', 'msg' => 'As senhas não coincidem.'];

    } else {
        // verifica se o e-mail ou CNPJ já existe no banco
        $cnpj_limpo = preg_replace('/\D/', '', $cnpj);
        $stmt = $pdo->prepare("SELECT id_beneficiario FROM beneficiario WHERE email = ? OR cnpj = ?");
        $stmt->execute([$email, $cnpj_limpo]);

        if ($stmt->fetch()) {
            $r = ['ok' => false, 'msg' => 'Este e-mail ou CNPJ já está cadastrado.'];

        } else {
            // tudo certo — salva no banco
            $hash  = password_hash($senha, PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(16));
            $cep_limpo = preg_replace('/\D/', '', $cep);

            try {
                $stmt = $pdo->prepare("
                    INSERT INTO beneficiario
                        (nome_receptor, cnpj, email, localizacao, endereco, cidade,
                         sigla_estado, area_atuacao, descricao, senha_hash, token_confirmacao, status_elegibilidade)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendente')
                ");
                
                $stmt->execute([
                    $nome, $cnpj_limpo, $email, $cep_limpo,
                    $endereco, $cidade, $estado, $area, $descricao, $hash, $token
                ]);

                $id_ong = $pdo->lastInsertId();

                // Fazer login automático
                session_start();
                $_SESSION['ong'] = [
                    'id'           => $id_ong,
                    'nome'         => $nome,
                    'email'        => $email,
                    'cnpj'         => $cnpj_limpo,
                    'status'       => 'pendente' // ou aprovado se admin aprovar
                ];

                $r = [
                    'ok'  => true,
                    'msg' => "ONG <strong>$nome</strong> cadastrada com sucesso! Redirecionando..."
                ];
            } catch (PDOException $e) {
                // Intercepta e expõe o erro exato do banco de dados
                $r = [
                    'ok'  => false,
                    'msg' => "FALHA SQL: " . $e->getMessage()
                ];
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
                    <input type="password" id="senha" name="senha" placeholder="Mínimo 6 caracteres">
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
                <div class="erro-campo" id="erroSenha2"></div>
            </div>
        </div>

        <button type="submit" id="btnCadastrar">Cadastrar ONG</button>

    </form>

    <div class="rodape">
        Já tem cadastro? <a href="login_ong.php">Entrar aqui</a>
    </div>

</div>

<script>
    //  regex do ──
    var REGEX_EMAIL = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/;
    var REGEX_SENHA = /^.{6,}$/;
    var REGEX_CNPJ  = /^\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}$/;
//  var REGEX_TEL   = /^\(?\d{2}\)?\s?\d{4,5}-?\d{4}$/;
    var REGEX_CEP   = /^\d{5}-?\d{3}$/;

    // ── máscara automática do CNPJ ──
    document.getElementById('cnpj').addEventListener('input', function() {
        var v = this.value.replace(/\D/g, '').substring(0, 14);
        v = v.replace(/^(\d{2})(\d)/, '$1.$2');
        v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
        v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
        v = v.replace(/(\d{4})(\d)/, '$1-$2');
        this.value = v;
    });

    // ── máscara automática do CEP ──
    document.getElementById('cep').addEventListener('input', function() {
        var v = this.value.replace(/\D/g, '').substring(0, 8);
        v = v.replace(/(\d{5})(\d)/, '$1-$2');
        this.value = v;
    });


    // ── mostrar/ocultar senhas ──
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

    // ── funções de erro por campo ──
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
//  var campos = ['nome','cnpj','email','telefone','cep','endereco','cidade','senha','senha2'];
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

    // ── envio do formulário ──
    document.getElementById('formOng').addEventListener('submit', async function(e) {
        e.preventDefault();

        var tudo_ok = true;

        // valida cada campo
        if (document.getElementById('nome').value.trim().length < 3) {
            mostrarErro('nome', 'erroNome', 'Nome deve ter pelo menos 3 caracteres.');
            tudo_ok = false;
        }

        if (!REGEX_CNPJ.test(document.getElementById('cnpj').value.trim())) {
            mostrarErro('cnpj', 'erroCnpj', 'CNPJ inválido. Use: 00.000.000/0000-00');
            tudo_ok = false;
        }

        if (!REGEX_EMAIL.test(document.getElementById('email').value.trim())) {
            mostrarErro('email', 'erroEmail', 'E-mail inválido.');
            tudo_ok = false;
        }

        //if (!REGEX_TEL.test(document.getElementById('telefone').value.trim())) {
        //  mostrarErro('telefone', 'erroTelefone', 'Telefone inválido. Ex: (41) 99999-1234');
        //  tudo_ok = false;
        //}

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
            mostrarErro('senha', 'erroSenha', 'Senha deve ter pelo menos 6 caracteres.');
            tudo_ok = false;
        }

        if (document.getElementById('senha').value !== document.getElementById('senha2').value) {
            mostrarErro('senha2', 'erroSenha2', 'As senhas não coincidem.');
            tudo_ok = false;
        }

        // se algum campo tiver erro, para aqui
        if (!tudo_ok) {
            var msg = document.getElementById('mensagem');
            msg.textContent = 'Corrija os campos marcados em vermelho.';
            msg.className   = 'mensagem erro';
            return;
        }

        // limpa mensagem anterior
        document.getElementById('mensagem').className = 'mensagem';

        var btn = document.getElementById('btnCadastrar');
        btn.disabled    = true;
        btn.textContent = 'Aguarde...';

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
                this.reset();
                setTimeout(() => {
                    window.location.href = 'home_ong.php';
                }, 2000); // Redirecionar após 2 segundos para mostrar a mensagem
            }

        } catch (erro) {
            var msg = document.getElementById('mensagem');
            msg.textContent = 'Erro de conexão. Tente novamente.';
            msg.className   = 'mensagem erro';
        } finally {
            btn.disabled    = false;
            btn.textContent = 'Cadastrar ONG';
        }
    });
</script>

</body>
</html>
