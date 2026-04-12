<?php
 
 //Necessário incluir banco de dados 
 require '../../src/api/database.php'; 
 
// Headers de segurança
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

session_start();
 
// Se já logado, redireciona
if (isset($_SESSION['usuario'])) {
    header('Location: ./home.php');
    exit;
}
 

define('REGEX_EMAIL', '/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/');
define('REGEX_SENHA', '/^.{6,}$/');
 

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $senha = trim($_POST['senha'] ?? '');
 
    // --- Validação com regex ---
    if (empty($email) || empty($senha)) {
        $resposta = ['ok' => false, 'msg' => 'Preencha todos os campos.'];
 
    } elseif (!preg_match(REGEX_EMAIL, $email)) {
        $resposta = ['ok' => false, 'msg' => 'Formato de e-mail inválido.'];
 
    } elseif (!preg_match(REGEX_SENHA, $senha)) {
        $resposta = ['ok' => false, 'msg' => 'Senha deve ter pelo menos 6 caracteres.'];
 
    } else {
        // Busca usuário no banco pelo e-mail (igual ao cadastro.php usa PDO)
        $stmt = $pdo->prepare("SELECT id_usuario, email, senha_hash, status_cadastro FROM usuario WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
 
        if (!$usuario) {
            // Usuário não encontrado — mensagem genérica por segurança
            $resposta = ['ok' => false, 'msg' => 'E-mail ou senha incorretos.'];
 
        } elseif (!password_verify($senha, $usuario['senha_hash'])) {
            // Senha errada — mesma mensagem genérica
            $resposta = ['ok' => false, 'msg' => 'E-mail ou senha incorretos.'];
 
        } elseif (isset($usuario['status_cadastro']) && !$usuario['status_cadastro']) {
            // Conta não confirmada (coluna criada pelo cadastro.php via token)
            $resposta = ['ok' => false, 'msg' => 'Confirme seu e-mail antes de entrar. Verifique sua caixa de entrada.'];
 
        } else {
            // Login OK — salva sessão
            $_SESSION['usuario'] = [
                'id_usuario'    => $usuario['id_usuario'],
                'email' => $usuario['email'],
            ];
            $resposta = [
                'ok'       => true,
                'msg'      => 'Login realizado! Redirecionando...',
                'redirect' => './home.php',
            ];
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
    <title>Login — Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
 
<div class="container">
    <h2>Entrar</h2>
 
    <form id="formLogin">
 
        <!-- E-mail -->
        <label for="email">E-mail</label>
        <input type="text" id="email" name="email" placeholder="nome@dominio.com" required>
        <div class="erro-campo" id="erroEmail">Formato inválido. Ex: nome@site.com</div>
        <!-- <div class="dica">regex: /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/</div> -->
 
        <!-- Senha -->
        <label for="senha">Senha</label>
        <div class="senha-wrap">
            <input type="password" id="senha" name="senha" placeholder="Mínimo 6 caracteres" required>
            <button type="button" class="btn-olho" id="btnOlho">Mostrar</button>
        </div>
        <div class="erro-campo" id="erroSenha">A senha deve ter pelo menos 6 caracteres.</div>
        <!-- <div class="dica">regex: /^.{6,}$/</div> -->
 
        <!-- Mensagem geral -->
        <div class="msg" id="mensagem"></div>
 
        <button type="submit" id="btnEntrar">Entrar</button>
    </form>
 
    <div class="link-cadastro">
        Não tem conta? <a href="./cadastro.php">Cadastre-se aqui</a>
    </div>
</div>
 
<script>

//  validação em tempo real
const REGEX_EMAIL = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/;
const REGEX_SENHA = /^.{6,}$/;
 
const campoEmail = document.getElementById('email');
const campoSenha = document.getElementById('senha');
const erroEmail  = document.getElementById('erroEmail');
const erroSenha  = document.getElementById('erroSenha');
const btnEntrar  = document.getElementById('btnEntrar');
const btnOlho    = document.getElementById('btnOlho');
const msgDiv     = document.getElementById('mensagem');
 
// Validação ao sair do campo
campoEmail.addEventListener('blur', () => validar(campoEmail, REGEX_EMAIL, erroEmail));
campoSenha.addEventListener('blur', () => validar(campoSenha, REGEX_SENHA, erroSenha));
 
// Remove erro enquanto digita
campoEmail.addEventListener('input', () => limpar(campoEmail, erroEmail));
campoSenha.addEventListener('input', () => limpar(campoSenha, erroSenha));
 
function validar(input, regex, erroDiv) {
    if (!regex.test(input.value.trim())) {
        input.classList.add('invalido');
        erroDiv.classList.add('visivel');
        return false;
    }
    limpar(input, erroDiv);
    return true;
}
 
function limpar(input, erroDiv) {
    input.classList.remove('invalido');
    erroDiv.classList.remove('visivel');
}
 
// Mostrar / ocultar senha
btnOlho.addEventListener('click', () => {
    const tipo = campoSenha.type === 'password' ? 'text' : 'password';
    campoSenha.type = tipo;
    btnOlho.textContent = tipo === 'password' ? 'Mostrar' : 'Ocultar';
});
 

document.getElementById('formLogin').addEventListener('submit', async function(e) {
    e.preventDefault();
 
    // Valida campos antes de enviar
    const emailOk = validar(campoEmail, REGEX_EMAIL, erroEmail);
    const senhaOk = validar(campoSenha, REGEX_SENHA, erroSenha);
    if (!emailOk || !senhaOk) return;
 
    // Limpa mensagem anterior
    msgDiv.className = 'msg';
    msgDiv.innerHTML = '';
 
    btnEntrar.disabled    = true;
    btnEntrar.textContent = 'Aguarde...';
 
    // Monta FormData 
    const dados = new FormData();
    dados.append('email', campoEmail.value.trim());
    dados.append('senha', campoSenha.value);
 
    try {
        const res  = await fetch('login.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }, 
            body: dados
        });
 
        const json = await res.json();
 
        if (json.ok) {
            mostrarMsg(json.msg, 'sucesso');
            // Redireciona após 1 segundo
            setTimeout(() => { window.location.href = json.redirect || 'index.php'; }, 1000);
        } else {
            mostrarMsg(json.msg, 'erro');
        }
 
    } catch (err) {
        mostrarMsg('Erro de conexão. Tente novamente.', 'erro');
    } finally {
        btnEntrar.disabled    = false;
        btnEntrar.textContent = 'Entrar';
    }
});
 
function mostrarMsg(texto, tipo) {
    msgDiv.innerHTML = texto;
    msgDiv.className = 'msg ' + tipo;
}
</script>
 
</body>
</html>