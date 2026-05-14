<?php
// ============================================================
//  meus_dados.php  –  public/pages/meus_dados.php
//  Página de transparência de dados (LGPD Art. 18)
// ============================================================
session_start();
 
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
 
if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['id_usuario'])) {
    header('Location: login.php');
    exit;
}
 
require '../../src/api/database.php';
 
$id_usuario = (int) $_SESSION['usuario']['id_usuario'];
 
function e(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
 
function formatarCPF(?string $cpf): string {
    if (!$cpf) return '—';
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) === 11)
        return substr($cpf,0,3).'.'.substr($cpf,3,3).'.'.substr($cpf,6,3).'-'.substr($cpf,9,2);
    return $cpf;
}
 
function formatarTelefone(?string $tel): string {
    if (!$tel) return '—';
    $tel = preg_replace('/[^0-9]/', '', $tel);
    if (strlen($tel) === 11) return '('.substr($tel,0,2).') '.substr($tel,2,5).'-'.substr($tel,7);
    if (strlen($tel) === 10) return '('.substr($tel,0,2).') '.substr($tel,2,4).'-'.substr($tel,6);
    return $tel;
}
 
function formatarData(?string $data): string {
    if (!$data) return '—';
    $dt = DateTime::createFromFormat('Y-m-d', $data);
    return $dt ? $dt->format('d/m/Y') : $data;
}
 
