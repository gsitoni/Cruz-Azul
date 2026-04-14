<?php
// ============================================================
//  editar_perfil.php  –  public/pages/editar_perfil.php
//  Segurança: XSS, Clickjacking, CSRF token, HTML-injection,
//             session-fixation, type juggling
// ============================================================
session_start();
 
// --- Cabeçalhos de segurança ---
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
 
require_once __DIR__ . '/../../src/api/database.php';
 
// --- Autenticação ---
if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['id_usuario'])) {
    header("Location: login.php");
    exit();
}
 
$id_usuario = (int) $_SESSION['usuario']['id_usuario'];
 
// --- Gera (ou reutiliza) token CSRF ---
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
 
    $stmtDoador = $pdo->prepare(
        "SELECT nome FROM doador WHERE email = ? LIMIT 1"
    );
    $stmtDoador->execute([$user['email']]);
    $perfil = $stmtDoador->fetch(PDO::FETCH_ASSOC);
    $tipo   = 'doador';
 
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
    error_log("editar_perfil.php PDOException: " . $e->getMessage());
    die("Erro interno ao carregar o perfil. Tente novamente.");
}
 
// Helper de escape
function e(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Editar Perfil – Cruz Azul</title>
    <style>
        body { font-family: sans-serif; background: #f4f6f9; }
        .perfil-container { max-width: 600px; margin: 50px auto; padding: 24px;
            border: 1px solid #ddd; border-radius: 8px; background: #fff; }
        .campo { margin-bottom: 18px; }
        label { display: block; font-weight: bold; color: #555; margin-bottom: 4px; }
        input[type="text"] { width: 100%; padding: 9px; box-sizing: border-box;
            border: 1px solid #ccc; border-radius: 5px; font-size: 1rem; }
        input[type="text"]:focus { outline: 2px solid #007bff; border-color: #007bff; }
        .btn-salvar { background:#28a745; color:#fff; padding:10px 22px;
            border:none; border-radius:5px; cursor:pointer; font-size:1rem; }
        .btn-salvar:hover { background:#218838; }
        .btn-cancelar { color:#666; padding:10px; text-decoration:none; margin-left:10px; }
        .aviso { font-size:.8rem; color:#888; margin-top:4px; }
    </style>
</head>
<body>
    <div class="perfil-container">
        <?php if (!empty($_GET['erro'])): ?>
            <div style="background:#f8d7da;color:#721c24;padding:10px;
                        margin-bottom:20px;border-radius:5px;" role="alert">
                <?= htmlspecialchars($_GET['erro'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
            </div>
        <?php endif; ?>
 
        <h1>Editar Perfil</h1>
 
        <!--
            Action aponta para o script de atualização.
            CSRF token em campo hidden impede requisições forjadas cross-site.
        -->
        <form action="../../src/api/atualizar_usuario.php" method="POST"
              autocomplete="off" novalidate>
 
            <!-- CSRF -->
            <input type="hidden" name="csrf_token"
                   value="<?= e($_SESSION['csrf_token']) ?>">
 
            <?php if ($tipo === 'doador'): ?>
 
                <div class="campo">
                    <label for="nome">Nome completo</label>
                    <input type="text"
                           id="nome"
                           name="nome"
                           value="<?= e($perfil['nome']) ?>"
                           maxlength="200"
                           required
                           pattern="[A-Za-zÀ-ÿ\s'\-]{2,200}">
                    <span class="aviso">Somente letras e espaços (máx. 200 caracteres).</span>
                </div>
 
                <div class="campo">
                    <label for="senha_atual">Senha atual</label>
                    <input type="password"
                           id="senha_atual"
                           name="senha_atual"
                           maxlength="255"
                           autocomplete="current-password">
                    <span class="aviso">Deixe em branco para não alterar a senha.</span>
                </div>

                <div class="campo">
                    <label for="nova_senha">Nova senha</label>
                    <input type="password"
                           id="nova_senha"
                           name="nova_senha"
                           maxlength="255"
                           autocomplete="new-password">
                    <span class="aviso">Mínimo 6 caracteres.</span>
                </div>

                <div class="campo">
                    <label for="confirmar_senha">Confirmar nova senha</label>
                    <input type="password"
                           id="confirmar_senha"
                           name="confirmar_senha"
                           maxlength="255"
                           autocomplete="new-password">
                </div>
 
            <?php else: ?>
 
                <div class="campo">
                    <label for="nome_receptor">Nome da Instituição</label>
                    <input type="text"
                           id="nome_receptor"
                           name="nome_receptor"
                           value="<?= e($perfil['nome_receptor']) ?>"
                           maxlength="300"
                           required>
                    <span class="aviso">Máximo 300 caracteres.</span>
                </div>
 
                <div class="campo">
                    <label for="localizacao">Localização (CEP ou endereço)</label>
                    <input type="text"
                           id="localizacao"
                           name="localizacao"
                           value="<?= e($perfil['localizacao']) ?>"
                           maxlength="50">
                    <span class="aviso">Máximo 50 caracteres.</span>
                </div>

                <div class="campo">
                    <label for="senha_atual">Senha atual</label>
                    <input type="password"
                           id="senha_atual"
                           name="senha_atual"
                           maxlength="255"
                           autocomplete="current-password">
                    <span class="aviso">Deixe em branco para não alterar a senha.</span>
                </div>

                <div class="campo">
                    <label for="nova_senha">Nova senha</label>
                    <input type="password"
                           id="nova_senha"
                           name="nova_senha"
                           maxlength="255"
                           autocomplete="new-password">
                    <span class="aviso">Mínimo 6 caracteres.</span>
                </div>

                <div class="campo">
                    <label for="confirmar_senha">Confirmar nova senha</label>
                    <input type="password"
                           id="confirmar_senha"
                           name="confirmar_senha"
                           maxlength="255"
                           autocomplete="new-password">
                </div>
 
            <?php endif; ?>
 
            <div style="margin-top:10px;">
                <button type="submit" class="btn-salvar">Salvar Alterações</button>
                <a href="perfil.php" class="btn-cancelar">Cancelar</a>
            </div>
 
        </form>
    </div>

    <script>
    document.querySelector('form').addEventListener('submit', function(e) {
        const nova    = document.getElementById('nova_senha');
        const conf    = document.getElementById('confirmar_senha');
        const atual   = document.getElementById('senha_atual');
        if (!nova || !conf) return;
        // Se preencheu nova senha, exige senha atual e confirmação
        if (nova.value.length > 0) {
            if (atual.value.length === 0) {
                e.preventDefault();
                alert('Informe a senha atual para alterá-la.');
                atual.focus();
                return;
            }
            if (nova.value.length < 6) {
                e.preventDefault();
                alert('A nova senha deve ter pelo menos 6 caracteres.');
                nova.focus();
                return;
            }
            if (nova.value !== conf.value) {
                e.preventDefault();
                alert('As senhas não coincidem.');
                conf.focus();
                return;
            }
        }
    });
    </script>
</body>
</html>