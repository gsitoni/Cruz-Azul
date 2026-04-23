<?php
require __DIR__ . '/auth.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../index.php");
    exit();
}

require __DIR__ . '/../../api/database.php';

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM ong WHERE status_elegibilidade = 'pendente'");
    $stmt->execute();
    $ongsPendentes = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM ong WHERE status_elegibilidade = 'ativo'");
    $stmt->execute();
    $ongsAtivas = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM ong WHERE status_elegibilidade = 'rejeitado'");
    $stmt->execute();
    $ongsRejeitadas = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM usuario");
    $stmt->execute();
    $totalUsuarios = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM doacao");
    $stmt->execute();
    $totalDoacoes = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    $stmt = $pdo->prepare("
        SELECT id_ong, nome, email, status_elegibilidade
        FROM ong
        WHERE status_elegibilidade = 'pendente'
        ORDER BY id_ong DESC
        LIMIT 5
    ");
    $stmt->execute();
    $ultimasPendentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro no banco: " . $e->getMessage());
}

$nomeAdmin = htmlspecialchars($_SESSION['usuario']['nome'] ?? 'Administrador', ENT_QUOTES, 'UTF-8');
$totalONGs = $ongsPendentes + $ongsAtivas + $ongsRejeitadas;
$taxaAtivacao = $totalONGs > 0 ? round(($ongsAtivas / $totalONGs) * 100) : 0;
$dataAtualizacao = date('d/m/Y H:i');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=20260423b">
</head>
<body>
    <header class="topbar">
        <div>
            <h1>Cruz Azul Admin</h1>
            <p>Painel central de acompanhamento e moderacao</p>
        </div>
        <nav>
            <ul>
                <li><a class="active" href="./dashboard.php">Dashboard</a></li>
                <li><a href="./ongs.php">ONGs</a></li>
                <li><a href="./logs.php">Logs</a></li>
                <li><a href="./usuarios.php">Usuarios</a></li>
                <li><a href="./configuracoes.php">Configuracoes</a></li>
                <li><a href="?logout=true">Sair</a></li>
            </ul>
        </nav>
    </header>

    <main class="page">
        <section class="hero">
            <div>
                <span class="eyebrow">Visao operacional</span>
                <h2>Ola, <?= $nomeAdmin ?></h2>
                <p>Priorize as ONGs pendentes e acompanhe os indicadores essenciais da plataforma em tempo real.</p>
                <span class="hero-update">Atualizado em <?= htmlspecialchars($dataAtualizacao, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <a class="hero-action" href="./ongs.php">Revisar solicitacoes</a>
        </section>

        <section class="metrics-grid">
            <article class="metric-card alert">
                <h3>ONGs pendentes</h3>
                <p class="value"><?= $ongsPendentes ?></p>
                <span class="meta">Exigem avaliacao administrativa</span>
            </article>

            <article class="metric-card">
                <h3>ONGs ativas</h3>
                <p class="value"><?= $ongsAtivas ?></p>
                <span class="meta">Taxa de ativacao: <?= $taxaAtivacao ?>%</span>
            </article>

            <article class="metric-card">
                <h3>Usuarios cadastrados</h3>
                <p class="value"><?= $totalUsuarios ?></p>
                <span class="meta">Contas totais no ecossistema</span>
            </article>

            <article class="metric-card">
                <h3>Doacoes registradas</h3>
                <p class="value"><?= $totalDoacoes ?></p>
                <span class="meta">Volume historico de operacoes</span>
            </article>
        </section>

        <section class="panel-grid">
            <article class="panel">
                <div class="panel-header">
                    <h3>Fila de aprovacao imediata</h3>
                    <a href="./ongs.php">Abrir gerenciamento completo</a>
                </div>

                <?php if (empty($ultimasPendentes)): ?>
                    <p class="empty-state">Nao ha ONGs pendentes no momento.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>E-mail</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimasPendentes as $ong): ?>
                                <tr>
                                    <td><?= htmlspecialchars($ong['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($ong['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="badge pending"><?= strtoupper(htmlspecialchars($ong['status_elegibilidade'], ENT_QUOTES, 'UTF-8')) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </article>

            <article class="panel panel-list">
                <h3>Resumo estrategico</h3>
                <ul>
                    <li><strong><?= $ongsPendentes ?></strong> solicitacoes aguardando moderacao.</li>
                    <li><strong><?= $ongsRejeitadas ?></strong> ONGs rejeitadas para revisao de politica.</li>
                    <li><strong><?= $totalUsuarios ?></strong> usuarios impactados pelos servicos.</li>
                    <li><strong><?= $totalDoacoes ?></strong> registros de doacao consolidados.</li>
                </ul>
            </article>
        </section>

        <section class="quick-actions">
            <a href="./ongs.php" class="quick-card">
                <strong>Gestao de ONGs</strong>
                <span>Aprove, rejeite e acompanhe pendencias.</span>
            </a>
            <a href="./usuarios.php" class="quick-card">
                <strong>Usuarios</strong>
                <span>Consulte cadastro e governanca de contas.</span>
            </a>
            <a href="./logs.php" class="quick-card">
                <strong>Auditoria</strong>
                <span>Revise eventos e rastros operacionais.</span>
            </a>
        </section>
    </main>

    <footer>
        <p>&copy; 2026 Cruz Azul</p>
    </footer>
</body>
</html>
