<?php
// ============================================================
//  atualizar_usuario.php  –  src/api/atualizar_usuario.php
//  Segurança: CSRF, XSS, HTML-injection, SQL-injection (PDO),
//             session-fixation, type juggling, rate-limit básico
// ============================================================
session_start();
 
// --- Cabeçalhos de segurança (esta página nunca deve ser exibida diretamente) ---
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
 
require_once __DIR__ . '/database.php';
 
// ──────────────────────────────────────────────
// 1. Verificações de sessão e método HTTP
// ──────────────────────────────────────────────
if (
    !isset($_SESSION['usuario']) ||
    empty($_SESSION['usuario']['id_usuario']) ||
    $_SERVER['REQUEST_METHOD'] !== 'POST'
) {
    header("Location: ../../public/pages/login.php");
    exit();
}
 
$id_usuario = (int) $_SESSION['usuario']['id_usuario'];
 
// ──────────────────────────────────────────────
// 2. Verificação de CSRF
//    O token precisa existir na sessão E no POST,
//    e devem ser iguais (hash_equals evita timing-attack).
// ──────────────────────────────────────────────
if (
    empty($_SESSION['csrf_token']) ||
    empty($_POST['csrf_token'])    ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    http_response_code(403);
    die("Requisição inválida. Token de segurança incorreto.");
}
 
// Invalida o token após uso (token único por envio)
unset($_SESSION['csrf_token']);
 
// ──────────────────────────────────────────────
// 3. Rate-limiting simples via sessão
//    Máximo de 10 atualizações por janela de 60 s
// ──────────────────────────────────────────────
$agora = time();
if (!isset($_SESSION['rl_ts'])) {
    $_SESSION['rl_ts']    = $agora;
    $_SESSION['rl_count'] = 0;
}
if (($agora - $_SESSION['rl_ts']) > 60) {
    $_SESSION['rl_ts']    = $agora;
    $_SESSION['rl_count'] = 0;
}
$_SESSION['rl_count']++;
if ($_SESSION['rl_count'] > 10) {
    http_response_code(429);
    die("Muitas requisições. Aguarde um momento e tente novamente.");
}
 
// ──────────────────────────────────────────────
// 4. Helpers de validação / sanitização
// ──────────────────────────────────────────────
 
/**
 * Lê um campo POST como string, remove espaços nas bordas e
 * retorna null se o campo não existir ou ficar vazio após strip.
 */
function postStr(string $campo, int $maxLen = 255): ?string {
    if (!isset($_POST[$campo])) return null;
    $valor = mb_substr(trim($_POST[$campo]), 0, $maxLen, 'UTF-8');
    return ($valor === '') ? null : $valor;
}
 
/**
 * Valida nome: apenas letras (incluindo acentos), espaços,
 * apóstrofos e hifens.
 */
function validaNome(string $v): bool {
    return (bool) preg_match('/^[\p{L}\s\'\-]{2,300}$/u', $v);
}
 
 
/**
 * Valida localização / texto genérico: sem tags HTML,
 * sem caracteres de controle, tamanho limitado.
 */
function validaTextoGenerico(string $v, int $max): bool {
    // Rejeita qualquer vestígio de tag HTML
    if ($v !== strip_tags($v)) return false;
    return mb_strlen($v, 'UTF-8') <= $max;
}
 
