<?php
require __DIR__ . '/../../api/database.php';
/** @var PDO $pdo */
session_start();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die("ID inválido");
}

$sql = "UPDATE doacao SET status = 'aprovado' WHERE id_doacao = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $id]);

header("Location: admin_doacoes.php");
exit;
?>
