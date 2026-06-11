<?php
// ============================================================
//  perfil_ong.php  —  public/pages/perfil_ong.php
// ============================================================
session_start();

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'none'");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

if (!isset($_SESSION['ong'])) {
    header('Location: login_ong.php');
    exit;
}

require '../../src/api/database.php';
require_once '../../src/crypto/crypto_helpers.php';

function e(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$ongId = (int) ($_SESSION['ong']['id'] ?? 0);

$stmt = $pdo->prepare('
    SELECT nome, email, area_atuacao, localizacao, cidade, sigla_estado,
           endereco, descricao, status_elegibilidade, cnpj,
           iv_dados, chave_aes_cifrada
    FROM ong
    WHERE id_ong = ?
');
$stmt->execute([$ongId]);
$ong = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ong) {
    header('Location: logout.php');
    exit;
}

// Decifra campos sensíveis se houver chave
if (!empty($ong['chave_aes_cifrada']) && !empty($ong['iv_dados'])) {
    decifrarOng($ong);
}

$_SESSION['ong']['nome']        = $ong['nome'];
$_SESSION['ong']['email']       = $ong['email'];
$_SESSION['ong']['area_atuacao']= $ong['area_atuacao'];
$_SESSION['ong']['status']      = $ong['status_elegibilidade'];
$_SESSION['ong']['cidade']      = $ong['cidade'];
$_SESSION['ong']['estado']      = $ong['sigla_estado'];

$localizacao = trim(implode(' / ', array_filter([$ong['cidade'], $ong['sigla_estado']])));

$status = [
    'pendente'  => 'Pendente',
    'aprovado'  => 'Aprovado',
    'rejeitado' => 'Rejeitado',
    'ativo'     => 'Ativo',
    'suspenso'  => 'Suspenso',
][$ong['status_elegibilidade'] ?? ''] ?? 'Não informado';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil da ONG – Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/perfil.css">
</head>
<body>
<div class="perfil-container perfil-container--visualizar">

    <?php if (isset($_GET['status']) && $_GET['status'] === 'atualizado'): ?>
        <div class="alerta-ok">Informações da ONG atualizadas com sucesso.</div>
    <?php endif; ?>

    <h1>Perfil da ONG</h1>

    <div class="secao-titulo">Dados da Instituição</div>
    <div class="campos-grid">
        <div class="campo-grupo full">
            <span class="campo-label">Nome</span>
            <span class="campo-valor"><?= e($ong['nome'] ?: '—') ?></span>
        </div>
        <div class="campo-grupo">
            <span class="campo-label">CNPJ</span>
            <span class="campo-valor"><?= e($ong['cnpj'] ?: '—') ?></span>
        </div>
        <div class="campo-grupo">
            <span class="campo-label">Área de atuação</span>
            <span class="campo-valor"><?= e($ong['area_atuacao'] ?: '—') ?></span>
        </div>
        <div class="campo-grupo full">
            <span class="campo-label">Descrição</span>
            <span class="campo-valor"><?= nl2br(e($ong['descricao'] ?: 'Sem descrição cadastrada.')) ?></span>
        </div>
    </div>

    <div class="secao-titulo">Localização</div>
    <div class="campos-grid">
        <div class="campo-grupo">
            <span class="campo-label">Cidade / Estado</span>
            <span class="campo-valor"><?= e($localizacao ?: '—') ?></span>
        </div>
        <div class="campo-grupo">
            <span class="campo-label">CEP / Localização</span>
            <span class="campo-valor"><?= e($ong['localizacao'] ?: '—') ?></span>
        </div>
        <div class="campo-grupo full">
            <span class="campo-label">Endereço</span>
            <span class="campo-valor"><?= e($ong['endereco'] ?: '—') ?></span>
        </div>
    </div>

    <div class="secao-titulo">Contato e Status</div>
    <div class="campos-grid">
        <div class="campo-grupo full">
            <span class="campo-label">E-mail</span>
            <span class="campo-valor"><?= e($ong['email'] ?: '—') ?></span>
        </div>
        <div class="campo-grupo">
            <span class="campo-label">Status</span>
            <span class="campo-valor">
                <?php
                $bc = match($ong['status_elegibilidade'] ?? '') {
                    'ativo','aprovado' => 'badge-ativo',
                    'rejeitado','suspenso' => 'badge-inativo',
                    default => 'badge-pendente'
                };
                ?>
                <span class="badge-status <?= $bc ?>"><?= e($status) ?></span>
            </span>
        </div>
    </div>

    <div class="acoes">
        <a href="editar_perfil_ong.php" class="btn-primary">Editar Informações</a>
        <a href="home_ong.php" class="btn-secondary">Voltar</a>
    </div>
</div>
</body>
</html>