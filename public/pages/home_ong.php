<?php
session_start();
if (!isset($_SESSION['ong'])) {
    header('Location: login_ong.php');
    exit;
}

require '../../src/api/database.php';

function formatarNumero($valor)
{
    $numero = (float) $valor;

    if ((float) ((int) $numero) === $numero) {
        return number_format($numero, 0, ',', '.');
    }

    return number_format($numero, 1, ',', '.');
}

function formatarDataHora(?string $valor, string $formato = 'd/m/Y')
{
    if (empty($valor)) {
        return 'Sem registros';
    }

    return date($formato, strtotime($valor));
}

$ongId = (int) ($_SESSION['ong']['id'] ?? 0);

$ong = null;
$estatisticas = [
    'total_doacoes' => 0,
    'total_lotes' => 0,
    'doadores_unicos' => 0,
    'ultima_entrada' => null,
];
$ultimasDoacoes = [];
$categoriasResumo = [];
$mapaCategorias = [
    'alimento' => ['titulo' => '🍚 Alimentos', 'descricao' => 'Itens alimentares já encaminhados para a ONG.'],
    'higiene' => ['titulo' => '🧴 Higiene', 'descricao' => 'Kits e produtos de higiene recebidos.'],
    'roupa' => ['titulo' => '👕 Roupas', 'descricao' => 'Peças de vestuário distribuídas para a ONG.'],
    'brinquedo' => ['titulo' => '🧸 Brinquedos', 'descricao' => 'Itens infantis recebidos nos repasses.'],
    'movel' => ['titulo' => '🪑 Móveis', 'descricao' => 'Móveis e utilidades maiores encaminhados.'],
    'eletronico' => ['titulo' => '💻 Eletrônicos', 'descricao' => 'Equipamentos eletrônicos recebidos.'],
    'outro' => ['titulo' => '📦 Outros itens', 'descricao' => 'Doações categorizadas como diversos.'],
];

