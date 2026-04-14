<?php
session_start();

// Verifica se usuário está logado
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$nome = $_SESSION['usuario']['nome'] ?? $_SESSION['usuario']['email'] ?? 'Usuário';
$categoria = $_POST['categoria'] ?? $_GET['categoria'] ?? 'todos';

// Dados dos serviços por categoria
$servicos = [
    'hospitais' => [
        [
            'nome' => 'Hospital do Trabalhador',
            'endereco' => 'Rua Getúlio Vargas, 1.300 - Centro',
            'cidade' => 'Curitiba',
            'estado' => 'PR',
            'telefone' => '(41) 3361-3000',
            'tipo' => 'Emergência 24h - SUS',
            'distancia' => '1.8 km'
        ],
        [
            'nome' => 'Hospital de Clínicas da UFPR',
            'endereco' => 'Rua General Carneiro, 181 - Alto da Glória',
            'cidade' => 'Curitiba',
            'estado' => 'PR',
            'telefone' => '(41) 3360-1800',
            'tipo' => 'Hospital Universitário - 24h',
            'distancia' => '2.5 km'
        ],
        [
            'nome' => 'Hospital Nossa Senhora das Graças',
            'endereco' => 'Rua Alcides Munhoz, 433 - Mercês',
            'cidade' => 'Curitiba',
            'estado' => 'PR',
            'telefone' => '(41) 3212-6000',
            'tipo' => 'Atendimento Emergencial 24h',
            'distancia' => '3.2 km'
        ],
        [
            'nome' => 'Hospital Cônego Mariano Roma',
            'endereco' => 'Rua Uruguai, 155 - Centro',
            'cidade' => 'Curitiba',
            'estado' => 'PR',
            'telefone' => '(41) 3361-2800',
            'tipo' => 'Emergência e Maternidade',
            'distancia' => '1.5 km'
        ],
        [
            'nome' => 'Hospital Erasto Gaertner',
            'endereco' => 'Rua Dr. Ovande do Amaral, 201 - Jardim das Américas',
            'cidade' => 'Curitiba',
            'estado' => 'PR',
            'telefone' => '(41) 3361-5000',
            'tipo' => 'Oncologia e Referência',
            'distancia' => '4.8 km'
        ]
    ],
    
    'emergencia' => [
        [
            'nome' => 'SAMU',
            'telefone' => '192',
            'tipo' => 'Emergência Médica',
            'descricao' => 'Atendimento de urgência e emergência 24h'
        ],
        [
            'nome' => 'Polícia Militar',
            'telefone' => '190',
            'tipo' => 'Segurança Pública',
            'descricao' => 'Emergências policiais e segurança'
        ],
        [
            'nome' => 'Corpo de Bombeiros',
            'telefone' => '193',
            'tipo' => 'Defesa Civil',
            'descricao' => 'Incêndios, resgates e desastres'
        ],
        [
            'nome' => 'Defesa Civil',
            'telefone' => '199',
            'tipo' => 'Proteção Civil',
            'descricao' => 'Alagamentos, deslizamentos e desastres naturais'
        ]
    ]
];

