<?php
// ============================================================
//  perfil.php  —  public/pages/perfil.php
// ============================================================
session_start();
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'none'");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

require_once __DIR__ . '/../../src/api/database.php';
require_once __DIR__ . '/../../src/crypto/crypto_helpers.php';

if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['id_usuario'])) {
    header("Location: login.php"); exit();
}

$id_usuario = (int) $_SESSION['usuario']['id_usuario'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    $stmtUser = $pdo->prepare("SELECT email, status_cadastro, tipo FROM usuario WHERE id_usuario = ? LIMIT 1");
    $stmtUser->execute([$id_usuario]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
    if (!$user) { session_destroy(); header("Location: login.php"); exit(); }

    $stmtDoador = $pdo->prepare("SELECT nome, cpf, telefone, data_nascimento, iv_dados, chave_aes_cifrada FROM doador WHERE id_usuario = ? LIMIT 1");
    $stmtDoador->execute([$id_usuario]);
    $perfil = $stmtDoador->fetch(PDO::FETCH_ASSOC);
    $tipo   = 'doador';

    if ($perfil) {
        decifrarDoador($perfil);
    } else {
        $stmtONG = $pdo->prepare("SELECT nome, localizacao FROM ong WHERE id_usuario = ? LIMIT 1");
        $stmtONG->execute([$id_usuario]);
        $perfil = $stmtONG->fetch(PDO::FETCH_ASSOC);
        $tipo   = 'ong';
    }

    if (!$perfil) die("Perfil não encontrado.");

} catch (PDOException $e) {
    error_log("perfil.php: " . $e->getMessage());
    die("Erro interno.");
}

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }

function formatarCPF(?string $cpf): string {
    if (!$cpf) return '—';
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    return strlen($cpf) === 11 ? substr($cpf,0,3).'.'.substr($cpf,3,3).'.'.substr($cpf,6,3).'-'.substr($cpf,9,2) : $cpf;
}

function formatarTelefone(?string $tel): string {
    if (!$tel) return '—';
    $tel = preg_replace('/[^0-9]/', '', $tel);
    if (strlen($tel) === 11) return '('.substr($tel,0,2).') '.substr($tel,2,5).'-'.substr($tel,7);
    if (strlen($tel) === 10) return '('.substr($tel,0,2).') '.substr($tel,2,4).'-'.substr($tel,6);
    return $tel;
}

function formatarData(?string $data): string {
    if (!$data) return '—';
    $dt = DateTime::createFromFormat('Y-m-d', $data);
    return $dt ? $dt->format('d/m/Y') : e($data);
}

function calcularIdade(?string $data): int {
    if (!$data) return 0;
    $dt = DateTime::createFromFormat('Y-m-d', $data);
    return $dt ? (int)(new DateTime())->diff($dt)->y : 0;
}

$msg = '';
if (($_GET['status'] ?? '') === 'atualizado') {
    $msg = '<div class="alerta-ok">Dados atualizados com sucesso!</div>';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil – Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/perfil.css">
</head>
<body>
<div class="perfil-container perfil-container--visualizar">
    <?= $msg ?>
    <h1>Meu Perfil</h1>

    <?php if ($tipo === 'doador'): ?>
    <div class="secao-titulo">Dados Pessoais</div>
    <div class="campos-grid">
        <div class="campo-grupo full">
            <span class="campo-label">Nome Completo</span>
            <span class="campo-valor"><?= e($perfil['nome'] ?? '—') ?></span>
        </div>
        <div class="campo-grupo">
            <span class="campo-label">CPF</span>
            <span class="campo-valor"><?= e(formatarCPF($perfil['cpf'] ?? null)) ?></span>
        </div>
        <div class="campo-grupo">
            <span class="campo-label">Data de Nascimento</span>
            <span class="campo-valor">
                <?= e(formatarData($perfil['data_nascimento'] ?? null)) ?>
                <small style="color:#888">(<?= calcularIdade($perfil['data_nascimento'] ?? null) ?> anos)</small>
            </span>
        </div>
    </div>
    <div class="secao-titulo">Contato</div>
    <div class="campos-grid">
        <div class="campo-grupo full">
            <span class="campo-label">E-mail</span>
            <span class="campo-valor"><?= e($user['email']) ?></span>
        </div>
        <div class="campo-grupo">
            <span class="campo-label">Telefone / WhatsApp</span>
            <span class="campo-valor"><?= e(formatarTelefone($perfil['telefone'] ?? null)) ?></span>
        </div>
        <div class="campo-grupo">
            <span class="campo-label">Status da Conta</span>
            <span class="campo-valor">
                <?php
                $status = $user['status_cadastro'] ?? 'pendente';
                $bc = match($status) { 'confirmado'=>'badge-ativo','bloqueado'=>'badge-inativo',default=>'badge-pendente' };
                $bl = match($status) { 'confirmado'=>'Confirmado','bloqueado'=>'Bloqueado',default=>'Pendente de confirmação' };
                ?>
                <span class="badge-status <?= $bc ?>"><?= $bl ?></span>
            </span>
        </div>
        <div class="campo-grupo">
            <span class="campo-label">Tipo de Conta</span>
            <span class="campo-valor tipo-conta"><?= e($user['tipo'] ?? 'doador') ?></span>
        </div>
    </div>
    <?php else: ?>
    <div class="secao-titulo">Dados da Instituição</div>
    <div class="campos-grid">
        <div class="campo-grupo full">
            <span class="campo-label">Nome</span>
            <span class="campo-valor"><?= e($perfil['nome'] ?? '—') ?></span>
        </div>
        <div class="campo-grupo full">
            <span class="campo-label">Localização</span>
            <span class="campo-valor"><?= e($perfil['localizacao'] ?? '—') ?></span>
        </div>
    </div>
    <div class="secao-titulo">Contato</div>
    <div class="campos-grid">
        <div class="campo-grupo full">
            <span class="campo-label">E-mail</span>
            <span class="campo-valor"><?= e($user['email']) ?></span>
        </div>
        <div class="campo-grupo">
            <span class="campo-label">Status</span>
            <span class="campo-valor">
                <?php
                $status = $user['status_cadastro'] ?? 'pendente';
                $bc = match($status) { 'confirmado'=>'badge-ativo','bloqueado'=>'badge-inativo',default=>'badge-pendente' };
                $bl = match($status) { 'confirmado'=>'Confirmado','bloqueado'=>'Bloqueado',default=>'Pendente' };
                ?>
                <span class="badge-status <?= $bc ?>"><?= $bl ?></span>
            </span>
        </div>
    </div>
    <?php endif; ?>

    <div class="acoes">
        <a href="editar_perfil.php" class="btn-primary">Editar Informações</a>
        <a href="home_usuario.php" class="btn-secondary">Voltar ao Início</a>
    </div>
</div>
</body>
</html>