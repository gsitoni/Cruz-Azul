<?php
ob_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

require __DIR__ . '/../../../vendor/autoload.php';
require __DIR__ . '/../../api/database.php';
require __DIR__ . '/../../api/mailer.php';
require __DIR__ . '/../../api/valida_senha.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    unset($_SESSION['usuario'], $_SESSION['2fa_ok'], $_SESSION['2fa_pendente'], $_SESSION['2fa_secret_temp'], $_SESSION['csrf_token']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim(strip_tags($_POST['nome'] ?? ''));
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $senha = trim($_POST['senha'] ?? '');
    $lgpd = $_POST['lgpd'] ?? '';

    $resultadoSenha = validarSenhaForte($senha);

    if ($nome === '' || $email === '' || $senha === '') {
        $resposta = ['ok' => false, 'msg' => 'Preencha todos os campos.'];
    } elseif ($lgpd !== 'true') {
        $resposta = ['ok' => false, 'msg' => 'Voce deve aceitar os termos da LGPD.'];
    } elseif (!preg_match('/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/', $email)) {
        $resposta = ['ok' => false, 'msg' => 'E-mail invalido.'];
    } elseif ($resultadoSenha !== true) {
        $resposta = ['ok' => false, 'msg' => $resultadoSenha];
    } else {
        $colunaPerfil = obterColunaPerfilUsuario($pdo);
        $valorAdmin = obterValorPerfilAdmin($pdo);
        $token = bin2hex(random_bytes(32));
        $hash = password_hash($senha, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            sprintf(
                'SELECT id_usuario, nome, email, status_cadastro, token_confirmacao, %s FROM usuario WHERE email = ? LIMIT 1',
                obterSelecaoPerfilUsuario($pdo, '')
            )
        );
        $stmt->execute([$email]);
        $usuarioExistente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuarioExistente) {
            if (($usuarioExistente['tipo'] ?? '') !== $valorAdmin) {
                $stmt = $pdo->prepare("UPDATE usuario SET {$colunaPerfil} = ? WHERE id_usuario = ?");
                $stmt->execute([$valorAdmin, $usuarioExistente['id_usuario']]);
            }

            $statusCadastro = (string) ($usuarioExistente['status_cadastro'] ?? '');
            $tokenConfirmacao = (string) ($usuarioExistente['token_confirmacao'] ?? '');
            $nomeDestino = $nome !== '' ? $nome : (string) ($usuarioExistente['nome'] ?? 'Administrador');

            if ($statusCadastro !== 'confirmado') {
                if ($tokenConfirmacao === '') {
                    $tokenConfirmacao = $token;
                    $stmt = $pdo->prepare('UPDATE usuario SET token_confirmacao = ? WHERE id_usuario = ?');
                    $stmt->execute([$tokenConfirmacao, $usuarioExistente['id_usuario']]);
                }

                if (enviarEmailConfirmacao($email, $nomeDestino, $tokenConfirmacao, 'admin')) {
                    $resposta = [
                        'ok' => true,
                        'msg' => "Conta administrativa atualizada. Enviamos um e-mail de confirmacao para <strong>{$email}</strong>."
                    ];
                } else {
                    $resposta = ['ok' => false, 'msg' => 'Conta atualizada, mas houve falha ao enviar o e-mail de confirmacao.'];
                }
            } else {
                $resposta = [
                    'ok' => true,
                    'msg' => 'Esta conta ja esta confirmada e agora tem acesso como administrador.'
                ];
            }
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO usuario (nome, email, senha_hash, token_confirmacao, {$colunaPerfil})
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nome, $email, $hash, $token, $valorAdmin]);

            if (enviarEmailConfirmacao($email, $nome, $token, 'admin')) {
                $resposta = [
                    'ok' => true,
                    'msg' => "Cadastro de administrador realizado! Verifique seu e-mail <strong>{$email}</strong> para confirmar a conta."
                ];
            } else {
                $resposta = ['ok' => false, 'msg' => 'Cadastro salvo, mas falha ao enviar e-mail.'];
            }
        }
    }

    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($resposta);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Administrador</title>
    <link rel="stylesheet" href="../../../public/assets/css/cadastro.css">
</head>
<body>
    <div class="container">
        <h2>Cadastro de Administrador</h2>

        <form id="formCadastro">
            <label>Nome</label>
            <input type="text" id="nome" name="nome" required>

            <label>E-mail</label>
            <input type="email" id="email" name="email" required>

            <label>Chat_id</label>
            <input type="number" id="chat_id" name="chat_id" required>


            <div class="msg" id="mensagem"></div>

            <button type="submit" id="btnCadastrar">Cadastrar Administrador</button>
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
            const lgpdChecked = document.getElementById('lgpd').checked;

            if (senha !== confirmarSenha) {
                mostrarMsg('As senhas nao coincidem.', 'erro');
                return;
            }

            if (!lgpdChecked) {
                mostrarMsg('Voce precisa aceitar a LGPD.', 'erro');
                return;
            }

            btnCad.disabled = true;
            btnCad.textContent = 'Aguarde...';

            const dados = new FormData();
            dados.append('nome', document.getElementById('nome').value.trim());
            dados.append('email', document.getElementById('email').value.trim());
            dados.append('senha', senha);
            dados.append('lgpd', lgpdChecked);

            try {
                const res = await fetch('cadastro_admin.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: dados
                });

                const json = await res.json();

                if (json.ok) {
                    window.location.href = 'cadastro_concluido.php?email=' + encodeURIComponent(document.getElementById('email').value.trim()) + '&tipo=admin';
                } else {
                    mostrarMsg(json.msg, 'erro');
                }
            } catch (err) {
                mostrarMsg('Erro de conexao. Tente novamente.', 'erro');
            } finally {
                btnCad.disabled = false;
                btnCad.textContent = 'Cadastrar Administrador';
            }
        });

        function mostrarMsg(texto, tipo) {
            msgDiv.innerHTML = texto;
            msgDiv.className = 'msg ' + tipo;
        }
    </script>
</body>
</html>
