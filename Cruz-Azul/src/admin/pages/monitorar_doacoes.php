<<<<<<< HEAD:src/admin/pages/monitorar_doacoes.php
<?php
include('../../api/database.php');
session_start();


$sql = "SELECT * FROM doacao WHERE status = 'pendente'";
$stmt = $pdo->prepare($sql);
$stmt->execute();

$doacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Doações Pendentes</h2>

<?php foreach ($doacoes as $d): ?>
    <div style="border:1px solid #ccc; margin:10px; padding:10px;">
        <p>ID: <?= $d['id_doacao'] ?></p>
        <p>Item: <?= $d['item'] ?></p>
        <p>Quantidade: <?= $d['quantidade'] ?> <?= $d['unidade_medida'] ?></p>

        <!-- LINKS -->
        <a href="aprovar.php?id=<?= $d['id_doacao'] ?>">✅ Aprovar</a>
        <a href="recusar.php?id=<?= $d['id_doacao'] ?>">❌ Recusar</a>
    </div>
=======
<?php
include('../../api/database.php');
session_start();


$sql = "SELECT * FROM doacao WHERE status = 'pendente'";
$stmt = $pdo->prepare($sql);
$stmt->execute();

$doacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Doações Pendentes</h2>

<?php foreach ($doacoes as $d): ?>
    <div style="border:1px solid #ccc; margin:10px; padding:10px;">
        <p>ID: <?= $d['id_doacao'] ?></p>
        <p>Item: <?= $d['item'] ?></p>
        <p>Quantidade: <?= $d['quantidade'] ?> <?= $d['unidade_medida'] ?></p>

        <!-- LINKS -->
        <a href="aprovar.php?id=<?= $d['id_doacao'] ?>">✅ Aprovar</a>
        <a href="recusar.php?id=<?= $d['id_doacao'] ?>">❌ Recusar</a>
    </div>
>>>>>>> 9225de5e9a276cc87f9f99734914e694435e6176:Cruz-Azul/src/admin/pages/monitorar_doacoes.php
<?php endforeach; ?>