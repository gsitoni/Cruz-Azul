<?php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['nivel'] !== 'admin') {
    header("Location: ../../../pages/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Usuários ADM</title>
    <link rel="stylesheet" href="../assets/css/usuarios.css">
</head>
<body>
    <div class="container">
        <header style="display: flex; justify-content: space-between; align-items: center; padding: 20px;">
            <h2>Painel de Controle - Usuários</h2>
            <div>
                <span>Admin: <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong></span>
                <a href="../../../api/logout.php" style="color: #ff4d4d; margin-left: 15px; text-decoration: none;">Sair</a>
            </div>
        </header>

        <table id="tabela-usuarios">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="corpo-tabela">
                </tbody>
        </table>
    </div>

    <script src="../assets/js/usuarios_adm.js"></script>
</body>
</html>