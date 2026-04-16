<?php
// cadastro_concluido.php - Página de confirmação de cadastro

session_start();

// Headers de segurança
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Captura os parâmetros
$email = isset($_GET['email']) ? filter_var($_GET['email'], FILTER_SANITIZE_EMAIL) : '';
$tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : 'usuario';

// Mapeamento de tipos para mensagens
$tipos = [
    'usuario' => [
        'titulo' => 'Cadastro de Doador Realizado!',
        'icone' => '👤',
        'mensagem' => 'Seu cadastro como doador foi realizado com sucesso!',
    ],
    'ong' => [
        'titulo' => 'Cadastro de ONG Realizado!',
        'icone' => '🏢',
        'mensagem' => 'Seu cadastro como ONG foi realizado com sucesso!',
    ],
    'admin' => [
        'titulo' => 'Cadastro de Administrador Realizado!',
        'icone' => '🔐',
        'mensagem' => 'Seu cadastro como administrador foi realizado com sucesso!',
    ],
];

// Obtém os dados do tipo
$dados = $tipos[$tipo] ?? $tipos['usuario'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($dados['titulo'], ENT_QUOTES, 'UTF-8') ?> — Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/login.css">
    <style>
        .container {
            text-align: center;
        }

        .sucesso-container {
            background: #f0f9ff;
            border: 2px solid #4CAF50;
            border-radius: 10px;
            padding: 40px;
            margin: 20px 0;
        }

        .icone-grande {
            font-size: 60px;
            margin-bottom: 20px;
        }

        h2 {
            color: #4CAF50;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .email-destaque {
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            margin: 20px 0;
            font-weight: bold;
            word-break: break-all;
            color: #333;
        }

        .instrucoes {
            text-align: left;
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #4CAF50;
        }

        .instrucoes ol {
            margin-left: 20px;
            line-height: 1.8;
        }

        .instrucoes li {
            margin-bottom: 10px;
        }

        .botoes {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }

        .btn-primario {
            background: #4CAF50;
            color: white;
        }

        .btn-primario:hover {
            background: #45a049;
        }

        .btn-secundario {
            background: #f0f0f0;
            color: #333;
            border: 2px solid #333;
        }

        .btn-secundario:hover {
            background: #e0e0e0;
        }

        .aviso {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="sucesso-container">
        <div class="icone-grande"><?= htmlspecialchars($dados['icone'], ENT_QUOTES, 'UTF-8') ?></div>
        <h2><?= htmlspecialchars($dados['titulo'], ENT_QUOTES, 'UTF-8') ?></h2>
        <p><?= htmlspecialchars($dados['mensagem'], ENT_QUOTES, 'UTF-8') ?></p>
    </div>

    <?php if (!empty($email)): ?>
    <div class="email-destaque">
        📧 <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php endif; ?>

    <div class="instrucoes">
        <strong>O que fazer agora?</strong>
        <ol>
            <li>Verifique sua caixa de <strong>entrada de e-mail</strong></li>
            <li>Procure por um e-mail de confirmação da Cruz Azul</li>
            <li>Clique no link de confirmação para ativar sua conta</li>
            <li>Se não encontrar, verifique a pasta de <strong>spam</strong></li>
        </ol>
    </div>

    <div class="aviso">
        ⏱️ O link de confirmação é válido por <strong>24 horas</strong>. Após esse período, você precisará fazer um novo cadastro.
    </div>

    <div class="botoes">
        <a href="index.php" class="btn btn-primario">← Voltar ao Início</a>
        <?php if ($tipo === 'admin'): ?>
            <a href="../../src/admin/index.php" class="btn btn-secundario">Ir para Painel Admin</a>
        <?php elseif ($tipo === 'ong'): ?>
            <a href="login_ong.php" class="btn btn-secundario">Fazer Login como ONG</a>
        <?php else: ?>
            <a href="login.php" class="btn btn-secundario">Fazer Login</a>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
