<?php
require __DIR__ . '/../../api/database.php';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Strict',
]);

session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!empty($_GET['reset'])) {
    unset(
        $_SESSION['admin_recovery_user_id'],
        $_SESSION['admin_recovery_question'],
        $_SESSION['admin_recovery_email'],
        $_SESSION['admin_recovery_verified']
    );
}

$mensagem = '';
$tipoMensagem = '';

$perguntasValidas = [
    'nome_primeiro_pet' => 'Qual o nome do seu primeiro pet?',
    'nome_escola_infancia' => 'Qual o nome da sua escola na infancia?',
    'bairro_infancia' => 'Qual era o nome do bairro onde voce cresceu?',
    'modelo_primeiro_carro' => 'Qual o modelo do seu primeiro carro?',
];

$step = empty($_SESSION['admin_recovery_user_id']) ? 'identificar' : 'pergunta';

if (!empty($_SESSION['admin_recovery_verified'])) {
    header('Location: redefinir_senha.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        empty($_SESSION['csrf_token']) ||
        empty($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        die('Requisicao invalida.');
    }

    $acao = trim((string) ($_POST['acao'] ?? ''));

    if ($acao === 'identificar') {
        $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_SANITIZE_EMAIL);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mensagem = 'Informe um e-mail valido.';
            $tipoMensagem = 'erro';
            $step = 'identificar';
        } else {
            $stmt = $pdo->prepare(
                "SELECT id_usuario, email, tipo, status_cadastro, pergunta_seguranca, resposta_seguranca_hash
                 FROM usuario
                 WHERE email = ?
                 LIMIT 1"
            );
            $stmt->execute([$email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            $ehAdmin =
                $usuario &&
                stripos((string) ($usuario['tipo'] ?? ''), 'admin') !== false;

            if (
                !$ehAdmin ||
                (string) ($usuario['status_cadastro'] ?? '') !== 'confirmado' ||
                empty($usuario['pergunta_seguranca']) ||
                empty($usuario['resposta_seguranca_hash'])
            ) {
                $mensagem = 'Nao foi possivel iniciar a recuperacao para esta conta.';
                $tipoMensagem = 'erro';
                $step = 'identificar';
            } else {
                $_SESSION['admin_recovery_user_id'] = (int) $usuario['id_usuario'];
                $_SESSION['admin_recovery_question'] = (string) $usuario['pergunta_seguranca'];
                $_SESSION['admin_recovery_email'] = (string) $usuario['email'];
                $_SESSION['admin_recovery_verified'] = false;
                $step = 'pergunta';
            }
        }
    }

    if ($acao === 'validar_resposta') {
        $usuarioId = (int) ($_SESSION['admin_recovery_user_id'] ?? 0);
        $resposta = mb_strtolower(trim((string) ($_POST['resposta_seguranca'] ?? '')));

        if ($usuarioId <= 0) {
            $mensagem = 'Sessao de recuperacao expirada. Inicie novamente.';
            $tipoMensagem = 'erro';
            $step = 'identificar';
            unset(
                $_SESSION['admin_recovery_user_id'],
                $_SESSION['admin_recovery_question'],
                $_SESSION['admin_recovery_email'],
                $_SESSION['admin_recovery_verified']
            );
        } else {
            $stmt = $pdo->prepare(
                "SELECT id_usuario, tipo, pergunta_seguranca, resposta_seguranca_hash, tentativas_recuperacao, bloqueado_ate
                 FROM usuario
                 WHERE id_usuario = ?
                 LIMIT 1"
            );
            $stmt->execute([$usuarioId]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            $ehAdmin =
                $usuario &&
                stripos((string) ($usuario['tipo'] ?? ''), 'admin') !== false;

            if (!$ehAdmin || empty($usuario['resposta_seguranca_hash'])) {
                $mensagem = 'Nao foi possivel validar esta conta.';
                $tipoMensagem = 'erro';
                $step = 'identificar';
                unset(
                    $_SESSION['admin_recovery_user_id'],
                    $_SESSION['admin_recovery_question'],
                    $_SESSION['admin_recovery_email'],
                    $_SESSION['admin_recovery_verified']
                );
            } else {
                $agoraTs = time();
                $bloqueadoAte = !empty($usuario['bloqueado_ate']) ? strtotime((string) $usuario['bloqueado_ate']) : false;

                if ($bloqueadoAte && $bloqueadoAte > $agoraTs) {
                    $faltamSeg = $bloqueadoAte - $agoraTs;
                    $faltamMin = (int) ceil($faltamSeg / 60);
                    $mensagem = "Conta temporariamente bloqueada. Tente novamente em {$faltamMin} minuto(s).";
                    $tipoMensagem = 'erro';
                    $step = 'pergunta';
                } elseif ($resposta === '') {
                    $mensagem = 'Informe a resposta da pergunta de seguranca.';
                    $tipoMensagem = 'erro';
                    $step = 'pergunta';
                } elseif (password_verify($resposta, (string) $usuario['resposta_seguranca_hash'])) {
                    $stmt = $pdo->prepare('UPDATE usuario SET tentativas_recuperacao = 0, bloqueado_ate = NULL WHERE id_usuario = ?');
                    $stmt->execute([$usuarioId]);

                    $_SESSION['admin_recovery_verified'] = true;
                    header('Location: redefinir_senha.php');
                    exit();
                } else {
                    $tentativas = (int) ($usuario['tentativas_recuperacao'] ?? 0) + 1;
                    $minutosBloqueio = 0;

                    if ($tentativas >= 3) {
                        $expo = min($tentativas - 3, 5);
                        $minutosBloqueio = (int) (2 ** $expo);
                    }

                    $bloqueadoAteDb = null;
                    if ($minutosBloqueio > 0) {
                        $bloqueadoAteDb = date('Y-m-d H:i:s', $agoraTs + ($minutosBloqueio * 60));
                    }

                    $stmt = $pdo->prepare('UPDATE usuario SET tentativas_recuperacao = ?, bloqueado_ate = ? WHERE id_usuario = ?');
                    $stmt->execute([$tentativas, $bloqueadoAteDb, $usuarioId]);

                    if ($minutosBloqueio > 0) {
                        $mensagem = "Resposta incorreta. Conta bloqueada por {$minutosBloqueio} minuto(s).";
                    } else {
                        $restantesSemBloqueio = max(0, 3 - $tentativas);
                        $mensagem = "Resposta incorreta. Restam {$restantesSemBloqueio} tentativa(s) antes do bloqueio temporario.";
                    }
                    $tipoMensagem = 'erro';
                    $step = 'pergunta';
                }
            }
        }
    }
}

$chavePergunta = (string) ($_SESSION['admin_recovery_question'] ?? '');
$textoPergunta = $perguntasValidas[$chavePergunta] ?? 'Pergunta de seguranca';

if (strpos($chavePergunta, 'custom:') === 0) {
    $textoPergunta = trim(substr($chavePergunta, 7));
}

if ($textoPergunta === '') {
    $textoPergunta = 'Pergunta de seguranca';
}

$emailSessao = (string) ($_SESSION['admin_recovery_email'] ?? '');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar senha admin - Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
    <main class="login-shell">
        <section class="login-panel login-panel--brand">
            <span class="eyebrow">Recuperacao de acesso</span>
            <h1>Administrador</h1>
            <p>
                Este fluxo usa pergunta de seguranca e bloqueio progressivo em caso de erro para proteger contas administrativas.
            </p>
            <ul class="feature-list">
                <li>Apos erros consecutivos, o tempo de bloqueio aumenta automaticamente</li>
                <li>A redefinicao so e liberada apos validacao da resposta</li>
                <li>O login principal por Telegram continua sendo o fluxo recomendado</li>
            </ul>
        </section>

        <section class="login-panel login-panel--form">
            <div class="form-header">
                <h2>Recuperar senha</h2>
                <?php if ($step === 'identificar'): ?>
                    <p>Informe seu e-mail administrativo para continuar.</p>
                <?php else: ?>
                    <p>Conta: <?= htmlspecialchars($emailSessao, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            </div>

            <div class="msg <?= $tipoMensagem === 'erro' ? 'erro' : ($tipoMensagem === 'sucesso' ? 'sucesso' : '') ?>">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
            </div>

            <?php if ($step === 'identificar'): ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="acao" value="identificar">

                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" required maxlength="254" autocomplete="email" placeholder="admin@dominio.com">

                    <button type="submit" id="btnEntrar">Continuar</button>
                </form>
            <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="acao" value="validar_resposta">

                    <label for="resposta_seguranca"><?= htmlspecialchars($textoPergunta, ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="text" id="resposta_seguranca" name="resposta_seguranca" required maxlength="120" autocomplete="off" placeholder="Digite sua resposta">

                    <button type="submit" id="btnEntrar">Validar resposta</button>
                </form>
            <?php endif; ?>

            <div class="support-links">
                <a href="./index.php">Voltar ao login</a>
                <?php if ($step === 'pergunta'): ?>
                    <a href="./recuperar_senha.php?reset=1">Trocar conta</a>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>
