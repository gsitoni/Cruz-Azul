<?php
ob_start();
// cadastro.php - Processa o formulário de cadastro

// Headers de segurança
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

require '../../vendor/autoload.php';
require '../../src/api/database.php';
require '../../src/api/mailer.php';
require '../../src/api/valida_senha.php';
require_once '../../config/recaptcha.php';

// Responde requisições AJAX em JSON
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim(strip_tags($_POST['nome'] ?? ''));
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $senha = trim($_POST['senha'] ?? '');
    $lgpd = $_POST['lgpd'] ?? ''; // Captura o aceite da LGPD
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
    $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone'] ?? '');
    $data_nascimento = $_POST['data_nascimento'] ?? '';
    $idade = 0;
    if (!empty($data_nascimento)) {
        $dataNascObj = new DateTime($data_nascimento);
        $hoje = new DateTime();
        $idade = $hoje->diff($dataNascObj)->y;
    }

    function validarCPF($cpf){
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        if (strlen($cpf) != 11) {
            return false;
        }
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        $soma = 0;
        for ($i = 0; $i < 9; $i++) {
            $soma += $cpf[$i] * (10 - $i);
        }
        $resto = $soma % 11;
        $digito1 = ($resto < 2) ? 0 : 11 - $resto;
        $soma = 0;
        for ($i = 0; $i < 10; $i++) {
            $soma += $cpf[$i] * (11 - $i);
        }
        $resto = $soma % 11;
        $digito2 = ($resto < 2) ? 0 : 11 - $resto;
        return ($cpf[9] == $digito1 && $cpf[10] == $digito2);
    }

    // --- Validações ---
    $resultadoSenha = validarSenhaForte($senha); // Chame a função aqui

    $cpfInvalido = !validarCPF($cpf);

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
            $hash = password_hash($senha, PASSWORD_DEFAULT);

            // Transação: garante que usuario E doador são criados juntos
            $pdo->beginTransaction();

            // 1. Insere usuario
            $stmtUsuario = $pdo->prepare("
                    INSERT INTO usuario (nome, email, senha_hash, token_confirmacao, status_cadastro, tipo)
                    VALUES (?, ?, ?, ?, 'pendente', 'doador')
                ");
            $stmtUsuario->execute([$nome, $email, $hash, $token]);

            $idUsuario = $pdo->lastInsertId();

            // 2. Insere doador vinculado ao usuario
            $stmtDoador = $pdo->prepare("
                    INSERT INTO doador (id_usuario, cpf, nome, telefone, data_nascimento)
                    VALUES (?, ?, ?, ?, ?)
                ");
            $stmtDoador->execute([$idUsuario, $cpf, $nome, $telefone, $data_nascimento]);

            $pdo->commit();

            if (enviarEmailConfirmacao($email, $nome, $token)) {
                $resposta = [
                    'ok' => true,
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

            <label>CPF(Apenas números)</label>
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

            <div class="g-recaptcha"
                data-sitekey="<?php echo $RECAPTCHA_SITE_KEY; ?>">
            </div>

            <div class="msg" id="mensagem"></div>

            <button type="submit" id="btnCadastrar">Cadastrar</button>
        </form>
    </div>

    <script>
        const form = document.getElementById('formCadastro');
        const msgDiv = document.getElementById('mensagem');
        const btnCad = document.getElementById('btnCadastrar');

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            msgDiv.className = 'msg';
            msgDiv.innerHTML = '';

            const senha = document.getElementById('senha').value;
            const confirmarSenha = document.getElementById('confirmarSenha').value;
            const lgpdChecked = document.getElementById('lgpd').checked; // Verifica o checkbox
            const dataNascimento = document.getElementById('data_nascimento').value;

            if (senha !== confirmarSenha) {
                mostrarMsg('As senhas não coincidem!', 'erro');
                return;
            }

            if (!lgpdChecked) { // Validação visual do aceite
                mostrarMsg('Você precisa aceitar a LGPD.', 'erro');
                return;
            }

            if (dataNascimento) {
                const hoje = new Date();
                const dataNasc = new Date(dataNascimento);
                let idade = hoje.getFullYear() - dataNasc.getFullYear();
                const m = hoje.getMonth() - dataNasc.getMonth();
                if (m < 0 || (m === 0 && hoje.getDate() < dataNasc.getDate())) {
                    idade--;
                }
                if (idade < 18) {
                    mostrarMsg('É necessário ter 18 anos ou mais para se cadastrar.', 'erro');
                    return;
                }
            }
            btnCad.disabled = true;
            btnCad.textContent = 'Aguarde...';

            const dados = new FormData();
            dados.append('nome', document.getElementById('nome').value.trim());
            dados.append('email', document.getElementById('email').value.trim());
            dados.append('cpf', document.getElementById('cpf').value.trim());
            dados.append('telefone', document.getElementById('telefone').value.trim());
            dados.append('senha', senha);
            dados.append('lgpd', lgpdChecked); // Envia o estado do checkbox
            dados.append('data_nascimento', dataNascimento);

            try {
                const res = await fetch('cadastro.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: dados
                });

                const json = await res.json();

                if (json.ok) {
                    // mostrarMsg(json.msg, 'sucesso');
                    // form.reset();
                    window.location.href = 'cadastro_concluido.php?email=' + encodeURIComponent(document.getElementById('email').value.trim()) + '&tipo=usuario';
                } else {
                    mostrarMsg(json.msg, 'erro');
                }
            } catch (err) {
                mostrarMsg('Erro de conexão. Tente novamente.', 'erro');
            } finally {
                btnCad.disabled = false;
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