<?php
session_start();

// Se já está logado como admin, redireciona para dashboard
if (isset($_SESSION['usuario']) && isset($_SESSION['usuario']['permissao']) && strpos($_SESSION['usuario']['permissao'], 'Admin') !== false) {
    header('Location: pages/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Administrativo - Cruz Azul ✙</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 50%, #60a5fa 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
        }

        .header {
            margin-bottom: 40px;
        }

        .header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .header p {
            color: #666;
            font-size: 14px;
            margin-top: 10px;
        }

        .options {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .btn {
            padding: 15px 20px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            width: 100%;
            text-align: center;
        }

        .btn-login {
            background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
            color: white;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(37, 99, 235, 0.35);
        }

        .btn-cadastro {
            background: #e0f2fe;
            color: #1d4ed8;
            border: 2px solid #1d4ed8;
        }

        .btn-cadastro:hover {
            background: #1d4ed8;
            color: white;
            transform: translateY(-2px);
        }

        .icon {
            font-size: 32px;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: #764ba2;
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1><span class="icon">🔐</span> Cruz Azul Admin</h1>
        <p>Painel de Acesso Administrativo</p>
    </div>

    <div class="options">
        <a href="./login_admin.php" class="btn btn-login">
            <span class="icon">🔓</span> Fazer Login
        </a>
        <a href="./cadastro_admin.php" class="btn btn-cadastro">
            <span class="icon">📝</span> Cadastrar Administrador
        </a>
    </div>

    <a href="../../public/pages/index.php" class="back-link">← Voltar ao início</a>
</div>

</body>
</html>
