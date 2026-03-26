<?php
// verify.php - Valida o token e ativa a conta
require 'test_email_bismark/database.php'; // usa o $pdo já configurado

if (!isset($_GET['token']) || empty(trim($_GET['token']))) {
    mostrarPagina('erro', 'Token ausente ou inválido.');
    exit;
}

$token = htmlspecialchars(trim($_GET['token']));

try {
    // Busca usuário pelo token — colunas corretas do seu banco
    $stmt = $pdo->prepare("
        SELECT id, confirmado 
        FROM usuarios 
        WHERE token_confirmacao = :token 
        LIMIT 1
    ");
    $stmt->execute(['token' => $token]);
    $user = $stmt->fetch();

    if (!$user) {
        mostrarPagina('erro', 'Link inválido ou expirado.');
    } elseif ($user['confirmado'] == 1) {
        mostrarPagina('info', 'Sua conta já foi confirmada. Faça login.');
    } else {
        // Ativa a conta e invalida o token
        $upd = $pdo->prepare("
            UPDATE usuarios 
            SET confirmado = 1, token_confirmacao = NULL 
            WHERE id = :id
        ");
        $upd->execute(['id' => $user['id']]);
        mostrarPagina('sucesso', 'E-mail confirmado com sucesso! Sua conta está ativa.');
    }

} catch (PDOException $e) {
    error_log("Erro verify.php: " . $e->getMessage());
    mostrarPagina('erro', 'Erro ao processar. Tente novamente.');
}

// --- Renderiza página de resposta ---
function mostrarPagina(string $tipo, string $mensagem): void {
    $cores = [
        'sucesso' => ['bg' => '#eafaf1', 'txt' => '#1e7e34', 'icone' => '✅'],
        'erro'    => ['bg' => '#fdecea', 'txt' => '#c0392b', 'icone' => '❌'],
        'info'    => ['bg' => '#d1ecf1', 'txt' => '#0c5460', 'icone' => 'ℹ️'],
    ];
    $c = $cores[$tipo];
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Confirmação de Cadastro</title>
        <style>
            * { box-sizing: border-box; }
            body {
                font-family: Arial, sans-serif;
                background: #f4f6f8;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                margin: 0;
            }
            .card {
                background: #fff;
                padding: 40px 32px;
                border-radius: 10px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                text-align: center;
                max-width: 400px;
                width: 100%;
            }
            .icone { font-size: 48px; margin-bottom: 16px; }
            .mensagem {
                background: <?= $c['bg'] ?>;
                color: <?= $c['txt'] ?>;
                padding: 14px 18px;
                border-radius: 6px;
                font-size: 15px;
                margin-bottom: 24px;
            }
            a {
                display: inline-block;
                padding: 10px 24px;
                background: #007BFF;
                color: #fff;
                border-radius: 5px;
                text-decoration: none;
                font-size: 14px;
            }
            a:hover { background: #0056b3; }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="icone"><?= $c['icone'] ?></div>
            <div class="mensagem"><?= htmlspecialchars($mensagem) ?></div>
            <a href="cadastro.php">← Voltar ao cadastro</a>
        </div>
    </body>
    </html>
    <?php
}
?>