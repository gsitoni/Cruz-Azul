<?php

session_start();

require __DIR__ . '/database.php';
/** @var PDO $pdo */

$chat_id = $_POST['chat_id'];
$id_usuario = $_SESSION['id_usuario'];

$sql = "
UPDATE usuario
SET telegram_chat_id = ?,
    telegram_2fa_ativo = TRUE
WHERE id_usuario = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$chat_id, $id_usuario]);

echo "Telegram conectado com sucesso!";