// Títulos das categorias
$titulos = [
    'hospitais' => '🏥 Hospitais Próximos',
    'emergencia' => '🚨 Serviços de Emergência',
    'todos' => '🏥 Todos os Serviços Públicos'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Serviços Públicos — Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/servicos_publicos.css">
</head>
<body>

<nav>
    <a href="home_usuario.php" class="logo" style="text-decoration:none;color:#fff;">🤝 Cruz Azul</a>
    <div>
        <a href="home_usuario.php">Início</a>
        <a href="doar.php">Fazer doação</a>
        <a href="minhas_doacoes.php">Minhas doações</a>
        <a href="ongs.php">ONGs</a>
        <a href="logout.php">Sair</a>
    </div>
</nav>

<nav aria-label="breadcrumb" style="background:#e9ecef;border-bottom:1px solid #dee2e6;padding:8px 20px;font-size:13px;">
    <ol style="list-style:none;margin:0 auto;padding:0;display:flex;flex-wrap:wrap;align-items:center;max-width:900px;">
        <li><a href="home_usuario.php" style="color:#007BFF;text-decoration:none;">Início</a></li>
        <li><span style="margin:0 6px;color:#aaa;">›</span></li>
        <li style="color:#555;">Serviços Públicos</li>
    </ol>
</nav>

<div class="conteudo">
    <a href="home_usuario.php" class="btn-voltar">← Voltar ao início</a>

    <h1 class="titulo"><?php echo $titulos[$categoria] ?? $titulos['todos']; ?></h1>
    <p class="subtitulo">Encontre hospitais e contatos de emergência com informações objetivas para atendimento rápido quando mais precisar.</p>

    <div class="filtros">
        <h3>📋 Filtrar por categoria</h3>
        <div class="categorias">
            <button class="categoria-btn <?php echo $categoria === 'todos' ? 'active' : ''; ?>" onclick="filtrarCategoria('todos')">Todos</button>
            <button class="categoria-btn <?php echo $categoria === 'hospitais' ? 'active' : ''; ?>" onclick="filtrarCategoria('hospitais')">🏥 Hospitais</button>
            <button class="categoria-btn <?php echo $categoria === 'emergencia' ? 'active' : ''; ?>" onclick="filtrarCategoria('emergencia')">🚨 Emergências</button>
        </div>
    </div>

    <div class="servicos-grid" id="servicos-container">
        <?php
        $servicosParaMostrar = $categoria === 'todos' ? $servicos : [$categoria => $servicos[$categoria] ?? []];

        foreach ($servicosParaMostrar as $cat => $listaServicos) {
            foreach ($listaServicos as $servico) {
                $isEmergencia = $cat === 'emergencia';
                $cardClass = $isEmergencia ? 'servico-card emergencia-card' : 'servico-card';
                ?>
                <div class="<?php echo $cardClass; ?>" data-categoria="<?php echo $cat; ?>">
                    <h3><?php echo htmlspecialchars($servico['nome']); ?></h3>

                    <?php if (isset($servico['endereco'])): ?>
                        <div class="servico-info">
                            <strong>📍 Endereço:</strong> <?php echo htmlspecialchars($servico['endereco']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="servico-info">
                        <strong>📌 Local:</strong> <?= htmlspecialchars(($servico['cidade'] ?? 'Curitiba') . ', ' . ($servico['estado'] ?? 'PR')); ?>
                    </div>

                    <?php if (isset($servico['tipo'])): ?>
                        <div class="servico-info">
                            <strong>🏷️ Tipo:</strong> <?php echo htmlspecialchars($servico['tipo']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($servico['horario'])): ?>
                        <div class="servico-info">
                            <strong>🕐 Horário:</strong> <?php echo htmlspecialchars($servico['horario']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($servico['capacidade'])): ?>
                        <div class="servico-info">
                            <strong>👥 Capacidade:</strong> <?php echo htmlspecialchars($servico['capacidade']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($servico['servicos'])): ?>
                        <div class="servico-info">
                            <strong>📋 Serviços:</strong> <?php echo htmlspecialchars($servico['servicos']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($servico['descricao'])): ?>
                        <div class="servico-info">
                            <strong>ℹ️ Descrição:</strong> <?php echo htmlspecialchars($servico['descricao']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="telefone">
                        📞 <?php echo htmlspecialchars($servico['telefone']); ?>
                    </div>

                    <?php if (isset($servico['distancia'])): ?>
                        <div class="distancia">
                            📏 <?php echo htmlspecialchars($servico['distancia']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php
            }
        }
        ?>
    </div>
</div>

<footer>
    &copy; <?= date('Y') ?> Cruz Azul — Sistema de Suprimentos e Serviços Públicos
</footer>

<script>
function filtrarCategoria(categoria) {
    // Atualizar URL sem recarregar a página
    const url = new URL(window.location);
    url.searchParams.set('categoria', categoria);
    window.location.href = url.toString();
}

document.addEventListener('DOMContentLoaded', function() {
    // Adicionar funcionalidade de filtro via JavaScript (fallback)
    const botoes = document.querySelectorAll('.categoria-btn');
    botoes.forEach(botao => {
        botao.addEventListener('click', function() {
            // Remover classe active de todos
            botoes.forEach(b => b.classList.remove('active'));
            // Adicionar classe active ao clicado
            this.classList.add('active');
        });
    });
});
</script>

</body>
</html>