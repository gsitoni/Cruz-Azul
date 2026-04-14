<?php
// Headers de segurança
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Conexão banco
require '../../src/api/database.php';

try {
    // Buscar ONGs por região (simulando com beneficiario)
    $stmt = $pdo->prepare("SELECT nome, endereco, telefone, email FROM beneficiario WHERE status_elegibilidade = 'ativo' ORDER BY nome LIMIT 10");
    $stmt->execute();
    $ongs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $ongs = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Áreas Vulneráveis do Brasil — Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/vulnerabilidade.css">
</head>
<body>
    <header class="page-header">
        <div class="page-title">
            <span>IBGE & Vulnerabilidade</span>
            <h1>Regiões mais vulneráveis do Brasil</h1>
            <p>Mapa interativo com as principais ONGs por região e um panorama de carência social baseado em indicadores do IBGE.</p>
            <p class="notice">Observação: esta página será acessada pela home assim que a página inicial estiver finalizada.</p>
        </div>
    </header>

    <main class="page-content">
        <section class="map-section">
            <div class="card">
                <div class="card-header">
                    <h2>Mapa interativo do Brasil</h2>
                    <p>Toque ou clique para explorar cada região.</p>
                </div>
                <div class="map-wrapper">
                    <div class="svg-container" id="svgContainer">
                        <div class="svg-loader">Carregando mapa do Brasil...</div>
                    </div>
                    <div class="map-tooltip" id="mapTooltip">Clique em uma região</div>
                </div>
                <div class="legend">
                    <div class="legend-item">
                        <span class="legend-color high"></span>
                        <div>
                            <strong>Alto</strong>
                            <p>Vulnerabilidade elevada segundo IBGE.</p>
                        </div>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color medium"></span>
                        <div>
                            <strong>Moderado</strong>
                            <p>Região com vulnerabilidade média e necessidade de apoio.</p>
                        </div>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color low"></span>
                        <div>
                            <strong>Menor</strong>
                            <p>Região com menor índice de carência relativa.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="info-section">
            <div class="card region-details">
                <div class="card-header">
                    <h2>Detalhes da região</h2>
                    <p>Descubra os principais dados e as ONGs mais relevantes.</p>
                </div>
                <div class="detail-block">
                    <h3 id="regionName">Selecione uma região</h3>
                    <span class="vulnerability-label" id="regionLevel">IBGE: aguarde seleção</span>
                    <p id="regionDescription">O mapa apresenta as áreas mais vulneráveis do Brasil por região macro, com foco em pobreza, desigualdade e acesso a serviços básicos.</p>
                </div>
                <div class="detail-grid">
                    <div>
                        <strong>População estimada</strong>
                        <p id="regionPopulation">—</p>
                    </div>
                    <div>
                        <strong>Carência social</strong>
                        <p id="regionNeed">—</p>
                    </div>
                </div>
                <div class="ong-card">
                    <h3>Principais ONGs</h3>
                    <ul id="regionOngs" class="ong-list">
                        <li>Escolha uma região no mapa.</li>
                    </ul>
                </div>
                <div class="region-selector">
                    <button type="button" data-region="norte">Norte</button>
                    <button type="button" data-region="nordeste">Nordeste</button>
                    <button type="button" data-region="centro-oeste">Centro-Oeste</button>
                    <button type="button" data-region="sudeste">Sudeste</button>
                    <button type="button" data-region="sul">Sul</button>
                </div>
            </div>
        </section>

        <section class="info-section">
            <div class="card">
                <div class="card-header">
                    <h2>ONGs Parceiras Ativas</h2>
                    <p>Instituições verificadas que atuam em regiões vulneráveis.</p>
                </div>
                <div class="ong-list">
                    <?php if(empty($ongs)): ?>
                        <p>Nenhuma ONG ativa no momento.</p>
                    <?php else: ?>
                        <?php foreach($ongs as $ong): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($ong['nome']); ?></strong><br>
                                <?php echo htmlspecialchars($ong['endereco']); ?><br>
                                <?php echo htmlspecialchars($ong['telefone']); ?> | <?php echo htmlspecialchars($ong['email']); ?>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <script src="../assets/js/vulnerabilidade.js"></script>
</body>
</html>
