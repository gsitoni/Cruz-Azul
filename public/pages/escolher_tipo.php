<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escolher Tipo de Usuário - Cruz Azul ✙</title>
    <link rel="stylesheet" href="../assets/css/index.css"> <!-- Usando o mesmo CSS do index para consistência -->
    <style>
        .tipo-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .tipo-option {
            display: block;
            margin: 20px 0;
            padding: 15px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .tipo-option:hover {
            background: #0056b3;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
        }
    </style>
</head>
<body>

    <div class="tipo-container">
        <h1>Escolher Tipo de Usuário</h1>
        <p>Selecione o tipo de conta que deseja criar:</p>
        
        <a href="cadastro.php" class="tipo-option">Doador</a>
        <a href="cadastro_ong.php" class="tipo-option">ONG</a>
        <a href="cadastro_admin.php" class="tipo-option">Administrador</a>
        
        <a href="index.php" class="back-link">Voltar ao início</a>
    </div>

</body>
</html>