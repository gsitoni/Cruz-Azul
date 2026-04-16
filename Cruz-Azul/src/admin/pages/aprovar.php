<<<<<<< HEAD:src/admin/pages/aprovar.php
<?php
include('../../api/database.php');
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
=======
<?php
include('../../api/database.php');
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
>>>>>>> 9225de5e9a276cc87f9f99734914e694435e6176:Cruz-Azul/src/admin/pages/aprovar.php
?>