<?php
// ============================================================
//  deletar_conta.php  –  src/api/deletar_conta.php
// ============================================================
session_start();
 
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Content-Type: application/json');
 
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'Método inválido.']);
    exit();
}
 
// CSRF
if (
    empty($_SESSION['csrf_token']) ||
    empty($_POST['csrf_token'])    ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    echo json_encode(['ok' => false, 'msg' => 'Token inválido.']);
    exit();
}
 
require_once __DIR__ . '/database.php';
/** @var PDO $pdo */
 
try {
    $pdo->beginTransaction();
 
    // ── Ramo USUÁRIO (doador) ──────────────────────────────
    if (!empty($_SESSION['usuario']['id_usuario'])) {
 
        $id_usuario = (int) $_SESSION['usuario']['id_usuario'];
 
        // Verifica se existe
        $stmt = $pdo->prepare("SELECT id_usuario FROM usuario WHERE id_usuario = ? LIMIT 1");
        $stmt->execute([$id_usuario]);
        if (!$stmt->fetch()) {
            throw new RuntimeException("Usuário não encontrado.");
        }
 
        // Deleta doador vinculado pelo id_usuario
        $pdo->prepare("DELETE FROM doador WHERE id_usuario = ?")->execute([$id_usuario]);
 
        // Deleta usuário
        $pdo->prepare("DELETE FROM usuario WHERE id_usuario = ?")->execute([$id_usuario]);
 
    // ── Ramo ONG ─────────────────────────────────────────
    } elseif (!empty($_SESSION['ong']['id'])) {
 
        $id_ong = (int) $_SESSION['ong']['id'];
 
        $stmt = $pdo->prepare("SELECT id_ong FROM ong WHERE id_ong = ? LIMIT 1");
        $stmt->execute([$id_ong]);
        if (!$stmt->fetch()) {
            throw new RuntimeException("ONG não encontrada.");
        }
 
        // Deleta ONG
        $pdo->prepare("DELETE FROM ong WHERE id_ong = ?")->execute([$id_ong]);
 
    } else {
        echo json_encode(['ok' => false, 'msg' => 'Não autorizado.']);
        exit();
    }
 
    $pdo->commit();
 
    // Destroi sessão
    $_SESSION = [];
    session_destroy();
 
    echo json_encode(['ok' => true]);
 
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("deletar_conta.php Exception: " . $e->getMessage());
    echo json_encode(['ok' => false, 'msg' => 'Erro interno. Tente novamente.']);
}
