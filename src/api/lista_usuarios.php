<?php
session_start();
require_once __DIR__ . '/database.php';
/** @var PDO $pdo */

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || $_SESSION['nivel'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Acesso negado"]);
    exit();
}

try {
    $query = "SELECT id, nome, email, status FROM usuarios_adm"; 
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Retorna os dados em JSON
    echo json_encode($usuarios);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erro ao processar dados"]);
}
?>
