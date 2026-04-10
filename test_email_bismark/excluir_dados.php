<?php

require '../../test_email_bismark/database.php'; // Ajustar caminho

// No futuro, usar session_start() e verificará o ID da ONG logada
session_start();
$id_ong = $_SESSION['usuario_id'] ?? null; 

if (!$id_ong) {
    die(json_encode(['ok' => false, 'msg' => 'Acesso negado.']));
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? ''; // 'parcial' ou 'total'

    try {
        if ($acao === 'parcial') {
            // EXCLUSÃO PARCIAL (LGPD: Direito à retificação/minimização)
            $stmt = $pdo->prepare("UPDATE ongs SET telefone = NULL, descricao = NULL WHERE id = ?");
            $stmt->execute([$id_ong]);
            
            echo json_encode(['ok' => true, 'msg' => 'Dados opcionais removidos com sucesso.']);
            
        } elseif ($acao === 'total') {
            // EXCLUSÃO TOTAL (LGPD: Direito ao esquecimento)
            $stmt = $pdo->prepare("DELETE FROM ongs WHERE id = ?");
            $stmt->execute([$id_ong]);
            
            // Finaliza a sessão pois o usuário não existe mais
            session_destroy();
            
            echo json_encode(['ok' => true, 'msg' => 'Sua conta e todos os dados foram excluídos permanentemente.']);
        } else {
            echo json_encode(['ok' => false, 'msg' => 'Ação inválida.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['ok' => false, 'msg' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit;
}
?>