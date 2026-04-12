<?php
header('Content-Type: application/json');
require '../../test_email_bismark/database.php'; 

session_start();
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    die(json_encode(['ok' => false, 'msg' => 'Acesso negado.']));
}

$id_usuario = $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $pdo->beginTransaction();

    try {
        if ($acao === 'parcial') {
            $pdo->prepare("UPDATE beneficiario SET localizacao = 'REMOVIDO', status_elegibilidade = 'inativo' 
                           WHERE id_beneficiario = (SELECT id_vinc_beneficiario FROM usuario WHERE id_usuario = ?)")
                ->execute([$id_usuario]);

            $pdo->prepare("UPDATE usuario SET chave_2fa = NULL, token_recuperacao = NULL WHERE id_usuario = ?")
                ->execute([$id_usuario]);

            $pdo->commit();
            echo json_encode(['ok' => true, 'msg' => 'Dados da ONG anonimizados com sucesso.']);

        } elseif ($acao === 'total') {
            $stmt = $pdo->prepare("SELECT id_beneficiario FROM beneficiario WHERE cnpj = (SELECT cnpj_vinculado FROM usuario WHERE id_usuario = ?)");
            $stmt->execute([$id_usuario]);
            $ong = $stmt->fetch();

            if ($ong) {
                $id_b = $ong['id_beneficiario'];

                $pdo->prepare("DELETE FROM distribuicao WHERE id_beneficiario = ?")->execute([$id_b]);

                $pdo->prepare("DELETE FROM beneficiario WHERE id_beneficiario = ?")->execute([$id_b]);
            }

            $pdo->prepare("DELETE FROM usuario WHERE id_usuario = ?")->execute([$id_usuario]);

            $pdo->commit();
            session_destroy();
            echo json_encode(['ok' => true, 'msg' => 'A ONG e todos os registos vinculados foram removidos permanentemente.']);
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'msg' => 'Erro ao processar exclusão: ' . $e->getMessage()]);
    }
}
