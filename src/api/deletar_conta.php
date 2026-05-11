<?php
// ============================================================
//  deletar_conta.php  –  src/api/deletar_conta.php
//  Exclusão PARCIAL: anonimiza dados pessoais e bloqueia o
//  acesso, mas preserva o histórico de doações/distribuições.
// ============================================================
session_start();
 
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Content-Type: application/json');
 
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'Método inválido.']);
    exit();
}
 
// --- CSRF ---
if (
    empty($_SESSION['csrf_token']) ||
    empty($_POST['csrf_token'])    ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    echo json_encode(['ok' => false, 'msg' => 'Token inválido.']);
    exit();
}
 
require_once __DIR__ . '/database.php';
 
// Gera um sufixo único para anonimização (evita conflitos de UNIQUE keys)
$sufixo = bin2hex(random_bytes(8)); // ex: "a3f2b1c4d5e6f7a8"
 
try {
    $pdo->beginTransaction();
 
    // ══════════════════════════════════════════════════════
    //  RAMO USUÁRIO (doador)
    // ══════════════════════════════════════════════════════
    if (!empty($_SESSION['usuario']['id_usuario'])) {
 
        $id_usuario = (int) $_SESSION['usuario']['id_usuario'];
 
        // Confirma existência
        $stmt = $pdo->prepare("SELECT id_usuario FROM usuario WHERE id_usuario = ? LIMIT 1");
        $stmt->execute([$id_usuario]);
        if (!$stmt->fetch()) {
            throw new RuntimeException("Usuário não encontrado.");
        }
 
        // 1. Anonimiza tabela `doador`
        //    Preserva: id_doador, id_usuario (FK para histórico de doações)
        //    Apaga:    nome, cpf, telefone, data_nascimento
        $pdo->prepare("
            UPDATE doador SET
                nome             = 'Usuário Removido',
                cpf              = NULL,
                telefone         = '00000000000',
                data_nascimento  = NULL
            WHERE id_usuario = ?
        ")->execute([$id_usuario]);
 
        // 2. Anonimiza tabela `usuario`
        //    Preserva: id_usuario, tipo (para integridade referencial)
        //    Apaga:    nome, email, senha_hash, tokens, chave 2FA
        //    Bloqueia: status_cadastro = 'bloqueado' (impede relogin)
        $pdo->prepare("
            UPDATE usuario SET
                nome               = 'Usuário Removido',
                email              = CONCAT('removido_', ?, '@excluido.invalid'),
                senha_hash         = '',
                status_cadastro    = 'bloqueado',
                token_confirmacao  = NULL,
                token_recuperacao  = NULL,
                token_expira_em    = NULL,
                chave_2fa          = NULL
            WHERE id_usuario = ?
        ")->execute([$sufixo, $id_usuario]);
 
    // ══════════════════════════════════════════════════════
    //  RAMO ONG
    // ══════════════════════════════════════════════════════
    } elseif (!empty($_SESSION['ong']['id'])) {
 
        $id_ong = (int) $_SESSION['ong']['id'];
 
        // Confirma existência
        $stmt = $pdo->prepare("SELECT id_ong FROM ong WHERE id_ong = ? LIMIT 1");
        $stmt->execute([$id_ong]);
        if (!$stmt->fetch()) {
            throw new RuntimeException("ONG não encontrada.");
        }
 
        // 1. Anonimiza tabela `ong`
        //    Preserva: id_ong, id_usuario, cnpj (obrigação legal), classificacao_risco
        //    Apaga:    nome, email, senha, tokens, endereço, contato, descrição
        //    Bloqueia: status_elegibilidade = 'suspenso' (impede operações)
        $pdo->prepare("
            UPDATE ong SET
                nome                  = 'ONG Removida',
                email                 = CONCAT('removido_', ?, '@excluido.invalid'),
                senha_hash            = NULL,
                token_confirmacao     = NULL,
                localizacao           = '',
                endereco              = NULL,
                cidade                = NULL,
                sigla_estado          = NULL,
                descricao             = NULL,
                area_atuacao          = NULL,
                status_elegibilidade  = 'suspenso',
                data_atualizacao      = NOW()
            WHERE id_ong = ?
        ")->execute([$sufixo, $id_ong]);
 
        // 2. Se a ONG tiver um usuario vinculado (id_usuario), anonimiza também
        $stmtIdUser = $pdo->prepare("SELECT id_usuario FROM ong WHERE id_ong = ? LIMIT 1");
        $stmtIdUser->execute([$id_ong]);
        $row = $stmtIdUser->fetch(PDO::FETCH_ASSOC);
 
        if ($row && !empty($row['id_usuario'])) {
            $id_usuario_ong = (int) $row['id_usuario'];
            $pdo->prepare("
                UPDATE usuario SET
                    nome               = 'Usuário Removido',
                    email              = CONCAT('removido_ong_', ?, '@excluido.invalid'),
                    senha_hash         = '',
                    status_cadastro    = 'bloqueado',
                    token_confirmacao  = NULL,
                    token_recuperacao  = NULL,
                    token_expira_em    = NULL,
                    chave_2fa          = NULL
                WHERE id_usuario = ?
            ")->execute([$sufixo, $id_usuario_ong]);
        }
 
    } else {
        echo json_encode(['ok' => false, 'msg' => 'Não autorizado.']);
        exit();
    }
 
    $pdo->commit();
 
    // Destrói a sessão completamente (igual ao logout.php)
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
 
    echo json_encode(['ok' => true]);
 
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("deletar_conta.php Exception: " . $e->getMessage());
    echo json_encode(['ok' => false, 'msg' => 'Erro interno. Tente novamente.']);
}