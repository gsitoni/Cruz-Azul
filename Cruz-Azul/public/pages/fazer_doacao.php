<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

require '../../src/api/database.php';

$userEmail = $_SESSION['usuario']['email'];
$primeiroNome = explode('@', $userEmail)[0];

$ongId = intval($_GET['ong'] ?? 0);

$stmtOng = $pdo->prepare(
    "SELECT id_beneficiario, nome_receptor FROM beneficiario
     WHERE id_beneficiario = ? AND status_elegibilidade = 'ativo'"
);
$stmtOng->execute([$ongId]);
$ongSelecionada = $stmtOng->fetch(PDO::FETCH_ASSOC);

if (!$ongSelecionada) {
    header('Location: ongs.php');
    exit;
}

// Busca doador pelo e-mail do usuário logado
$stmtDo = $pdo->prepare("SELECT id_doador, nome FROM doador WHERE email = ?");
$stmtDo->execute([$userEmail]);
$doador = $stmtDo->fetch(PDO::FETCH_ASSOC);

$erro    = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoria          = trim($_POST['categoria'] ?? '');
    $item               = trim($_POST['item'] ?? '');
    $quantidade         = trim($_POST['quantidade'] ?? '');
    $unidade            = trim($_POST['unidade_medida'] ?? '');
    $data_validade      = trim($_POST['data_validade'] ?? '') ?: null;
    $estado_conservacao = trim($_POST['estado_conservacao'] ?? '') ?: null;

    // Campos do doador (apenas se não tiver cadastro)
    $nomeDoa  = trim($_POST['nome_doador'] ?? '');
    $cpf      = trim($_POST['cpf_cnpj'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');

    $categorias_validas = ['alimento','roupa','brinquedo','higiene','movel','eletronico','outro'];
    $unidades_validas   = ['kg','g','l','ml','unidade','par','pacote','caixa','saco','fardo','lata','garrafa','frasco','tubo','dose'];

    if (!in_array($categoria, $categorias_validas, true)) {
        $erro = 'Categoria inválida.';
    } elseif (empty($item) || strlen($item) > 200) {
        $erro = 'Descrição do item é obrigatória (máx. 200 caracteres).';
    } elseif (!is_numeric($quantidade) || floatval($quantidade) <= 0) {
        $erro = 'Quantidade deve ser um número positivo.';
    } elseif (!in_array($unidade, $unidades_validas, true)) {
        $erro = 'Unidade de medida inválida.';
    } elseif ($categoria === 'alimento' && empty($data_validade)) {
        $erro = 'Data de validade é obrigatória para alimentos.';
    } elseif ($categoria !== 'alimento' && empty($estado_conservacao)) {
        $erro = 'Estado de conservação é obrigatório para itens não-perecíveis.';
    } else {
        // Criar doador se ainda não existir
        if (!$doador) {
            if (empty($nomeDoa) || empty($cpf) || empty($telefone)) {
                $erro = 'Preencha seus dados de doador: nome, CPF/CNPJ e telefone.';
            } else {
                try {
                    $ins = $pdo->prepare(
                        "INSERT INTO doador (cpf_cnpj, nome, telefone, email) VALUES (?, ?, ?, ?)"
                    );
                    $ins->execute([$cpf, $nomeDoa, $telefone, $userEmail]);
                    $doador = ['id_doador' => $pdo->lastInsertId(), 'nome' => $nomeDoa];
                } catch (PDOException $e) {
                    $erro = ($e->errorInfo[1] === 1062)
                        ? 'CPF/CNPJ ou telefone já cadastrado para outro doador.'
                        : 'Erro ao cadastrar dados do doador.';
                }
            }
        }

        if (!$erro && $doador) {
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO doacao
                        (id_doador, id_beneficiario, categoria, item, quantidade, unidade_medida, data_validade, estado_conservacao, data_doacao)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE())"
                );
                $stmt->execute([
                    $doador['id_doador'],
                    $ongId,
                    $categoria,
                    $item,
                    floatval($quantidade),
                    $unidade,
                    $data_validade,
                    $estado_conservacao,
                ]);
                $sucesso = 'Doação registrada com sucesso! Obrigado por ajudar.';
            } catch (PDOException $e) {
                $erro = 'Erro ao registrar doação: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Doação — Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/fazer_doacao.css">
</head>
<body>
<nav>
    <a href="home_usuario.php" class="logo" style="text-decoration:none;color:#fff;">🤝 Cruz Azul</a>
    <div>
        <a href="home_usuario.php">Início</a>
        <a href="doar.php">Voltar</a>
        <a href="logout.php">Sair</a>
    </div>
</nav>

<nav aria-label="breadcrumb" style="background:#e9ecef;border-bottom:1px solid #dee2e6;padding:8px 20px;font-size:13px;">
    <ol style="list-style:none;margin:0 auto;padding:0;display:flex;flex-wrap:wrap;align-items:center;max-width:900px;">
        <li><a href="home_usuario.php" style="color:#007BFF;text-decoration:none;">Início</a></li>
        <li><span style="margin:0 6px;color:#aaa;">›</span></li>
        <li><a href="doar.php" style="color:#007BFF;text-decoration:none;">Fazer Doação</a></li>
        <li><span style="margin:0 6px;color:#aaa;">›</span></li>
        <li style="color:#555;">Registrar Doação</li>
    </ol>
</nav>

<div class="container">
    <div class="header">
        <h1>Registrar Doação</h1>
        <p>Olá, <?php echo htmlspecialchars($primeiroNome); ?>! 👋 Você está doando para <strong><?php echo htmlspecialchars($ongSelecionada['nome_receptor']); ?></strong>.</p>
    </div>
    <div class="box">
        <?php if ($sucesso): ?>
            <div class="alert" style="background:#e6f9ef;color:#1a7a3f;"><?php echo htmlspecialchars($sucesso); ?></div>
        <?php elseif ($erro): ?>
            <div class="alert" style="background:#fdecea;color:#b71c1c;"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <form method="post" action="fazer_doacao.php?ong=<?php echo $ongId; ?>">

            <?php if (!$doador): ?>
            <p style="margin-bottom:16px;font-size:14px;color:#555;">Primeiro acesso como doador. Preencha seus dados:</p>
            <div class="form-row">
                <label for="nome_doador">Seu nome completo</label>
                <input type="text" id="nome_doador" name="nome_doador" maxlength="200"
                       value="<?php echo htmlspecialchars($_POST['nome_doador'] ?? ''); ?>" required>
            </div>
            <div class="form-row">
                <label for="cpf_cnpj">CPF ou CNPJ</label>
                <input type="text" id="cpf_cnpj" name="cpf_cnpj" maxlength="18"
                       placeholder="000.000.000-00"
                       value="<?php echo htmlspecialchars($_POST['cpf_cnpj'] ?? ''); ?>" required>
            </div>
            <div class="form-row">
                <label for="telefone">Telefone</label>
                <input type="text" id="telefone" name="telefone" maxlength="20"
                       placeholder="(11) 99999-9999"
                       value="<?php echo htmlspecialchars($_POST['telefone'] ?? ''); ?>" required>
            </div>
            <hr style="margin:20px 0;border-color:#eee;">
            <?php else: ?>
            <p style="font-size:14px;color:#555;margin-bottom:16px;">
                Doando como: <strong><?php echo htmlspecialchars($doador['nome']); ?></strong>
            </p>
            <?php endif; ?>

            <div class="form-row">
                <label for="categoria">Categoria</label>
                <select id="categoria" name="categoria" required onchange="alternarCampos(this.value)">
                    <option value="">Selecione</option>
                    <option value="alimento"   <?php echo (($_POST['categoria'] ?? '') === 'alimento')    ? 'selected' : ''; ?>>Alimento</option>
                    <option value="roupa"      <?php echo (($_POST['categoria'] ?? '') === 'roupa')       ? 'selected' : ''; ?>>Roupa</option>
                    <option value="higiene"    <?php echo (($_POST['categoria'] ?? '') === 'higiene')     ? 'selected' : ''; ?>>Higiene</option>
                    <option value="brinquedo"  <?php echo (($_POST['categoria'] ?? '') === 'brinquedo')   ? 'selected' : ''; ?>>Brinquedo</option>
                    <option value="movel"      <?php echo (($_POST['categoria'] ?? '') === 'movel')       ? 'selected' : ''; ?>>Móvel</option>
                    <option value="eletronico" <?php echo (($_POST['categoria'] ?? '') === 'eletronico')  ? 'selected' : ''; ?>>Eletrônico</option>
                    <option value="outro"      <?php echo (($_POST['categoria'] ?? '') === 'outro')       ? 'selected' : ''; ?>>Outro</option>
                </select>
            </div>

            <div class="form-row">
                <label for="item">Descrição do item</label>
                <input type="text" id="item" name="item" maxlength="200"
                       placeholder="Ex: Arroz tipo 1, Agasalho infantil, Kit sabonete"
                       value="<?php echo htmlspecialchars($_POST['item'] ?? ''); ?>" required>
            </div>

            <div class="form-row" style="display:flex;gap:12px;">
                <div style="flex:1;">
                    <label for="quantidade">Quantidade</label>
                    <input type="number" id="quantidade" name="quantidade" min="0.001" step="0.001"
                           placeholder="Ex: 10"
                           value="<?php echo htmlspecialchars($_POST['quantidade'] ?? ''); ?>" required>
                </div>
                <div style="flex:1;">
                    <label for="unidade_medida">Unidade</label>
                    <select id="unidade_medida" name="unidade_medida" required>
                        <option value="">Selecione</option>
                        <optgroup label="Peso">
                            <option value="kg"  <?= (($_POST['unidade_medida'] ?? '') === 'kg')       ? 'selected' : '' ?>>Quilograma (kg)</option>
                            <option value="g"   <?= (($_POST['unidade_medida'] ?? '') === 'g')        ? 'selected' : '' ?>>Grama (g)</option>
                        </optgroup>
                        <optgroup label="Volume">
                            <option value="l"   <?= (($_POST['unidade_medida'] ?? '') === 'l')        ? 'selected' : '' ?>>Litro (l)</option>
                            <option value="ml"  <?= (($_POST['unidade_medida'] ?? '') === 'ml')       ? 'selected' : '' ?>>Mililitro (ml)</option>
                        </optgroup>
                        <optgroup label="Contagem">
                            <option value="unidade" <?= (($_POST['unidade_medida'] ?? '') === 'unidade') ? 'selected' : '' ?>>Unidade</option>
                            <option value="par"     <?= (($_POST['unidade_medida'] ?? '') === 'par')     ? 'selected' : '' ?>>Par</option>
                            <option value="pacote"  <?= (($_POST['unidade_medida'] ?? '') === 'pacote')  ? 'selected' : '' ?>>Pacote</option>
                            <option value="caixa"   <?= (($_POST['unidade_medida'] ?? '') === 'caixa')   ? 'selected' : '' ?>>Caixa</option>
                            <option value="saco"    <?= (($_POST['unidade_medida'] ?? '') === 'saco')    ? 'selected' : '' ?>>Saco</option>
                            <option value="fardo"   <?= (($_POST['unidade_medida'] ?? '') === 'fardo')   ? 'selected' : '' ?>>Fardo</option>
                            <option value="lata"    <?= (($_POST['unidade_medida'] ?? '') === 'lata')    ? 'selected' : '' ?>>Lata</option>
                            <option value="garrafa" <?= (($_POST['unidade_medida'] ?? '') === 'garrafa') ? 'selected' : '' ?>>Garrafa</option>
                            <option value="frasco"  <?= (($_POST['unidade_medida'] ?? '') === 'frasco')  ? 'selected' : '' ?>>Frasco</option>
                            <option value="tubo"    <?= (($_POST['unidade_medida'] ?? '') === 'tubo')    ? 'selected' : '' ?>>Tubo</option>
                            <option value="dose"    <?= (($_POST['unidade_medida'] ?? '') === 'dose')    ? 'selected' : '' ?>>Dose</option>
                        </optgroup>
                    </select>
                </div>
            </div>

            <div class="form-row" id="campo-validade" style="display:none;">
                <label for="data_validade">Data de validade</label>
                <input type="date" id="data_validade" name="data_validade"
                       min="<?php echo date('Y-m-d'); ?>"
                       value="<?php echo htmlspecialchars($_POST['data_validade'] ?? ''); ?>">
            </div>

            <div class="form-row" id="campo-conservacao" style="display:none;">
                <label for="estado_conservacao">Estado de conservação</label>
                <select id="estado_conservacao" name="estado_conservacao">
                    <option value="">Selecione</option>
                    <option value="novo"      <?php echo (($_POST['estado_conservacao'] ?? '') === 'novo')      ? 'selected' : ''; ?>>Novo</option>
                    <option value="usado"     <?php echo (($_POST['estado_conservacao'] ?? '') === 'usado')     ? 'selected' : ''; ?>>Usado</option>
                    <option value="desgastado"<?php echo (($_POST['estado_conservacao'] ?? '') === 'desgastado')? 'selected' : ''; ?>>Desgastado</option>
                </select>
            </div>

            <button type="submit">Registrar Doação</button>
        </form>
    </div>

    <script>
    function alternarCampos(categoria) {
        const validade    = document.getElementById('campo-validade');
        const conservacao = document.getElementById('campo-conservacao');
        const inputVal    = document.getElementById('data_validade');
        const selectCon   = document.getElementById('estado_conservacao');

        if (categoria === 'alimento') {
            validade.style.display    = '';
            conservacao.style.display = 'none';
            inputVal.required  = true;
            selectCon.required = false;
        } else if (categoria !== '') {
            validade.style.display    = 'none';
            conservacao.style.display = '';
            inputVal.required  = false;
            selectCon.required = true;
        } else {
            validade.style.display    = 'none';
            conservacao.style.display = 'none';
            inputVal.required  = false;
            selectCon.required = false;
        }
    }
    // Restaura os campos ao recarregar após erro de validação server-side
    alternarCampos(document.getElementById('categoria').value);
    </script>
</div>
</body>
</html>