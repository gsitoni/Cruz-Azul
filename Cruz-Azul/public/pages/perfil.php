<?php
// ============================================================
//  perfil.php  –  public/pages/perfil.php
//  Segurança: XSS, Clickjacking, session-fixation, CSRF read
// ============================================================
session_start();
 
// --- Cabeçalhos de segurança ---
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
 
// --- Gera token CSRF para o link de edição (GET → formulário POST) ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
 
// --- Busca dados ---
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
        "SELECT nome FROM doador WHERE email = ? LIMIT 1"
    );
    $stmtDoador->execute([$user['email']]);
    $perfil = $stmtDoador->fetch(PDO::FETCH_ASSOC);
    $tipo   = 'doador';
 
    // Fallback: beneficiário
    if (!$perfil) {
        $stmtONG = $pdo->prepare(
            "SELECT b.nome_receptor, b.localizacao
               FROM beneficiario b
               JOIN usuario u ON u.id_usuario = ?
              WHERE b.email = ?
              LIMIT 1"
        );
        $stmtONG->execute([$id_usuario, $user['email']]);
        $perfil = $stmtONG->fetch(PDO::FETCH_ASSOC);
        $tipo   = 'beneficiario';
    }
 
    if (!$perfil) {
        die("Perfil não encontrado. Entre em contato com o suporte.");
    }
 
} catch (PDOException $e) {
    // Nunca exponha detalhes do banco ao usuário
    error_log("perfil.php PDOException: " . $e->getMessage());
    die("Erro interno ao carregar o perfil. Tente novamente.");
}
 
// Helper de escape — atalho local
function e(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
 
// Mensagem de sucesso via query-string (valor limitado ao esperado)
$msg = '';
if (isset($_GET['status']) && $_GET['status'] === 'atualizado') {
    $msg = '<div class="alerta-ok" role="alert">Dados atualizados com sucesso!</div>';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Meu Perfil – Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/perfil.css">
    <style>
        /* Estilos inline mínimos de fallback */
        body { font-family: sans-serif; background: #f4f6f9; }
        .perfil-container { max-width: 600px; margin: 50px auto; padding: 24px;
            border: 1px solid #ddd; border-radius: 8px; background: #fff; }
        .alerta-ok  { background:#d4edda; color:#155724; padding:10px;
            margin-bottom:20px; border-radius:5px; }
        .campo-label { font-weight: bold; color: #555; }
        .campo-valor { margin-bottom: 16px; }
        .btn-primary { background:#007bff; color:#fff; padding:10px 20px;
            text-decoration:none; border-radius:5px; display:inline-block; }
        .btn-secondary { color:#666; padding:10px; display:inline-block; }
        .acoes { margin-top:30px; display:flex; gap:10px; flex-wrap:wrap; }
    </style>
</head>
<body>
    <div class="perfil-container">
 
        <?= $msg /* Já é HTML estático – não vem de input do usuário */ ?>
 
        <h1>Meu Perfil</h1>
 
        <div class="campo-valor">
            <span class="campo-label">E-mail:</span>
            <?= e($user['email']) ?>
        </div>
 
        <?php if ($tipo === 'doador'): ?>
            <div class="campo-valor">
                <span class="campo-label">Nome:</span>
                <?= e($perfil['nome']) ?>
            </div>
        <?php else: ?>
            <div class="campo-valor">
                <span class="campo-label">Instituição:</span>
                <?= e($perfil['nome_receptor']) ?>
            </div>
            <div class="campo-valor">
                <span class="campo-label">Localização:</span>
                <?= e($perfil['localizacao']) ?>
            </div>
        <?php endif; ?>
 
        <div class="acoes">
            <a href="editar_perfil.php" class="btn-primary">Editar Informações</a>
            <a href="home_usuario.php" class="btn-secondary">Sair</a>
        </div>
    </div>
</body>
</html> 