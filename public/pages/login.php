<?php
 
 //Necessário incluir banco de dados 
// require 'test_email_bismark/database.php'; 
 
session_start();
 
// Se já logado, redireciona
if (isset($_SESSION['usuario'])) {
    header('Location: home.php');
    exit;
}
 

define('REGEX_EMAIL', '/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/');
define('REGEX_SENHA', '/^.{6,}$/');
 

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
 
    // --- Validação com regex ---
    if (empty($email) || empty($senha)) {
        $resposta = ['ok' => false, 'msg' => 'Preencha todos os campos.'];
 
    } elseif (!preg_match(REGEX_EMAIL, $email)) {
        $resposta = ['ok' => false, 'msg' => 'Formato de e-mail inválido.'];
 
    } elseif (!preg_match(REGEX_SENHA, $senha)) {
        $resposta = ['ok' => false, 'msg' => 'Senha deve ter pelo menos 6 caracteres.'];
 
    } else {
        // Busca usuário no banco pelo e-mail (igual ao cadastro.php usa PDO)
        $stmt = $pdo->prepare("SELECT id, nome, email, senha, email_confirmado FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
 
        if (!$usuario) {
            // Usuário não encontrado — mensagem genérica por segurança
            $resposta = ['ok' => false, 'msg' => 'E-mail ou senha incorretos.'];
 
        } elseif (!password_verify($senha, $usuario['senha'])) {
            // Senha errada — mesma mensagem genérica
            $resposta = ['ok' => false, 'msg' => 'E-mail ou senha incorretos.'];
 
        } elseif (isset($usuario['email_confirmado']) && !$usuario['email_confirmado']) {
            // Conta não confirmada (coluna criada pelo cadastro.php via token)
            $resposta = ['ok' => false, 'msg' => 'Confirme seu e-mail antes de entrar. Verifique sua caixa de entrada.'];
 
        } else {
            // Login OK — salva sessão
            $_SESSION['usuario'] = [
                'id'    => $usuario['id'],
                'nome'  => $usuario['nome'],
                'email' => $usuario['email'],
            ];
            $resposta = [
                'ok'       => true,
                'msg'      => 'Login realizado! Redirecionando...',
                'redirect' => 'index.php',
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
    <style>
        * { box-sizing: border-box; }
 
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f8;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
 
        .container {
            background: #fff;
            padding: 30px 25px;
            border-radius: 10px;
            width: 360px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
 
        h2 { text-align: center; margin-bottom: 20px; }
 
        label {
            display: block;
            margin-top: 12px;
            font-weight: bold;
            font-size: 14px;
        }
 
        input {
            width: 100%;
            padding: 9px 10px;
            margin-top: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 14px;
            transition: border-color .2s;
        }
 
        input:focus { outline: none; border-color: #007BFF; }
 
        /* Borda vermelha quando regex falha */
        input.invalido { border-color: #c0392b; }
 
        /* Texto de erro abaixo do campo */
        .erro-campo {
            font-size: 12px;
            color: #c0392b;
            margin-top: 4px;
            display: none;
        }
        .erro-campo.visivel { display: block; }
 
        button {
            margin-top: 18px;
            width: 100%;
            padding: 11px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 15px;
            transition: background .2s;
        }
 
        button:hover:not(:disabled) { background-color: #0056b3; }
        button:disabled { opacity: .6; cursor: not-allowed; }
 
        /* Mostrar/ocultar senha */
        .senha-wrap { position: relative; }
        .btn-olho {
            position: absolute;
            right: 10px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            cursor: pointer; font-size: 16px;
            margin-top: 0; width: auto; padding: 0;
        }
        .senha-wrap input { padding-right: 36px; }
 
        /* Mensagem geral */
        .msg {
            margin-top: 14px;
            padding: 10px 12px;
            border-radius: 5px;
            font-size: 13px;
            display: none;
        }
        .msg.erro    { background: #fdecea; color: #c0392b; display: block; }
        .msg.sucesso { background: #eafaf1; color: #1e7e34; display: block; }
 
        /* Link cadastro */
        .link-cadastro {
            text-align: center;
            margin-top: 16px;
            font-size: 13px;
            color: #555;
        }
        .link-cadastro a { color: #007BFF; text-decoration: none; }
        .link-cadastro a:hover { text-decoration: underline; }
 
        /* Dica regex */
        .dica {
            font-size: 11px;
            color: #888;
            margin-top: 3px;
            font-family: monospace;
        }
    </style>
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
            <button type="button" class="btn-olho" id="btnOlho">👁️</button>
        </div>
        <div class="erro-campo" id="erroSenha">A senha deve ter pelo menos 6 caracteres.</div>
        <!-- <div class="dica">regex: /^.{6,}$/</div> -->
 
        <!-- Mensagem geral -->
        <div class="msg" id="mensagem"></div>
 
        <button type="submit" id="btnEntrar">Entrar</button>
    </form>
 
    <div class="link-cadastro">
        Não tem conta? <a href="cadastro.php">Cadastre-se aqui</a>
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
    btnOlho.textContent = tipo === 'password' ? '👁️' : '🙈';
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