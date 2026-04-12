<?php
session_start();

require_once __DIR__ . '/database.php';

if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../public/pages/login.php");
    exit();
}

$id_usuario = $_SESSION['usuario_id'];

$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare("SELECT email FROM usuario WHERE id_usuario = ?");
    $stmt->execute([$id_usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("Utilizador não encontrado no sistema.");
    }

    $email_usuario = $user['email'];

    
    if (isset($_POST['nome'])) { 

        $nome     = trim($_POST['nome']);
        $telefone = trim($_POST['telefone']);

        $sql = "UPDATE doador SET nome = ?, telefone = ? WHERE email = ?";
        $stmtUpdate = $pdo->prepare($sql);
        $stmtUpdate->execute([$nome, $telefone, $email_usuario]);

    } elseif (isset($_POST['nome_receptor'])) { 

        $nome_receptor = trim($_POST['nome_receptor']);
        $localizacao   = trim($_POST['localizacao']);

        $sql = "UPDATE beneficiario SET nome_receptor = ?, localizacao = ? 
                WHERE id_beneficiario = (SELECT id_vinc_beneficiario FROM usuario WHERE id_usuario = ?)";
        $stmtUpdate = $pdo->prepare($sql);
        $stmtUpdate->execute([$nome_receptor, $localizacao, $id_usuario]);
    }

    $pdo->commit();

    header("Location: ../../public/pages/perfil.php?status=atualizado");
    exit();

} catch (Exception $e) {
    $pdo->rollBack();

    die("Erro ao atualizar os dados: " . $e->getMessage());
}