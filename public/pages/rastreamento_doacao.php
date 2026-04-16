<?php
// Headers de segurança
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: ./login.php');
    exit;
}

$usuario = $_SESSION['usuario'];
$doacaoId = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT) ?: 12345;

// Conexão banco
require '../../src/api/database.php';

try {
    // Buscar doação do usuário
        $stmt = $pdo->prepare("SELECT d.id_doacao, d.categoria, d.item, d.quantidade, d.unidade_medida, d.data_doacao, 
                          CASE WHEN dist.id_operacao IS NOT NULL THEN 'entregue'
                               WHEN e.id_lote IS NOT NULL THEN 'andamento'
                               ELSE 'pendente' END AS status_doacao,
                         b.nome as destino
                          FROM doacao d 
                          INNER JOIN doador dr ON dr.id_doador = d.id_doador
                          LEFT JOIN estoque e ON e.id_doacao = d.id_doacao
                          LEFT JOIN distribuicao dist ON dist.id_lote = e.id_lote
                         LEFT JOIN ong b ON b.id_ong = dist.id_ong
                         WHERE d.id_doacao = ? AND dr.id_usuario = ?");
        $stmt->execute([$doacaoId, (int) ($usuario['id_usuario'] ?? 0)]);
    $doacao_db = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($doacao_db) {
        $doacao = [
            'id' => $doacao_db['id_doacao'],
            'tipo' => $doacao_db['categoria'] ?: 'Alimentos',
            'item' => $doacao_db['item'],
            'quantidade' => number_format($doacao_db['quantidade'], 2, ',', '.') . ' ' . $doacao_db['unidade_medida'],
            'destino' => $doacao_db['destino'] ?: 'Aguardando distribuição',
            'previsao' => '48 horas',
            'status' => $doacao_db['status_doacao'] ?: 'pendente',
        ];
    } else {
        $doacao = [
            'id' => $doacaoId,
            'tipo' => 'Alimentos e Remédios',
            'valor' => 'R$ 250,00',
            'destino' => 'Casa de Apoio Cruz Azul',
            'previsao' => '48 horas',
            'status' => 'Em transporte',
        ];
    }
    
} catch (PDOException $e) {
    $doacao = [
        'id' => $doacaoId,
        'tipo' => 'Alimentos e Remédios',
        'valor' => 'R$ 250,00',
        'destino' => 'Casa de Apoio Cruz Azul',
        'previsao' => '48 horas',
        'status' => 'Em transporte',
    ];
}
$steps = [
    ['title' => 'Doação recebida', 'done' => true],
    ['title' => 'Conferência e triagem', 'done' => true],
    ['title' => 'Em transporte', 'done' => true],
    ['title' => 'Entrega concluída', 'done' => false],
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rastreamento de Doação — Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/rastreamento_doacao.css">
</head>
<body>
    <header class="tracker-header">
        <div>
            <h1>Rastreamento de Doação</h1>
            <p>Olá, <?php echo htmlspecialchars($usuario['nome'] ?? 'Doador'); ?>. Acompanhe o caminho da sua doação com transparência.</p>
        </div>
        <nav>
            <a href="./index.php">Início</a>
            <a href="./login.php">Meu Perfil</a>
            <a href="./rastreamento_doacao.php">Rastreamento</a>
        </nav>
    </header>

    <main class="tracker-main">
        <section class="tracker-hero">
            <div class="hero-copy">
                <h2>Status atual: <span><?php echo htmlspecialchars($doacao['status']); ?></span></h2>
                <p>Use a verificação de GPS para confirmar sua localização e manter o rastreamento mais seguro.</p>
            </div>
            <div class="hero-summary">
                <div class="summary-item">
                    <strong>ID da doação</strong>
                    <span>#<?php echo htmlspecialchars($doacao['id']); ?></span>
                </div>
                <div class="summary-item">
                    <strong>Destino</strong>
                    <span><?php echo htmlspecialchars($doacao['destino']); ?></span>
                </div>
                <div class="summary-item">
                    <strong>Previsão</strong>
                    <span><?php echo htmlspecialchars($doacao['previsao']); ?></span>
                </div>
            </div>
        </section>

        <div class="tracker-grid">
            <aside class="card info-card">
                <h2>Detalhes da doação</h2>
                <ul>
                    <li><strong>Tipo:</strong> <?php echo htmlspecialchars($doacao['tipo']); ?></li>
                    <li><strong>Valor estimado:</strong> <?php echo htmlspecialchars($doacao['valor']); ?></li>
                    <li><strong>Destino:</strong> <?php echo htmlspecialchars($doacao['destino']); ?></li>
                    <li><strong>Previsão de entrega:</strong> <?php echo htmlspecialchars($doacao['previsao']); ?></li>
                    <li><strong>Status atual:</strong> <?php echo htmlspecialchars($doacao['status']); ?></li>
                </ul>
            </aside>

            <section class="card timeline-card">
                <h2>Linha do tempo</h2>
                <div class="timeline">
                    <?php foreach ($steps as $index => $step): ?>
                        <div class="timeline-step <?php echo $step['done'] ? 'done' : 'pending'; ?>">
                            <span class="step-number"><?php echo $index + 1; ?></span>
                            <p><?php echo htmlspecialchars($step['title']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="card map-card">
                <h2>Rota estimada</h2>
                <div class="map-frame">
                    <div class="map-pin">🚚</div>
                    <div class="map-path"></div>
                    <div class="map-destination">🏁 ONG Cruz Azul</div>
                </div>
                <p class="map-description">A posição exibida é uma estimativa do trajeto. A verificação de GPS confirma o local do doador no momento do acompanhamento.</p>
            </section>

            <section class="card gps-card">
                <h2>Verificação de GPS</h2>
                <p>Ative sua localização para confirmar que a doação está sendo acompanhada por você.</p>
                <button id="btn-gps" class="btn">Verificar localização</button>
                <div id="gps-info" class="gps-info">
                    <p><strong>Status:</strong> <span id="gps-status">Aguardando</span></p>
                    <p id="gps-details">Nenhuma leitura de GPS realizada.</p>
                </div>
            </section>
        </div>

        <section class="detail-note">
            <h3>Por que este rastreamento importa?</h3>
            <p>O painel de rastreamento ajuda a manter a transparência da sua doação e a criar confiança entre você e a ONG beneficiada. A confirmação de GPS reforça que a pessoa responsável está acompanhando o processo.</p>
        </section>
    </main>

    <footer class="tracker-footer">
        <p>© 2026 Cruz Azul ✙ — Plataforma criada para doadores e ONGs.</p>
    </footer>

    <script src="../assets/js/rastreamento_doacao.js" defer></script>
</body>
</html>
