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
            background: linear-gradient(135deg, #e0f2fe 0%, #f0f9ff 100%);
            border: 2px solid #1976d2;
            border-radius: 15px;
            padding: 40px;
            margin: 20px 0;
            box-shadow: 0 8px 32px rgba(25, 118, 210, 0.15);
        }

        .icone-grande {
            font-size: 60px;
            margin-bottom: 20px;
        }

        h2 {
            color: #1976d2;
            font-size: 28px;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .email-destaque {
            background: white;
            border: 2px solid #e3f2fd;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            font-weight: bold;
            word-break: break-all;
            color: #1565c0;
            font-size: 16px;
            box-shadow: 0 4px 12px rgba(25, 118, 210, 0.1);
        }

        .instrucoes {
            text-align: left;
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 5px solid #1976d2;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .instrucoes ol {
            margin-left: 20px;
            line-height: 1.8;
        }

        .instrucoes li {
            margin-bottom: 10px;
            color: #424242;
        }

        .botoes {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 16px;
            min-width: 160px;
        }

        .btn-primario {
            background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(25, 118, 210, 0.3);
        }

        .btn-primario:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(25, 118, 210, 0.4);
        }

        .btn-secundario {
            background: white;
            color: #1976d2;
            border: 2px solid #1976d2;
            box-shadow: 0 4px 15px rgba(25, 118, 210, 0.1);
        }

        .btn-secundario:hover {
            background: #1976d2;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(25, 118, 210, 0.3);
        }

        .aviso {
            background: linear-gradient(135deg, #fff8e1 0%, #fff3c4 100%);
            border: 1px solid #ffb74d;
            color: #f57c00;
            padding: 18px;
            border-radius: 10px;
            margin: 20px 0;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(255, 183, 77, 0.1);
        }

        .mensagem-sucesso {
            color: #2e7d32;
            font-size: 18px;
            margin-top: 10px;
            font-weight: 500;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="sucesso-container">
        <div class="icone-grande"><?= htmlspecialchars($dados['icone'], ENT_QUOTES, 'UTF-8') ?></div>
        <h2><?= htmlspecialchars($dados['titulo'], ENT_QUOTES, 'UTF-8') ?></h2>
        <p class="mensagem-sucesso">✅ <?= htmlspecialchars($dados['mensagem'], ENT_QUOTES, 'UTF-8') ?></p>
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
        <strong>⏱️ Importante:</strong> O link de confirmação enviado para seu e-mail é válido por <strong> 5 minutos </strong>.
        Após esse período, você precisará fazer um novo cadastro. Verifique também sua pasta de <strong>spam</strong> se não encontrar o e-mail.
    </div>

    <div class="botoes">
        <?php if ($tipo === 'admin'): ?>
            <a href="../../src/admin/login_admin.php" class="btn btn-secundario">🔐 Fazer Login como Admin</a>
            <a href="../../src/admin/index.php" class="btn btn-primario">🏠 Ir para Painel Admin</a>
        <?php elseif ($tipo === 'ong'): ?>
            <a href="login_ong.php" class="btn btn-secundario">🏢 Fazer Login como ONG</a>
            <a href="index.php" class="btn btn-primario">🏠 Voltar ao Início</a>
        <?php else: ?>
            <a href="login.php" class="btn btn-secundario">👤 Fazer Login</a>
            <a href="index.php" class="btn btn-primario">🏠 Voltar ao Início</a>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
