<?php
session_start();

$_SESSION['id_usuario'] = 1;
?>

<form action="chat_id.php" method="POST">
    <input type="text" name="chat_id" placeholder="Digite o chat_id">
    <button type="submit">
        Salvar Telegram
    </button>
</form>

chat_id = 8748565935;