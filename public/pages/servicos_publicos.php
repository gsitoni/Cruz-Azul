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
            'endereco' => 'Rua dos Pioneiros, 1234 - Centro',
            'cidade' => 'Curitiba',
            'estado' => 'PR',
            'telefone' => '(41) 3361-3000',
            'tipo' => 'Emergência 24h',
            'distancia' => '2.3 km'
        ],
        [
            'nome' => 'Hospital das Clínicas (FMUSP)',
            'endereco' => 'Av. Dr. Enéas Carvalho de Aguiar, 255 - Cerqueira César',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
            'telefone' => '(11) 3069-6000',
            'tipo' => 'Referência em Saúde',
            'distancia' => '6.7 km'
        ],
        [
            'nome' => 'Hospital da Mulher Professor Moraes Rego',
            'endereco' => 'Av. Vasco da Gama, 1.000 - Barra',
            'cidade' => 'Salvador',
            'estado' => 'BA',
            'telefone' => '(71) 3116-8100',
            'tipo' => 'Especializado em Saúde da Mulher',
            'distancia' => '4.1 km'
        ]
    ],
    'postos_saude' => [
        [
            'nome' => 'UBS Centro',
            'endereco' => 'Praça Tiradentes, 45 - Centro',
            'cidade' => 'Curitiba',
            'estado' => 'PR',
            'telefone' => '(41) 3321-2346',
            'tipo' => 'Unidade Básica',
            'horario' => 'Seg-Sex: 7h-17h',
            'distancia' => '0.8 km'
        ],
        [
            'nome' => 'UBS Jardim das Flores',
            'endereco' => 'Rua das Rosas, 234 - Jardim das Flores',
            'cidade' => 'Belo Horizonte',
            'estado' => 'MG',
            'telefone' => '(31) 3277-1700',
            'tipo' => 'Unidade Básica',
            'horario' => 'Seg-Sex: 7h-17h',
            'distancia' => '2.2 km'
        ],
        [
            'nome' => 'CAPS AD III Fortaleza',
            'endereco' => 'Av. República, 678 - Centro',
            'cidade' => 'Fortaleza',
            'estado' => 'CE',
            'telefone' => '(85) 3451-5100',
            'tipo' => 'Saúde Mental',
            'horario' => 'Seg-Sex: 8h-18h',
            'distancia' => '1.5 km'
        ]
    ],
    'alimentacao' => [
        [
            'nome' => 'Banco de Alimentos do Paraná',
            'endereco' => 'Rua da Cidadania, 1000 - Cidade Industrial',
            'cidade' => 'Curitiba',
            'estado' => 'PR',
            'telefone' => '(41) 3350-8200',
            'tipo' => 'Distribuição Gratuita',
            'horario' => 'Seg-Sex: 8h-16h',
            'distancia' => '5.2 km'
        ],
        [
            'nome' => 'Cozinha Comunitária São Francisco',
            'endereco' => 'Rua São Francisco, 456 - São Francisco',
            'cidade' => 'Belo Horizonte',
            'estado' => 'MG',
            'telefone' => '(31) 3409-2100',
            'tipo' => 'Refeições Quentes',
            'horario' => 'Seg-Sex: 11h-14h',
            'distancia' => '2.8 km'
        ],
        [
            'nome' => 'Restaurante Popular Manaus',
            'endereco' => 'Avenida Brasil, 1500 - Centro',
            'cidade' => 'Manaus',
            'estado' => 'AM',
            'telefone' => '(92) 3234-5000',
            'tipo' => 'Refeições a R$ 1,00',
            'horario' => 'Seg-Sex: 11h-15h',
            'distancia' => '1.2 km'
        ]
    ],
    'abrigos' => [
        [
            'nome' => 'Casa de Passagem Vida Nova',
            'endereco' => 'Rua Itupava, 2100 - Alto da Rua XV',
            'cidade' => 'Curitiba',
            'estado' => 'PR',
            'telefone' => '(41) 3250-8080',
            'tipo' => 'Acolhimento Social',
            'capacidade' => '40 pessoas',
            'distancia' => '4.2 km'
        ],
        [
            'nome' => 'Centro POP Curitiba',
            'endereco' => 'Rua Visconde do Rio Branco, 1125 - Centro',
            'cidade' => 'Curitiba',
            'estado' => 'PR',
            'telefone' => '(41) 3250-1090',
            'tipo' => 'Acolhimento Social',
            'capacidade' => '30 pessoas',
            'distancia' => '2.1 km'
        ],
        [
            'nome' => 'Casa de Passagem São José',
            'endereco' => 'Av. Marechal Floriano, 321 - Centro',
            'cidade' => 'Porto Alegre',
            'estado' => 'RS',
            'telefone' => '(51) 3028-2800',
            'tipo' => 'Acolhimento',
            'capacidade' => '30 pessoas',
            'distancia' => '2.1 km'
        ]
    ],
    'assistencia_social' => [
        [
            'nome' => 'CRAS Centro Recife',
            'endereco' => 'Rua Visconde do Rio Branco, 543 - Centro',
            'cidade' => 'Recife',
            'estado' => 'PE',
            'telefone' => '(81) 3237-9230',
            'tipo' => 'Centro de Referência',
            'servicos' => 'Cadastro Único, Bolsa Família',
            'distancia' => '1.0 km'
        ],
        [
            'nome' => 'CREAS Leste Fortaleza',
            'endereco' => 'Av. Paraná, 876 - Centro',
            'cidade' => 'Fortaleza',
            'estado' => 'CE',
            'telefone' => '(85) 3454-6250',
            'tipo' => 'Centro Especializado',
            'servicos' => 'Proteção Social Especial',
            'distancia' => '2.4 km'
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
    'postos_saude' => '⚕️ Postos de Saúde',
    'alimentacao' => '🍽️ Serviços de Alimentação',
    'abrigos' => '🏠 Abrigos e Acolhimento',
    'assistencia_social' => '🤝 Assistência Social',
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
    <span class="logo">🤝 Cruz Azul</span>
    <div>
        <a href="home_usuario.php">Início</a>
        <a href="doar.php">Fazer doação</a>
        <a href="minhas_doacoes.php">Minhas doações</a>
        <a href="ongs.php">ONGs</a>
        <a href="logout.php">Sair</a>
    </div>
</nav>

<div class="conteudo">
    <a href="home_usuario.php" class="btn-voltar">← Voltar ao início</a>

    <h1 class="titulo"><?php echo $titulos[$categoria] ?? $titulos['todos']; ?></h1>
    <p class="subtitulo">Serviços públicos essenciais em várias cidades e estados do Brasil para ajudar quem precisa.</p>

    <div class="filtros">
        <h3>📋 Filtrar por categoria</h3>
        <div class="categorias">
            <button class="categoria-btn <?php echo $categoria === 'todos' ? 'active' : ''; ?>" onclick="filtrarCategoria('todos')">Todos</button>
            <button class="categoria-btn <?php echo $categoria === 'hospitais' ? 'active' : ''; ?>" onclick="filtrarCategoria('hospitais')">🏥 Hospitais</button>
            <button class="categoria-btn <?php echo $categoria === 'postos_saude' ? 'active' : ''; ?>" onclick="filtrarCategoria('postos_saude')">⚕️ Saúde</button>
            <button class="categoria-btn <?php echo $categoria === 'alimentacao' ? 'active' : ''; ?>" onclick="filtrarCategoria('alimentacao')">🍽️ Alimentação</button>
            <button class="categoria-btn <?php echo $categoria === 'abrigos' ? 'active' : ''; ?>" onclick="filtrarCategoria('abrigos')">🏠 Abrigos</button>
            <button class="categoria-btn <?php echo $categoria === 'assistencia_social' ? 'active' : ''; ?>" onclick="filtrarCategoria('assistencia_social')">🤝 Assistência Social</button>
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