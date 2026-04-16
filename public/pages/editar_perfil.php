<?php
// ============================================================
//  editar_perfil.php  –  public/pages/editar_perfil.php
// ============================================================
session_start();

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'none'");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

require_once __DIR__ . '/../../src/api/database.php';

// --- Autenticação ---
if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['id_usuario'])) {
    header("Location: login.php");
    exit();
}

$id_usuario = (int) $_SESSION['usuario']['id_usuario'];

// --- CSRF ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// --- Busca dados atuais ---
try {
    $stmtUser = $pdo->prepare(
        "SELECT email FROM usuario WHERE id_usuario = ? LIMIT 1"
    );
    $stmtUser->execute([$id_usuario]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header("Location: login.php");
        exit();
    }

    // Tenta doador
    $stmtDoador = $pdo->prepare(
        "SELECT id_doador, nome, telefone FROM doador WHERE id_usuario = ? LIMIT 1"
    );
    $stmtDoador->execute([$id_usuario]);
    $perfil = $stmtDoador->fetch(PDO::FETCH_ASSOC);
    $tipo   = 'doador';

    // Fallback: ONG
    if (!$perfil) {
        $stmtONG = $pdo->prepare(
            "SELECT id_ong, nome, localizacao FROM ong WHERE id_usuario = ? LIMIT 1"
        );
        $stmtONG->execute([$id_usuario]);
        $perfil = $stmtONG->fetch(PDO::FETCH_ASSOC);
        $tipo   = 'ong';
    }

    if (!$perfil) {
        die("Perfil não encontrado. Entre em contato com o suporte.");
    }

} catch (PDOException $ex) {
    error_log("editar_perfil.php – busca: " . $ex->getMessage());
    die("Erro interno ao carregar o perfil. Tente novamente.");
}