// ──────────────────────────────────────────────
// 5. Execução dentro de transação
// ──────────────────────────────────────────────
try {
 
    // Confirma que o usuário existe e está ativo
    $stmtUser = $pdo->prepare(
        "SELECT email FROM usuario WHERE id_usuario = ? LIMIT 1"
    );
    $stmtUser->execute([$id_usuario]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
 
    if (!$user) {
        session_destroy();
        header("Location: ../../public/pages/login.php");
        exit();
    }
 
    $email_usuario = $user['email'];
 
    $pdo->beginTransaction();
 
    // ── Ramo DOADOR ──────────────────────────────
    if (isset($_POST['nome'])) {
 
        $nome       = postStr('nome', 200);
        $senha_atual   = postStr('senha_atual', 255);
        $nova_senha    = postStr('nova_senha', 255);
        $conf_senha    = postStr('confirmar_senha', 255);
 
        // Validação de nome
        if ($nome === null) {
            throw new InvalidArgumentException("O campo Nome é obrigatório.");
        }
        if (!validaNome($nome)) {
            throw new InvalidArgumentException("Nome inválido. Use somente letras e espaços.");
        }
 
        // Confirma que o doador pertence ao usuário logado
        $stmtCheck = $pdo->prepare(
            "SELECT id_doador FROM doador WHERE email = ? LIMIT 1"
        );
        $stmtCheck->execute([$email_usuario]);
        if (!$stmtCheck->fetch()) {
            throw new RuntimeException("Registro não encontrado para este usuário.");
        }
 
        // Atualiza nome
        $stmtUp = $pdo->prepare(
            "UPDATE doador SET nome = ? WHERE email = ?"
        );
        $stmtUp->execute([$nome, $email_usuario]);
 
        // Alteração de senha (opcional)
        if ($nova_senha !== null) {
            if ($senha_atual === null) {
                throw new InvalidArgumentException("Informe a senha atual para alterá-la.");
            }
            if (mb_strlen($nova_senha, 'UTF-8') < 6) {
                throw new InvalidArgumentException("A nova senha deve ter pelo menos 6 caracteres.");
            }
            if ($nova_senha !== $conf_senha) {
                throw new InvalidArgumentException("As senhas não coincidem.");
            }
 
            // Verifica senha atual
            $stmtSenha = $pdo->prepare(
                "SELECT senha_hash FROM usuario WHERE id_usuario = ? LIMIT 1"
            );
            $stmtSenha->execute([$id_usuario]);
            $row = $stmtSenha->fetch(PDO::FETCH_ASSOC);
 
            if (!$row || !password_verify($senha_atual, $row['senha_hash'])) {
                throw new InvalidArgumentException("Senha atual incorreta.");
            }
 
            $novoHash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $stmtUpSenha = $pdo->prepare(
                "UPDATE usuario SET senha_hash = ? WHERE id_usuario = ?"
            );
            $stmtUpSenha->execute([$novoHash, $id_usuario]);
        }
 
    // ── Ramo BENEFICIÁRIO ────────────────────────
    } elseif (isset($_POST['nome_receptor'])) {
 
        $nome_receptor = postStr('nome_receptor', 300);
        $localizacao   = postStr('localizacao', 50);
 
        if ($nome_receptor === null) {
            throw new InvalidArgumentException("O campo Nome da Instituição é obrigatório.");
        }
        if (!validaTextoGenerico($nome_receptor, 300)) {
            throw new InvalidArgumentException("Nome da instituição inválido.");
        }
        if ($localizacao !== null && !validaTextoGenerico($localizacao, 50)) {
            throw new InvalidArgumentException("Localização inválida.");
        }
 
        // Confirma que o beneficiário pertence ao usuário logado
        $stmtCheck = $pdo->prepare(
            "SELECT b.id_beneficiario
               FROM beneficiario b
               JOIN usuario u ON u.id_usuario = ?
              WHERE b.email = ?
              LIMIT 1"
        );
        $stmtCheck->execute([$id_usuario, $email_usuario]);
        $ben = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        if (!$ben) {
            throw new RuntimeException("Registro não encontrado para este usuário.");
        }
 
        $stmtUp = $pdo->prepare(
            "UPDATE beneficiario
                SET nome_receptor = ?,
                    localizacao   = ?
              WHERE id_beneficiario = ?"
        );
        $stmtUp->execute([
            $nome_receptor,
            $localizacao ?? '',
            (int) $ben['id_beneficiario'],
        ]);
 
        // Alteração de senha (opcional) — igual ao ramo doador
        $senha_atual = postStr('senha_atual', 255);
        $nova_senha  = postStr('nova_senha', 255);
        $conf_senha  = postStr('confirmar_senha', 255);
 
        if ($nova_senha !== null) {
            if ($senha_atual === null) {
                throw new InvalidArgumentException("Informe a senha atual para alterá-la.");
            }
            if (mb_strlen($nova_senha, 'UTF-8') < 6) {
                throw new InvalidArgumentException("A nova senha deve ter pelo menos 6 caracteres.");
            }
            if ($nova_senha !== $conf_senha) {
                throw new InvalidArgumentException("As senhas não coincidem.");
            }
 
            $stmtSenha = $pdo->prepare(
                "SELECT senha_hash FROM usuario WHERE id_usuario = ? LIMIT 1"
            );
            $stmtSenha->execute([$id_usuario]);
            $row = $stmtSenha->fetch(PDO::FETCH_ASSOC);
 
            if (!$row || !password_verify($senha_atual, $row['senha_hash'])) {
                throw new InvalidArgumentException("Senha atual incorreta.");
            }
 
            $novoHash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $stmtUpSenha = $pdo->prepare(
                "UPDATE usuario SET senha_hash = ? WHERE id_usuario = ?"
            );
            $stmtUpSenha->execute([$novoHash, $id_usuario]);
        }
 
    } else {
        // Nenhum campo reconhecido → possível adulteração do formulário
        throw new InvalidArgumentException("Dados do formulário inválidos.");
    }
 
    $pdo->commit();
 
    // Redireciona de volta ao perfil com mensagem de sucesso
    header("Location: ../../public/pages/perfil.php?status=atualizado");
    exit();
 
} catch (InvalidArgumentException $e) {
    // Erros de validação — mostra ao usuário de forma segura
    if ($pdo->inTransaction()) $pdo->rollBack();
    $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    header("Location: ../../public/pages/editar_perfil.php?erro=" . urlencode($msg));
    exit();
 
} catch (Exception $e) {
    // Erros internos — loga, nunca expõe ao usuário
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("atualizar_usuario.php Exception [{$id_usuario}]: " . $e->getMessage());
    header("Location: ../../public/pages/editar_perfil.php?erro=" . urlencode("Erro interno. Tente novamente."));
    exit();
}