if ($ongId > 0) {
    $stmt = $pdo->prepare('
        SELECT id_ong, nome, email, area_atuacao, status_elegibilidade, cidade, sigla_estado,
               localizacao, classificacao_risco, endereco, descricao, data_atualizacao
        FROM ong
        WHERE id_ong = ?
    ');
    $stmt->execute([$ongId]);
    $ong = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($ong) {
        $_SESSION['ong']['nome'] = $ong['nome'];
        $_SESSION['ong']['email'] = $ong['email'];
        $_SESSION['ong']['area_atuacao'] = $ong['area_atuacao'];
        $_SESSION['ong']['status'] = $ong['status_elegibilidade'];
        $_SESSION['ong']['cidade'] = $ong['cidade'];
        $_SESSION['ong']['estado'] = $ong['sigla_estado'];
    }

    $stmt = $pdo->prepare('
        SELECT
            COUNT(*) AS total_doacoes,
            COUNT(DISTINCT di.id_lote) AS total_lotes,
            COUNT(DISTINCT d.id_doador) AS doadores_unicos,
            MAX(di.data_hora) AS ultima_entrada
        FROM distribuicao di
        INNER JOIN estoque e ON e.id_lote = di.id_lote
        INNER JOIN doacao d ON d.id_doacao = e.id_doacao
        WHERE di.id_ong = ?
    ');
    $stmt->execute([$ongId]);
    $estatisticas = $stmt->fetch(PDO::FETCH_ASSOC) ?: $estatisticas;

    $stmt = $pdo->prepare('
        SELECT
            d.item,
            di.quantidade_retirada,
            d.unidade_medida,
            doador.nome AS remetente,
            di.data_hora
        FROM distribuicao di
        INNER JOIN estoque e ON e.id_lote = di.id_lote
        INNER JOIN doacao d ON d.id_doacao = e.id_doacao
        LEFT JOIN doador ON doador.id_doador = d.id_doador
        WHERE di.id_ong = ?
        ORDER BY di.data_hora DESC
        LIMIT 5
    ');
    $stmt->execute([$ongId]);
    $ultimasDoacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare('
        SELECT d.categoria, SUM(di.quantidade_retirada) AS total_recebido,
               COUNT(*) AS total_registros, MAX(di.data_hora) AS ultima_data
        FROM distribuicao di
        INNER JOIN estoque e ON e.id_lote = di.id_lote
        INNER JOIN doacao d ON d.id_doacao = e.id_doacao
        WHERE di.id_ong = ?
        GROUP BY d.categoria
    ');
    $stmt->execute([$ongId]);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $categoria) {
        $chave = $categoria['categoria'];
        $config = $mapaCategorias[$chave] ?? ['titulo' => ucfirst((string) $chave), 'descricao' => 'Itens registrados nesta categoria.'];
        $categoriasResumo[] = [
            'titulo' => $config['titulo'],
            'descricao' => $config['descricao'],
            'total_recebido' => (float) $categoria['total_recebido'],
            'total_registros' => (int) $categoria['total_registros'],
            'ultima_data' => $categoria['ultima_data'],
        ];
    }
}
// Variáveis para exibição
$nome = $ong['nome'] ?? ($_SESSION['ong']['nome'] ?? $_SESSION['ong']['email'] ?? 'ONG');
$area = $ong['area_atuacao'] ?? ($_SESSION['ong']['area_atuacao'] ?? 'Assistência social');
$status = $ong['status_elegibilidade'] ?? ($_SESSION['ong']['status'] ?? 'pendente');
$cidade = $ong['cidade'] ?? ($_SESSION['ong']['cidade'] ?? '');
$estado = $ong['sigla_estado'] ?? ($_SESSION['ong']['estado'] ?? '');
$localizacao = trim(implode(' / ', array_filter([$cidade, $estado])));
$ultimaEntrada = !empty($estatisticas['ultima_entrada'])
    ? date('d/m/Y', strtotime($estatisticas['ultima_entrada']))
    : 'Sem registros';
$statusLabel = [
    'pendente' => 'Aguardando aprovação',
    'aprovada' => 'Cadastro aprovado',
    'rejeitada' => 'Cadastro rejeitado',
][$status] ?? ucfirst((string) $status);
$classificacaoRisco = $ong['classificacao_risco'] ?? null;
$classificacaoLabel = [
    'emergencia' => 'Emergência',
    'continuo' => 'Atendimento contínuo',
    'pontual' => 'Demanda pontual',
    'baixa_prioridade' => 'Baixa prioridade',
][$classificacaoRisco] ?? 'Não informado';
$enderecoCompleto = trim(implode(' - ', array_filter([
    $ong['endereco'] ?? '',
    $ong['localizacao'] ?? '',
])));
$ultimaAtualizacaoCadastral = !empty($ong['data_atualizacao'])
    ? date('d/m/Y H:i', strtotime($ong['data_atualizacao']))
    : 'Sem atualização';
$totalSuprimentosRecebidos = 0.0;
$maiorCategoriaRecebida = 0.0;

foreach ($categoriasResumo as $categoriaResumo) {
    $totalSuprimentosRecebidos += (float) $categoriaResumo['total_recebido'];
    $maiorCategoriaRecebida = max($maiorCategoriaRecebida, (float) $categoriaResumo['total_recebido']);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel ONG — Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/home_ong.css">
</head>
<body>

<nav>
    <a href="home_ong.php" class="logo" style="text-decoration:none;color:#fff;">🤝 Cruz Azul</a>
    <div>
        <a href="doacoes_recebidas.php">Doações</a>
        <a href="perfil_ong.php">Perfil</a>
        <a href="logout.php">Sair</a>
    </div>
</nav>

<?php if ($status === 'pendente'): ?>
<div class="aviso">
    ⏳ Sua ONG está <strong>aguardando validação</strong> do administrador.
</div>
<?php endif; ?>

<div class="topo">
    <h1><?= htmlspecialchars($nome) ?></h1>
    <div class="area-tag">📌 <?= htmlspecialchars($area) ?></div>
    <p>Gerencie as doações recebidas e atualize as necessidades da sua ONG.</p>
</div>

<div class="barra-stats">
    <div class="stat">
        <div class="numero"><?= (int) ($estatisticas['total_doacoes'] ?? 0) ?></div>
        <div class="label">Doações recebidas</div>
    </div>
    <div class="stat">
        <div class="numero"><?= (int) ($estatisticas['total_lotes'] ?? 0) ?></div>
        <div class="label">Lotes recebidos</div>
    </div>
    <div class="stat">
        <div class="numero"><?= (int) ($estatisticas['doadores_unicos'] ?? 0) ?></div>
        <div class="label">Doadores únicos</div>
    </div>
    <div class="stat">
        <div class="numero"><?= htmlspecialchars($ultimaEntrada) ?></div>
        <div class="label">Última entrada</div>
    </div>
</div>

<div class="conteudo">

    <div class="titulo-secao">O que você quer fazer?</div>
    <div class="grid-acoes">
        <a href="doacoes_recebidas.php" class="card-acao">
            <div class="icone">📦</div>
            <h3>Ver doações</h3>
            <p>Confirme o recebimento das doações.</p>
        </a>
        <a href="relatorios.php" class="card-acao">
            <div class="icone">📊</div>
            <h3>Relatórios</h3>
            <p>Veja o histórico de impacto.</p>
        </a>
        <a href="perfil_ong.php" class="card-acao">
            <div class="icone">✏️</div>
            <h3>Editar perfil</h3>
            <p>Atualize os dados da ONG.</p>
        </a>
    </div>

    <div class="titulo-secao">Últimas doações recebidas</div>
    <div class="lista-doacoes">
        <?php if (empty($ultimasDoacoes)): ?>
            <div class="doacao-item">
                <div class="doacao-info">
                    <h4>Nenhuma doação recebida ainda</h4>
                    <span>As entradas registradas para esta ONG aparecerão aqui.</span>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($ultimasDoacoes as $doacao): ?>
                <div class="doacao-item">
                    <div class="doacao-info">
                        <h4><?= htmlspecialchars((string) $doacao['item']) ?> — <?= htmlspecialchars((string) ($doacao['remetente'] ?: 'Doador não identificado')) ?></h4>
                        <span><?= htmlspecialchars(formatarNumero($doacao['quantidade_retirada'])) ?> <?= htmlspecialchars((string) $doacao['unidade_medida']) ?> · Recebido em <?= htmlspecialchars(formatarDataHora($doacao['data_hora'])) ?></span>
                    </div>
                    <div class="doacao-meta">
                        <?= htmlspecialchars(formatarDataHora($doacao['data_hora'], 'd/m/Y H:i')) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="titulo-secao">Resumo por categoria</div>
    <div class="grid-nec">
        <?php if (empty($categoriasResumo)): ?>
            <div class="card-nec">
                <h4>Sem categorias registradas</h4>
                <div class="qtd">Quando a ONG receber doações distribuídas, o resumo aparecerá aqui.</div>
            </div>
        <?php else: ?>
            <?php foreach ($categoriasResumo as $categoria): ?>
                <?php
                    $percentual = $maiorCategoriaRecebida > 0
                        ? max(8, min(100, (int) round(($categoria['total_recebido'] / $maiorCategoriaRecebida) * 100)))
                        : 0;
                    $classeBarra = $percentual >= 75 ? 'ok' : ($percentual <= 30 ? 'urgente' : '');
                ?>
                <div class="card-nec">
                    <h4><?= htmlspecialchars($categoria['titulo']) ?></h4>
                    <div class="qtd"><?= htmlspecialchars(formatarNumero($categoria['total_recebido'])) ?> itens recebidos em <?= (int) $categoria['total_registros'] ?> registros</div>
                    <div class="qtd"><?= htmlspecialchars($categoria['descricao']) ?></div>
                    <div class="barra-fundo">
                        <div class="barra-fill<?= $classeBarra !== '' ? ' ' . $classeBarra : '' ?>" style="width: <?= $percentual ?>%"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<footer>
    &copy; <?= date('Y') ?> Cruz Azul — Sistema de Suprimentos
</footer>

</body>
</html>