// ============================================================
//  Processamento do POST
// ============================================================
$erros  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Valida CSRF ---
    if (
        empty($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        die("Requisição inválida (CSRF).");
    }

    // --- Coleta campos ---
    $novo_nome      = trim($_POST['nome']           ?? '');
    $novo_email     = trim($_POST['email']          ?? '');
    $nova_senha     = $_POST['senha']               ?? '';
    $confirma_senha = $_POST['confirma_senha']      ?? '';
    $novo_telefone  = trim($_POST['telefone']       ?? ''); // só doador

    // --- Validações comuns ---
    if ($novo_nome === '') {
        $erros[] = "O nome não pode estar vazio.";
    } elseif (mb_strlen($novo_nome) > 200) {
        $erros[] = "Nome muito longo (máximo 200 caracteres).";
    }

    if ($novo_email === '') {
        $erros[] = "O e-mail não pode estar vazio.";
    } elseif (!filter_var($novo_email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = "E-mail inválido.";
    } elseif (mb_strlen($novo_email) > 254) {
        $erros[] = "E-mail muito longo.";
    }

    // Senha opcional
    $alterar_senha = ($nova_senha !== '');
    if ($alterar_senha) {
        if (mb_strlen($nova_senha) < 8) {
            $erros[] = "A senha deve ter pelo menos 8 caracteres.";
        }
        if ($nova_senha !== $confirma_senha) {
            $erros[] = "A confirmação de senha não confere.";
        }
    }

    // Telefone só para doador
    if ($tipo === 'doador') {
        $telefone_digits = preg_replace('/\D/', '', $novo_telefone);
        if ($telefone_digits === '') {
            $erros[] = "O telefone não pode estar vazio.";
        } elseif (!preg_match('/^\d{10,11}$/', $telefone_digits)) {
            $erros[] = "Telefone inválido. Use DDD + número (10 ou 11 dígitos).";
        } else {
            $novo_telefone = $telefone_digits;
        }
    }

    // Verifica e-mail duplicado
    if (empty($erros) && $novo_email !== $user['email']) {
        try {
            $stmtCheck = $pdo->prepare(
                "SELECT id_usuario FROM usuario
                  WHERE email = ? AND id_usuario <> ? LIMIT 1"
            );
            $stmtCheck->execute([$novo_email, $id_usuario]);
            if ($stmtCheck->fetch()) {
                $erros[] = "Este e-mail já está em uso por outra conta.";
            }
        } catch (PDOException $ex) {
            error_log("editar_perfil.php – check email: " . $ex->getMessage());
            $erros[] = "Erro ao verificar e-mail. Tente novamente.";
        }
    }

    // --- Persiste ---
    if (empty($erros)) {
        try {
            $pdo->beginTransaction();

            // Atualiza usuario (email + senha opcional)
            if ($alterar_senha) {
                $hash = password_hash($nova_senha, PASSWORD_BCRYPT);
                $pdo->prepare(
                    "UPDATE usuario SET email = ?, senha_hash = ? WHERE id_usuario = ?"
                )->execute([$novo_email, $hash, $id_usuario]);
            } else {
                $pdo->prepare(
                    "UPDATE usuario SET email = ? WHERE id_usuario = ?"
                )->execute([$novo_email, $id_usuario]);
            }

            // Atualiza nome + telefone no doador
            if ($tipo === 'doador') {
                $pdo->prepare(
                    "UPDATE doador SET nome = ?, telefone = ? WHERE id_usuario = ?"
                )->execute([$novo_nome, $novo_telefone, $id_usuario]);
            }

            // Atualiza nome na ONG
            if ($tipo === 'ong') {
                $pdo->prepare(
                    "UPDATE ong SET nome = ? WHERE id_usuario = ?"
                )->execute([$novo_nome, $id_usuario]);
            }

            $pdo->commit();

            session_regenerate_id(true);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            header("Location: perfil.php?status=atualizado");
            exit();

        } catch (PDOException $ex) {
            $pdo->rollBack();
            error_log("editar_perfil.php – update: " . $ex->getMessage());
            $erros[] = "Erro ao salvar os dados. Tente novamente.";
        }
    }

    // Re-exibe formulário com valores digitados
    $user['email']      = $novo_email;
    $perfil['nome']     = $novo_nome;
    if ($tipo === 'doador') {
        $perfil['telefone'] = $novo_telefone;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil – Cruz Azul</title>
    <style>
        body { font-family: sans-serif; background: #f4f6f9; }
        .perfil-container {
            max-width: 560px; margin: 50px auto; padding: 28px;
            border: 1px solid #ddd; border-radius: 8px; background: #fff;
        }
        h1 { margin-top: 0; }
        .campo { margin-bottom: 18px; }
        label { display: block; font-weight: bold; color: #444; margin-bottom: 4px; }
        input[type="email"],
        input[type="text"],
        input[type="password"],
        input[type="tel"] {
            width: 100%; padding: 9px 11px; box-sizing: border-box;
            border: 1px solid #bbb; border-radius: 5px; font-size: 1rem;
        }
        input:focus { outline: 2px solid #007bff; border-color: #007bff; }
        .hint { font-size: .85rem; color: #666; margin-top: 3px; }
        .alerta-erro {
            background: #f8d7da; color: #721c24; padding: 10px 14px;
            border-radius: 5px; margin-bottom: 20px;
        }
        .alerta-erro ul { margin: 6px 0 0 18px; padding: 0; }
        .separador { border: none; border-top: 1px solid #e0e0e0; margin: 24px 0; }
        .acoes { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 24px; }
        .btn-primary {
            background: #007bff; color: #fff; padding: 10px 22px;
            border: none; border-radius: 5px; font-size: 1rem; cursor: pointer;
        }
        .btn-primary:hover { background: #0056b3; }
        .btn-secondary {
            color: #555; padding: 10px 14px; text-decoration: none;
            display: inline-block; font-size: 1rem;
        }
    </style>
</head>
<body>
<div class="perfil-container">

    <h1>Editar Perfil</h1>

    <?php if (!empty($erros)): ?>
        <div class="alerta-erro" role="alert">
            <strong>Corrija os erros abaixo:</strong>
            <ul>
                <?php foreach ($erros as $erro): ?>
                    <li><?= e($erro) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="editar_perfil.php" novalidate>
        <input type="hidden" name="csrf_token"
               value="<?= e($_SESSION['csrf_token']) ?>">

        <!-- Nome -->
        <div class="campo">
            <label for="nome">
                <?= $tipo === 'doador' ? 'Nome' : 'Nome da Instituição' ?>
            </label>
            <input type="text" id="nome" name="nome" required
                   maxlength="200"
                   value="<?= e($perfil['nome']) ?>">
        </div>

        <!-- E-mail -->
        <div class="campo">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" required
                   maxlength="254"
                   value="<?= e($user['email']) ?>">
        </div>

        <?php if ($tipo === 'doador'): ?>
        <!-- Telefone -->
        <div class="campo">
            <label for="telefone">Telefone</label>
            <input type="tel" id="telefone" name="telefone"
                   maxlength="20"
                   value="<?= e($perfil['telefone']) ?>">
            <p class="hint">Somente números com DDD (ex.: 11987654321).</p>
        </div>
        <?php endif; ?>

        <hr class="separador">

        <!-- Senha -->
        <div class="campo">
            <label for="senha">Nova senha</label>
            <input type="password" id="senha" name="senha"
                   autocomplete="new-password" maxlength="128">
            <p class="hint">Deixe em branco para manter a senha atual. Mínimo 8 caracteres.</p>
        </div>
        <div class="campo">
            <label for="confirma_senha">Confirmar nova senha</label>
            <input type="password" id="confirma_senha" name="confirma_senha"
                   autocomplete="new-password" maxlength="128">
        </div>

        <div class="acoes">
            <button type="submit" class="btn-primary">Salvar alterações</button>
            <a href="perfil.php" class="btn-secondary">Cancelar</a>
        </div>
    </form>

</div>
</body>
</html>