// ── Busca dados do usuário ───────────────────────────────────
try {
    $stmtUser = $pdo->prepare(
        "SELECT email, status_cadastro, tipo FROM usuario WHERE id_usuario = ? LIMIT 1"
    );
    $stmtUser->execute([$id_usuario]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
 
    if (!$user) { session_destroy(); header('Location: login.php'); exit; }
 
    // Doador
    $stmtDoador = $pdo->prepare(
        "SELECT nome, cpf, telefone, data_nascimento, criado_em FROM doador WHERE id_usuario = ? LIMIT 1"
    );
    $stmtDoador->execute([$id_usuario]);
    $doador = $stmtDoador->fetch(PDO::FETCH_ASSOC);
 
    // Histórico de doações
    $stmtDoacoes = $pdo->prepare(
        "SELECT d.id_doacao, d.data_doacao, d.categoria, d.item, d.quantidade,
                d.unidade_medida, d.estado_conservacao, d.data_validade, d.criado_em,
                o.nome AS ong_nome,
                CASE
                    WHEN dist.id_operacao IS NOT NULL THEN 'entregue'
                    WHEN e.id_lote IS NOT NULL THEN 'andamento'
                    ELSE 'pendente'
                END AS status_doacao
         FROM doacao d
         INNER JOIN doador dr ON dr.id_doador = d.id_doador
         LEFT JOIN estoque e ON e.id_doacao = d.id_doacao
         LEFT JOIN distribuicao dist ON dist.id_lote = e.id_lote
         LEFT JOIN ong o ON o.id_ong = dist.id_ong
         WHERE dr.id_usuario = ?
         ORDER BY d.data_doacao DESC, d.criado_em DESC"
    );
    $stmtDoacoes->execute([$id_usuario]);
    $doacoes = $stmtDoacoes->fetchAll(PDO::FETCH_ASSOC);
 
    // Consentimento LGPD (data do cadastro — usa doador.criado_em)
    $dataCadastro = $doador['criado_em'] ?? null;
 
} catch (PDOException $e) {
    error_log("meus_dados.php PDOException: " . $e->getMessage());
    die("Erro: " . $e->getMessage());
}
 
$nome       = $doador['nome']            ?? '—';
$cpf        = formatarCPF($doador['cpf'] ?? null);
$telefone   = formatarTelefone($doador['telefone'] ?? null);
$dataNasc   = formatarData($doador['data_nascimento'] ?? null);
$email      = $user['email']             ?? '—';
$status     = $user['status_cadastro']   ?? 'pendente';
$tipo       = $user['tipo']              ?? 'doador';
$membro     = $dataCadastro ? (new DateTime($dataCadastro))->format('d/m/Y') : '—';
$total      = count($doacoes);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Dados — Cruz Azul</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --azul:      #0057FF;
            --azul-soft: #EEF3FF;
            --texto:     #111827;
            --sub:       #6B7280;
            --borda:     #E5E7EB;
            --fundo:     #F9FAFB;
            --branco:    #FFFFFF;
            --verde:     #059669;
            --verde-bg:  #ECFDF5;
            --amarelo:   #D97706;
            --amarelo-bg:#FFFBEB;
            --vermelho:  #DC2626;
            --vermelho-bg:#FEF2F2;
        }
 
        * { box-sizing: border-box; margin: 0; padding: 0; }
 
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--fundo);
            color: var(--texto);
            min-height: 100vh;
        }
 
        /* ── NAV ── */
        nav {
            background: var(--azul);
            padding: 0 32px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
 
        .nav-logo {
            color: #fff;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            letter-spacing: -.01em;
        }
 
        .nav-links a {
            color: rgba(255,255,255,.85);
            text-decoration: none;
            font-size: .875rem;
            margin-left: 24px;
            transition: color .15s;
        }
 
        .nav-links a:hover { color: #fff; }
 
        /* ── BREADCRUMB ── */
        .breadcrumb {
            background: #fff;
            border-bottom: 1px solid var(--borda);
            padding: 10px 32px;
            font-size: .8rem;
            color: var(--sub);
        }
 
        .breadcrumb a { color: var(--azul); text-decoration: none; }
        .breadcrumb span { margin: 0 6px; }
 
        /* ── WRAPPER ── */
        .wrapper {
            max-width: 900px;
            margin: 32px auto;
            padding: 0 20px 64px;
        }
 
        /* ── CABEÇALHO DA PÁGINA ── */
        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 32px;
        }
 
        .page-header h1 {
            font-size: 1.6rem;
            font-weight: 600;
            letter-spacing: -.02em;
        }
 
        .page-header p {
            color: var(--sub);
            font-size: .9rem;
            margin-top: 4px;
        }
 
        .export-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
 
        .btn-export {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: .875rem;
            font-weight: 500;
            cursor: pointer;
            border: 1px solid var(--borda);
            background: var(--branco);
            color: var(--texto);
            transition: all .15s;
            font-family: inherit;
        }
 
        .btn-export:hover {
            border-color: var(--azul);
            color: var(--azul);
            background: var(--azul-soft);
        }
 
        .btn-export--primary {
            background: var(--azul);
            color: #fff;
            border-color: var(--azul);
        }
 
        .btn-export--primary:hover {
            background: #0046cc;
            border-color: #0046cc;
            color: #fff;
        }
 
        /* ── CARDS DE RESUMO ── */
        .resumo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }
 
        .resumo-card {
            background: var(--branco);
            border: 1px solid var(--borda);
            border-radius: 10px;
            padding: 20px;
        }
 
        .resumo-card .label {
            font-size: .75rem;
            color: var(--sub);
            text-transform: uppercase;
            letter-spacing: .06em;
            font-weight: 500;
            margin-bottom: 8px;
        }
 
        .resumo-card .valor {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--azul);
            font-family: 'DM Mono', monospace;
        }
 
        .resumo-card .sub {
            font-size: .8rem;
            color: var(--sub);
            margin-top: 4px;
        }
 
        /* ── SEÇÕES ── */
        .secao {
            background: var(--branco);
            border: 1px solid var(--borda);
            border-radius: 12px;
            margin-bottom: 24px;
            overflow: hidden;
        }
 
        .secao-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 24px;
            border-bottom: 1px solid var(--borda);
            background: var(--fundo);
        }
 
        .secao-titulo {
            font-size: .875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--azul);
            display: flex;
            align-items: center;
            gap: 8px;
        }
 
        .secao-acao {
            font-size: .8rem;
            color: var(--azul);
            text-decoration: none;
        }
 
        .secao-acao:hover { text-decoration: underline; }
 
        /* ── GRID DE DADOS ── */
        .dados-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
        }
 
        .dado-item {
            padding: 16px 24px;
            border-bottom: 1px solid var(--borda);
            border-right: 1px solid var(--borda);
        }
 
        .dado-item:nth-child(even) { border-right: none; }
        .dado-item:nth-last-child(-n+2) { border-bottom: none; }
 
        .dado-item .dado-label {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--sub);
            font-weight: 500;
            margin-bottom: 5px;
        }
 
        .dado-item .dado-valor {
            font-size: .95rem;
            color: var(--texto);
            font-weight: 400;
        }
 
        .dado-item .dado-valor.mono {
            font-family: 'DM Mono', monospace;
            font-size: .88rem;
        }
 
        .dado-item .dado-valor.vazio {
            color: #D1D5DB;
            font-style: italic;
        }
 
        /* ── BADGE STATUS ── */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: .75rem;
            font-weight: 600;
        }
 
        .badge-confirmado  { background: var(--verde-bg);   color: var(--verde);   }
        .badge-pendente    { background: var(--amarelo-bg);  color: var(--amarelo); }
        .badge-bloqueado   { background: var(--vermelho-bg); color: var(--vermelho);}
        .badge-entregue    { background: var(--verde-bg);    color: var(--verde);   }
        .badge-andamento   { background: var(--amarelo-bg);  color: var(--amarelo); }
        .badge-aguardando  { background: #F3F4F6;            color: var(--sub);     }
 
        /* ── TABELA DE DOAÇÕES ── */
        .tabela-wrap {
            overflow-x: auto;
        }
 
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: .875rem;
        }
 
        thead tr {
            background: var(--fundo);
        }
 
        thead th {
            padding: 12px 16px;
            text-align: left;
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--sub);
            font-weight: 600;
            border-bottom: 1px solid var(--borda);
            white-space: nowrap;
        }
 
        tbody tr {
            border-bottom: 1px solid var(--borda);
            transition: background .1s;
        }
 
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: var(--fundo); }
 
        tbody td {
            padding: 12px 16px;
            color: var(--texto);
            vertical-align: middle;
        }
 
        .vazio-tabela {
            padding: 48px 24px;
            text-align: center;
            color: var(--sub);
            font-size: .9rem;
        }
 
        /* ── SEGURANÇA ── */
        .seg-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 24px;
            border-bottom: 1px solid var(--borda);
        }
 
        .seg-item:last-child { border-bottom: none; }
 
        .seg-info .seg-label {
            font-size: .875rem;
            font-weight: 500;
        }
 
        .seg-info .seg-sub {
            font-size: .8rem;
            color: var(--sub);
            margin-top: 2px;
        }
 
        /* ── CONSENTIMENTO ── */
        .consent-item {
            padding: 16px 24px;
            border-bottom: 1px solid var(--borda);
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
 
        .consent-item:last-child { border-bottom: none; }
 
        .consent-check {
            width: 20px;
            height: 20px;
            background: var(--verde-bg);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: .7rem;
            margin-top: 2px;
        }
 
        .consent-texto .consent-titulo {
            font-size: .875rem;
            font-weight: 500;
        }
 
        .consent-texto .consent-data {
            font-size: .8rem;
            color: var(--sub);
            margin-top: 2px;
        }
 
        /* ── NOTA LGPD ── */
        .nota-lgpd {
            background: var(--azul-soft);
            border: 1px solid #C7D7FF;
            border-radius: 10px;
            padding: 16px 20px;
            font-size: .85rem;
            color: #1E40AF;
            display: flex;
            gap: 10px;
            align-items: flex-start;
            margin-bottom: 24px;
        }
 
        /* ── RESPONSIVO ── */
        @media (max-width: 600px) {
            .wrapper { padding: 0 12px 48px; margin-top: 20px; }
            .dados-grid { grid-template-columns: 1fr; }
            .dado-item { border-right: none; }
            .dado-item:nth-last-child(-n+2) { border-bottom: 1px solid var(--borda); }
            .dado-item:last-child { border-bottom: none; }
            nav { padding: 0 16px; }
            .nav-links { display: none; }
            .page-header { flex-direction: column; }
        }
    </style>
</head>
<body>
 
<nav>
    <a href="home_usuario.php" class="nav-logo">🤝 Cruz Azul</a>
    <div class="nav-links">
        <a href="home_usuario.php">Início</a>
        <a href="doar.php">Fazer doação</a>
        <a href="minhas_doacoes.php">Minhas doações</a>
        <a href="perfil.php">Perfil</a>
        <a href="logout.php">Sair</a>
    </div>
</nav>
 
<div class="breadcrumb">
    <a href="home_usuario.php">Início</a>
    <span>›</span>
    <a href="perfil.php">Meu Perfil</a>
    <span>›</span>
    Meus Dados
</div>
 
<div class="wrapper">
 
    <!-- Cabeçalho -->
    <div class="page-header">
        <div>
            <h1>Meus Dados</h1>
            <p>Todas as informações que a Cruz Azul possui sobre você, em um só lugar.</p>
        </div>
        <div class="export-buttons">
            <button class="btn-export" onclick="exportarJSON()">
                ⬇ Exportar JSON
            </button>
            <button class="btn-export btn-export--primary" onclick="exportarPDF()">
                ⬇ Exportar PDF
            </button>
        </div>
    </div>
 
    <!-- Nota LGPD -->
    <div class="nota-lgpd">
        <span>ℹ️</span>
        <span>Esta página exibe todos os dados pessoais que temos sobre você, conforme o <strong>Art. 18 da LGPD</strong>. Você pode exportar, corrigir ou solicitar a exclusão a qualquer momento.</span>
    </div>
 
    <!-- Cards de resumo -->
    <div class="resumo-grid">
        <div class="resumo-card">
            <div class="label">Total de doações</div>
            <div class="valor"><?= $total ?></div>
            <div class="sub">registradas na plataforma</div>
        </div>
        <div class="resumo-card">
            <div class="label">Membro desde</div>
            <div class="valor" style="font-size:1.1rem;margin-top:4px"><?= $membro ?></div>
            <div class="sub">data de cadastro</div>
        </div>
        <div class="resumo-card">
            <div class="label">Status da conta</div>
            <div class="valor" style="font-size:1rem;margin-top:6px">
                <?php
                $badgeStatus = match($status) {
                    'confirmado' => 'badge-confirmado',
                    'bloqueado'  => 'badge-bloqueado',
                    default      => 'badge-pendente',
                };
                $labelStatus = match($status) {
                    'confirmado' => 'Confirmado',
                    'bloqueado'  => 'Bloqueado',
                    default      => 'Pendente',
                };
                ?>
                <span class="badge <?= $badgeStatus ?>"><?= $labelStatus ?></span>
            </div>
            <div class="sub">tipo: <?= e($tipo) ?></div>
        </div>
        <div class="resumo-card">
            <div class="label">Proteção de dados</div>
            <div class="valor" style="font-size:1rem;margin-top:6px">
                <span class="badge badge-confirmado">✓ LGPD</span>
            </div>
            <div class="sub">em conformidade</div>
        </div>
    </div>
 
    <!-- Dados pessoais -->
    <div class="secao" id="sec-pessoais">
        <div class="secao-header">
            <div class="secao-titulo">👤 Dados Pessoais</div>
            <a href="editar_perfil.php" class="secao-acao">Editar →</a>
        </div>
        <div class="dados-grid">
            <div class="dado-item">
                <div class="dado-label">Nome completo</div>
                <div class="dado-valor <?= ($nome === '—') ? 'vazio' : '' ?>"><?= e($nome) ?></div>
            </div>
            <div class="dado-item">
                <div class="dado-label">E-mail</div>
                <div class="dado-valor mono"><?= e($email) ?></div>
            </div>
            <div class="dado-item">
                <div class="dado-label">CPF</div>
                <div class="dado-valor mono <?= ($cpf === '—') ? 'vazio' : '' ?>"><?= e($cpf) ?></div>
            </div>
            <div class="dado-item">
                <div class="dado-label">Telefone / WhatsApp</div>
                <div class="dado-valor mono <?= ($telefone === '—') ? 'vazio' : '' ?>"><?= e($telefone) ?></div>
            </div>
            <div class="dado-item">
                <div class="dado-label">Data de Nascimento</div>
                <div class="dado-valor <?= ($dataNasc === '—') ? 'vazio' : '' ?>"><?= e($dataNasc) ?></div>
            </div>
            <div class="dado-item">
                <div class="dado-label">Cadastrado em</div>
                <div class="dado-valor"><?= $membro ?></div>
            </div>
        </div>
    </div>
 
    <!-- Histórico de doações -->
    <div class="secao" id="sec-doacoes">
        <div class="secao-header">
            <div class="secao-titulo">📦 Histórico de Doações <span style="font-weight:400;color:var(--sub);text-transform:none;letter-spacing:0">(<?= $total ?>)</span></div>
            <a href="minhas_doacoes.php" class="secao-acao">Ver tudo →</a>
        </div>
        <div class="tabela-wrap">
            <?php if (empty($doacoes)): ?>
                <div class="vazio-tabela">Nenhuma doação registrada ainda. <a href="doar.php" style="color:var(--azul)">Faça sua primeira doação →</a></div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Data</th>
                        <th>Item</th>
                        <th>Categoria</th>
                        <th>Qtd</th>
                        <th>ONG Beneficiária</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($doacoes as $i => $d):
                        $badgeClass = match($d['status_doacao']) {
                            'entregue'  => 'badge-entregue',
                            'andamento' => 'badge-andamento',
                            default     => 'badge-aguardando',
                        };
                        $badgeLabel = match($d['status_doacao']) {
                            'entregue'  => 'Entregue',
                            'andamento' => 'Em andamento',
                            default     => 'Aguardando',
                        };
                    ?>
                    <tr>
                        <td style="color:var(--sub);font-family:'DM Mono',monospace;font-size:.8rem"><?= $i + 1 ?></td>
                        <td style="white-space:nowrap"><?= e(date('d/m/Y', strtotime($d['data_doacao']))) ?></td>
                        <td><?= e($d['item']) ?></td>
                        <td><?= e(ucfirst($d['categoria'])) ?></td>
                        <td style="font-family:'DM Mono',monospace"><?= ($d['quantidade'] ?? 0) + 0 ?> <?= e($d['unidade_medida']) ?></td>
                        <td><?= $d['ong_nome'] ? e($d['ong_nome']) : '<span style="color:#D1D5DB">Aguardando</span>' ?></td>
                        <td><span class="badge <?= $badgeClass ?>"><?= $badgeLabel ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
 
    <!-- Segurança -->
    <div class="secao" id="sec-seguranca">
        <div class="secao-header">
            <div class="secao-titulo">🔐 Segurança e Acesso</div>
        </div>
        <div class="seg-item">
            <div class="seg-info">
                <div class="seg-label">Senha</div>
                <div class="seg-sub">Armazenada com hash bcrypt — nunca em texto puro</div>
            </div>
            <span class="badge badge-confirmado">✓ Protegida</span>
        </div>
        <div class="seg-item">
            <div class="seg-info">
                <div class="seg-label">Autenticação em dois fatores (2FA)</div>
                <div class="seg-sub">Verificação via aplicativo TOTP</div>
            </div>
            <span class="badge badge-confirmado">✓ Ativo</span>
        </div>
        <div class="seg-item">
            <div class="seg-info">
                <div class="seg-label">Cookies utilizados</div>
                <div class="seg-sub">PHPSESSID (sessão) e token CSRF (segurança de formulários) — sem rastreamento</div>
            </div>
            <span class="badge badge-confirmado">✓ Apenas técnicos</span>
        </div>
        <div class="seg-item">
            <div class="seg-info">
                <div class="seg-label">Compartilhamento com terceiros</div>
                <div class="seg-sub">Seus dados não são vendidos nem compartilhados para marketing</div>
            </div>
            <span class="badge badge-confirmado">✓ Nenhum</span>
        </div>
    </div>
 
    <!-- Consentimentos -->
    <div class="secao" id="sec-consentimentos">
        <div class="secao-header">
            <div class="secao-titulo">✅ Consentimentos Registrados</div>
        </div>
        <div class="consent-item">
            <div class="consent-check">✓</div>
            <div class="consent-texto">
                <div class="consent-titulo">Política de Privacidade e Termos de Uso (LGPD)</div>
                <div class="consent-data">Aceito em <?= $membro ?> — <a href="privacidade.php" target="_blank" style="color:var(--azul)">Ver política →</a></div>
            </div>
        </div>
        <div class="consent-item">
            <div class="consent-check">✓</div>
            <div class="consent-texto">
                <div class="consent-titulo">Coleta e tratamento de dados pessoais para fins de doação</div>
                <div class="consent-data">Aceito em <?= $membro ?> no momento do cadastro</div>
            </div>
        </div>
    </div>
 
</div>
 
<script>
// ── Dados para exportação ─────────────────────────────────────
const dadosUsuario = {
    exportado_em: new Date().toISOString(),
    dados_pessoais: {
        nome:             <?= json_encode($nome) ?>,
        email:            <?= json_encode($email) ?>,
        cpf:              <?= json_encode($cpf) ?>,
        telefone:         <?= json_encode($telefone) ?>,
        data_nascimento:  <?= json_encode($dataNasc) ?>,
        membro_desde:     <?= json_encode($membro) ?>,
        status:           <?= json_encode($labelStatus ?? $status) ?>,
        tipo:             <?= json_encode($tipo) ?>
    },
    doacoes: <?= json_encode(array_map(fn($d) => [
        'data'      => date('d/m/Y', strtotime($d['data_doacao'])),
        'item'      => $d['item'],
        'categoria' => $d['categoria'],
        'quantidade'=> ($d['quantidade'] ?? 0) + 0,
        'unidade'   => $d['unidade_medida'],
        'ong'       => $d['ong_nome'] ?? 'Aguardando distribuição',
        'status'    => $d['status_doacao'],
    ], $doacoes)) ?>,
    seguranca: {
        senha:          "hash bcrypt (não visível)",
        dois_fatores:   "ativo",
        cookies:        ["PHPSESSID", "csrf_token"],
        rastreamento:   "nenhum"
    },
    consentimentos: [
        { descricao: "Política de Privacidade e LGPD", data: <?= json_encode($membro) ?> },
        { descricao: "Coleta de dados para fins de doação", data: <?= json_encode($membro) ?> }
    ]
};
 
// ── Exportar JSON ─────────────────────────────────────────────
function exportarJSON() {
    const blob = new Blob([JSON.stringify(dadosUsuario, null, 2)], { type: 'application/json' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = 'meus_dados_cruzazul.json';
    a.click();
    URL.revokeObjectURL(url);
}
 
// ── Exportar PDF ──────────────────────────────────────────────
function exportarPDF() {
    const conteudo = `
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Meus Dados – Cruz Azul</title>
            <style>
                body { font-family: Arial, sans-serif; color: #111; padding: 32px; font-size: 13px; }
                h1   { color: #0057FF; font-size: 20px; border-bottom: 2px solid #0057FF; padding-bottom: 8px; margin-bottom: 4px; }
                .sub { color: #6B7280; font-size: 11px; margin-bottom: 24px; }
                h2   { font-size: 13px; text-transform: uppercase; letter-spacing: .06em; color: #0057FF; margin: 24px 0 10px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
                th   { background: #F3F4F6; text-align: left; padding: 8px 10px; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: #6B7280; border: 1px solid #E5E7EB; }
                td   { padding: 8px 10px; border: 1px solid #E5E7EB; vertical-align: top; }
                .rodape { margin-top: 32px; font-size: 11px; color: #9CA3AF; border-top: 1px solid #E5E7EB; padding-top: 12px; }
            </style>
        </head>
        <body>
            <h1>Meus Dados — Cruz Azul</h1>
            <div class="sub">Exportado em ${new Date().toLocaleString('pt-BR')} · Conforme LGPD Art. 18</div>
 
            <h2>Dados Pessoais</h2>
            <table>
                <tr><th>Campo</th><th>Valor</th></tr>
                ${Object.entries(dadosUsuario.dados_pessoais).map(([k,v]) =>
                    `<tr><td>${k.replace(/_/g,' ')}</td><td>${v ?? '—'}</td></tr>`
                ).join('')}
            </table>
 
            <h2>Histórico de Doações (${dadosUsuario.doacoes.length})</h2>
            ${dadosUsuario.doacoes.length === 0 ? '<p style="color:#9CA3AF">Nenhuma doação registrada.</p>' : `
            <table>
                <tr><th>Data</th><th>Item</th><th>Categoria</th><th>Qtd</th><th>ONG</th><th>Status</th></tr>
                ${dadosUsuario.doacoes.map(d =>
                    `<tr><td>${d.data}</td><td>${d.item}</td><td>${d.categoria}</td><td>${d.quantidade} ${d.unidade}</td><td>${d.ong}</td><td>${d.status}</td></tr>`
                ).join('')}
            </table>`}
 
            <h2>Segurança</h2>
            <table>
                <tr><th>Item</th><th>Status</th></tr>
                <tr><td>Senha</td><td>Hash bcrypt (não visível)</td></tr>
                <tr><td>2FA</td><td>Ativo</td></tr>
                <tr><td>Cookies</td><td>PHPSESSID, csrf_token (apenas técnicos)</td></tr>
                <tr><td>Rastreamento</td><td>Nenhum</td></tr>
            </table>
 
            <h2>Consentimentos</h2>
            <table>
                <tr><th>Descrição</th><th>Data</th></tr>
                ${dadosUsuario.consentimentos.map(c =>
                    `<tr><td>${c.descricao}</td><td>${c.data}</td></tr>`
                ).join('')}
            </table>
 
            <div class="rodape">Cruz Azul · Sistema de Suprimentos · Dados gerados conforme LGPD (Lei nº 13.709/2018)</div>
        </body>
        </html>
    `;
 
    const janela = window.open('', '_blank');
    janela.document.write(conteudo);
    janela.document.close();
    janela.focus();
    setTimeout(() => { janela.print(); }, 500);
}
</script>
 
</body>
</html>