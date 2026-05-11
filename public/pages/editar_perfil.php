<?php
// ============================================================
//  editar_perfil.php  –  public/pages/editar_perfil.php
// ============================================================
session_start();
 
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
 
require_once __DIR__ . '/../../src/api/database.php';
 
// --- Autenticação ---
if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['id_usuario'])) {
    header("Location: login.php");
    exit();
}
 
$id_usuario = (int) $_SESSION['usuario']['id_usuario'];
 
// --- CSRF ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
 
function e(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
 
// --- Mesma função validarCPF do cadastro.php ---
function validarCPF(string $cpf): bool {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) !== 11) return false;
    if (preg_match('/(\d)\1{10}/', $cpf)) return false;
 
    $soma = 0;
    for ($i = 0; $i < 9; $i++) $soma += $cpf[$i] * (10 - $i);
    $resto   = $soma % 11;
    $digito1 = ($resto < 2) ? 0 : 11 - $resto;
 
    $soma = 0;
    for ($i = 0; $i < 10; $i++) $soma += $cpf[$i] * (11 - $i);
    $resto   = $soma % 11;
    $digito2 = ($resto < 2) ? 0 : 11 - $resto;
 
    return ($cpf[9] == $digito1 && $cpf[10] == $digito2);
}
 
// --- Busca dados atuais ---
try {
    $stmtUser = $pdo->prepare(
        "SELECT email FROM usuario WHERE id_usuario = ? LIMIT 1"
    );
    $stmtUser->execute([$id_usuario]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
 
    if (!$user) {
        session_destroy();
        header("Location: login.php");
        exit();
    }
 
    $stmtDoador = $pdo->prepare(
        "SELECT id_doador, nome, cpf, telefone, data_nascimento FROM doador WHERE id_usuario = ? LIMIT 1"
    );
    $stmtDoador->execute([$id_usuario]);
    $perfil = $stmtDoador->fetch(PDO::FETCH_ASSOC);
    $tipo   = 'doador';
 
    if (!$perfil) {
        $stmtONG = $pdo->prepare(
            "SELECT id_ong, nome, localizacao FROM ong WHERE id_usuario = ? LIMIT 1"
        );
        $stmtONG->execute([$id_usuario]);
        $perfil = $stmtONG->fetch(PDO::FETCH_ASSOC);
        $tipo   = 'ong';
    }
 
    if (!$perfil) {
        die("Perfil não encontrado. Entre em contato com o suporte.");
    }
 
} catch (PDOException $ex) {
    error_log("editar_perfil.php – busca: " . $ex->getMessage());
    die("Erro interno ao carregar o perfil. Tente novamente.");
}
 
// ============================================================
//  Processamento do POST (salvar edição)
// ============================================================
$erros = [];
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 
    if (
        empty($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        die("Requisição inválida (CSRF).");
    }
 
    $novo_nome        = trim(strip_tags($_POST['nome']             ?? ''));
    $novo_email       = trim($_POST['email']                       ?? '');
    $nova_senha       = $_POST['senha']                            ?? '';
    $confirma_senha   = $_POST['confirma_senha']                   ?? '';
    $novo_telefone    = trim($_POST['telefone']                    ?? '');
    $novo_cpf         = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
    $nova_data_nasc   = trim($_POST['data_nascimento']             ?? '');
 
    // --- Nome ---
    if ($novo_nome === '') {
        $erros[] = "O nome não pode estar vazio.";
    } elseif (mb_strlen($novo_nome) > 200) {
        $erros[] = "Nome muito longo (máximo 200 caracteres).";
    }
 
    // --- E-mail ---
    $novo_email = filter_var($novo_email, FILTER_SANITIZE_EMAIL);
    if ($novo_email === '') {
        $erros[] = "O e-mail não pode estar vazio.";
    } elseif (!preg_match('/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/', $novo_email)) {
        $erros[] = "E-mail inválido.";
    } elseif (mb_strlen($novo_email) > 254) {
        $erros[] = "E-mail muito longo.";
    }
 
    // --- Senha opcional ---
    $alterar_senha = ($nova_senha !== '');
    if ($alterar_senha) {
        if (mb_strlen($nova_senha) < 8) {
            $erros[] = "A senha deve ter pelo menos 8 caracteres.";
        }
        if ($nova_senha !== $confirma_senha) {
            $erros[] = "A confirmação de senha não confere.";
        }
    }
 
    if ($tipo === 'doador') {
 
        // --- Telefone ---
        $telefone_digits = preg_replace('/\D/', '', $novo_telefone);
        if ($telefone_digits === '') {
            $erros[] = "O telefone não pode estar vazio.";
        } elseif (!preg_match('/^\d{10,11}$/', $telefone_digits)) {
            $erros[] = "Telefone inválido. Use DDD + número (10 ou 11 dígitos).";
        } else {
            $novo_telefone = $telefone_digits;
        }
 
        // --- CPF (mesma lógica do cadastro.php) ---
        if ($novo_cpf === '') {
            $erros[] = "O CPF não pode estar vazio.";
        } elseif (!validarCPF($novo_cpf)) {
            $erros[] = "O CPF informado não é válido.";
        } else {
            // Verifica CPF duplicado em outro doador
            $stmtCpf = $pdo->prepare(
                "SELECT d.id_doador FROM doador d
                  WHERE d.cpf = ? AND d.id_usuario <> ? LIMIT 1"
            );
            $stmtCpf->execute([$novo_cpf, $id_usuario]);
            if ($stmtCpf->fetch()) {
                $erros[] = "Este CPF já está cadastrado em outra conta.";
            }
        }
 
        // --- Data de nascimento (mesma lógica do cadastro.php) ---
        if ($nova_data_nasc === '') {
            $erros[] = "A data de nascimento não pode estar vazia.";
        } else {
            $dataNascObj = DateTime::createFromFormat('Y-m-d', $nova_data_nasc);
            if (!$dataNascObj) {
                $erros[] = "Data de nascimento inválida.";
            } else {
                $idade = (new DateTime())->diff($dataNascObj)->y;
                if ($idade < 18) {
                    $erros[] = "É necessário ter 18 anos ou mais.";
                }
            }
        }
    }
 
    // --- E-mail duplicado ---
    if (empty($erros) && $novo_email !== $user['email']) {
        $stmtCheck = $pdo->prepare(
            "SELECT id_usuario FROM usuario WHERE email = ? AND id_usuario <> ? LIMIT 1"
        );
        $stmtCheck->execute([$novo_email, $id_usuario]);
        if ($stmtCheck->fetch()) {
            $erros[] = "Este e-mail já está em uso por outra conta.";
        }
    }
 
    // --- Persiste ---
    if (empty($erros)) {
        try {
            $pdo->beginTransaction();
 
            if ($alterar_senha) {
                $hash = password_hash($nova_senha, PASSWORD_BCRYPT);
                $pdo->prepare(
                    "UPDATE usuario SET email = ?, senha_hash = ? WHERE id_usuario = ?"
                )->execute([$novo_email, $hash, $id_usuario]);
            } else {
                $pdo->prepare(
                    "UPDATE usuario SET email = ? WHERE id_usuario = ?"
                )->execute([$novo_email, $id_usuario]);
            }
 
            if ($tipo === 'doador') {
                $pdo->prepare("
                    UPDATE doador
                    SET nome = ?, cpf = ?, telefone = ?, data_nascimento = ?
                    WHERE id_usuario = ?
                ")->execute([$novo_nome, $novo_cpf, $novo_telefone, $nova_data_nasc, $id_usuario]);
            }
 
            if ($tipo === 'ong') {
                $pdo->prepare(
                    "UPDATE ong SET nome = ? WHERE id_usuario = ?"
                )->execute([$novo_nome, $id_usuario]);
            }
 
            $pdo->commit();
 
            session_regenerate_id(true);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
 
            header("Location: perfil.php?status=atualizado");
            exit();
 
        } catch (PDOException $ex) {
            $pdo->rollBack();
            error_log("editar_perfil.php – update: " . $ex->getMessage());
            $erros[] = "Erro ao salvar os dados. Tente novamente.";
        }
    }
 
    // Re-exibe formulário com valores digitados
    $user['email']              = $novo_email;
    $perfil['nome']             = $novo_nome;
    $perfil['cpf']              = $novo_cpf;
    $perfil['data_nascimento']  = $nova_data_nasc;
    if ($tipo === 'doador') {
        $perfil['telefone'] = $novo_telefone;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil – Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/perfil.css">
</head>
<body>
<div class="perfil-container perfil-container--editar">
 
    <h1>Editar Perfil</h1>
 
    <?php if (!empty($erros)): ?>
        <div class="alerta-erro" role="alert">
            <strong>Corrija os erros abaixo:</strong>
            <ul>
                <?php foreach ($erros as $erro): ?>
                    <li><?= e($erro) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
 
    <form method="post" action="editar_perfil.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
 
        <!-- Nome -->
        <div class="campo">
            <label for="nome">Nome</label>
            <div class="campo-com-acao">
                <input type="text" id="nome" name="nome" required maxlength="200"
                       value="<?= e($perfil['nome'] ?? '') ?>">
                <button type="button" class="btn-apagar-campo"
                        data-campo="nome" data-label="nome" title="Apagar nome">🗑</button>
            </div>
        </div>
 
        <!-- E-mail -->
        <div class="campo">
            <label for="email">E-mail</label>
            <div class="campo-com-acao">
                <input type="email" id="email" name="email" required maxlength="254"
                       value="<?= e($user['email'] ?? '') ?>">
                <button type="button" class="btn-apagar-campo"
                        data-campo="email" data-label="e-mail" title="Apagar e-mail">🗑</button>
            </div>
            <p class="hint aviso-campo" id="aviso-email" style="display:none">
                ⚠️ Apagar o e-mail bloqueará seu acesso ao sistema.
            </p>
        </div>
 
        <?php if ($tipo === 'doador'): ?>
 
        <!-- CPF -->
        <div class="campo">
            <label for="cpf">CPF <span class="campo-hint-label">(apenas números)</span></label>
            <div class="campo-com-acao">
                <input type="text" id="cpf" name="cpf" maxlength="14"
                       value="<?= e($perfil['cpf'] ?? '') ?>"
                       placeholder="Somente números">
                <button type="button" class="btn-apagar-campo"
                        data-campo="cpf" data-label="CPF" title="Apagar CPF">🗑</button>
            </div>
            <p class="hint" id="erro-cpf" style="display:none; color:#dc3545;"></p>
        </div>
 
        <!-- Telefone -->
        <div class="campo">
            <label for="telefone">Telefone / WhatsApp</label>
            <div class="campo-com-acao">
                <input type="tel" id="telefone" name="telefone" maxlength="20"
                       value="<?= e($perfil['telefone'] ?? '') ?>">
                <button type="button" class="btn-apagar-campo"
                        data-campo="telefone" data-label="telefone" title="Apagar telefone">🗑</button>
            </div>
            <p class="hint">Somente números com DDD (ex.: 11987654321).</p>
        </div>
 
        <!-- Data de nascimento -->
        <div class="campo">
            <label for="data_nascimento">Data de Nascimento</label>
            <div class="campo-com-acao">
                <input type="date" id="data_nascimento" name="data_nascimento"
                       value="<?= e($perfil['data_nascimento'] ?? '') ?>">
                <button type="button" class="btn-apagar-campo"
                        data-campo="data_nascimento" data-label="data de nascimento"
                        title="Apagar data de nascimento">🗑</button>
            </div>
            <p class="hint" id="erro-data" style="display:none; color:#dc3545;"></p>
        </div>
 
        <?php endif; ?>
 
        <hr class="separador">
 
        <!-- Senha -->
        <div class="campo">
            <label for="senha">Nova senha</label>
            <input type="password" id="senha" name="senha"
                   autocomplete="new-password" maxlength="128">
            <p class="hint">Deixe em branco para manter a senha atual. Mínimo 8 caracteres.</p>
        </div>
        <div class="campo">
            <label for="confirma_senha">Confirmar nova senha</label>
            <input type="password" id="confirma_senha" name="confirma_senha"
                   autocomplete="new-password" maxlength="128">
        </div>
 
        <div class="acoes">
            <button type="submit" class="btn-primary">Salvar alterações</button>
            <a href="perfil.php" class="btn-secondary">Cancelar</a>
        </div>
    </form>
 
    <!-- ░░ ZONA DE PERIGO ░░ -->
    <div class="zona-perigo">
        <h3>⚠️ Encerrar conta</h3>
        <p>
            Ao solicitar o encerramento, seus <strong>dados pessoais serão todos removidos</strong>
            (nome, CPF, telefone, e-mail e senha). O histórico de doações é mantido de forma
            anônima para fins de controle interno, conforme a LGPD.
        </p>
        <p>Seu acesso será bloqueado imediatamente e esta ação não poderá ser desfeita.</p>
        <button class="btn-danger" onclick="confirmarExclusaoParcial()">
            Encerrar minha conta
        </button>
    </div>
 
</div>
 
<script>
const CSRF      = '<?= e($_SESSION['csrf_token']) ?>';
const API_CAMPO = '../../src/api/anonimizar_campo.php';
const API_CONTA = '../../src/api/deletar_conta.php';
 
// ── Validação de CPF (mesma lógica do cadastro.php) ──────────
function validarCPF(cpf) {
    cpf = cpf.replace(/[^0-9]/g, '');
    if (cpf.length !== 11) return false;
    if (/^(\d)\1{10}$/.test(cpf)) return false;
 
    let soma = 0;
    for (let i = 0; i < 9; i++) soma += parseInt(cpf[i]) * (10 - i);
    let resto = soma % 11;
    const d1 = resto < 2 ? 0 : 11 - resto;
 
    soma = 0;
    for (let i = 0; i < 10; i++) soma += parseInt(cpf[i]) * (11 - i);
    resto = soma % 11;
    const d2 = resto < 2 ? 0 : 11 - resto;
 
    return parseInt(cpf[9]) === d1 && parseInt(cpf[10]) === d2;
}
 
// ── Validação de idade mínima (mesma lógica do cadastro.php) ─
function calcularIdade(dataStr) {
    const hoje     = new Date();
    const dataNasc = new Date(dataStr);
    let idade = hoje.getFullYear() - dataNasc.getFullYear();
    const m = hoje.getMonth() - dataNasc.getMonth();
    if (m < 0 || (m === 0 && hoje.getDate() < dataNasc.getDate())) idade--;
    return idade;
}
 
// ── Feedback inline de CPF ───────────────────────────────────
const campoCPF  = document.getElementById('cpf');
const erroCPF   = document.getElementById('erro-cpf');
 
campoCPF?.addEventListener('blur', () => {
    const val = campoCPF.value.replace(/[^0-9]/g, '');
    if (val === '') { erroCPF.style.display = 'none'; return; }
    if (!validarCPF(val)) {
        erroCPF.textContent = 'CPF inválido.';
        erroCPF.style.display = 'block';
        campoCPF.classList.add('campo-invalido');
    } else {
        erroCPF.style.display = 'none';
        campoCPF.classList.remove('campo-invalido');
    }
});
 
campoCPF?.addEventListener('input', () => {
    erroCPF.style.display = 'none';
    campoCPF.classList.remove('campo-invalido');
});
 
// ── Feedback inline de data de nascimento ───────────────────
const campoData = document.getElementById('data_nascimento');
const erroData  = document.getElementById('erro-data');
 
campoData?.addEventListener('change', () => {
    const val = campoData.value;
    if (!val) { erroData.style.display = 'none'; return; }
    const idade = calcularIdade(val);
    if (idade < 18) {
        erroData.textContent = 'É necessário ter 18 anos ou mais.';
        erroData.style.display = 'block';
        campoData.classList.add('campo-invalido');
    } else {
        erroData.style.display = 'none';
        campoData.classList.remove('campo-invalido');
    }
});
 
// ── Aviso dinâmico e-mail ────────────────────────────────────
document.querySelector('[data-campo="email"]')
    ?.addEventListener('mouseenter', () => {
        document.getElementById('aviso-email').style.display = 'block';
    });
document.querySelector('[data-campo="email"]')
    ?.addEventListener('mouseleave', () => {
        document.getElementById('aviso-email').style.display = 'none';
    });
 
// ── Botões de apagar campo ───────────────────────────────────
document.querySelectorAll('.btn-apagar-campo').forEach(btn => {
    btn.addEventListener('click', () => {
        const campo = btn.dataset.campo;
        const label = btn.dataset.label;
 
        const avisos = {
            nome:             `Deseja apagar seu nome?\n\nEle será substituído por "Usuário Removido".`,
            email:            `Deseja apagar seu e-mail?\n\n⚠️ ATENÇÃO: sem e-mail você não conseguirá fazer login novamente.\n\nTem certeza?`,
            cpf:              `Deseja apagar seu CPF?\n\nEsta informação será removida permanentemente.`,
            telefone:         `Deseja apagar seu telefone?\n\nEsta informação será removida permanentemente.`,
            data_nascimento:  `Deseja apagar sua data de nascimento?\n\nEsta informação será removida permanentemente.`,
        };
 
        if (!confirm(avisos[campo] ?? `Deseja apagar ${label}?`)) return;
 
        btn.disabled = true;
        btn.textContent = '⏳';
 
        fetch(API_CAMPO, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'csrf_token=' + encodeURIComponent(CSRF)
                + '&campo='     + encodeURIComponent(campo)
        })
        .then(r => r.json())
        .then(json => {
            if (json.ok) {
                const input = document.getElementById(campo);
                if (input) { input.value = ''; input.classList.add('campo-apagado'); }
                btn.textContent = '✓';
                btn.classList.add('btn-apagar-campo--ok');
                btn.disabled = true;
            } else {
                alert('Erro: ' + json.msg);
                btn.disabled = false;
                btn.textContent = '🗑';
            }
        })
        .catch(() => {
            alert('Erro de conexão. Tente novamente.');
            btn.disabled = false;
            btn.textContent = '🗑';
        });
    });
});
 
// ── Encerrar conta completa ──────────────────────────────────
function confirmarExclusaoParcial() {
    if (!confirm('Deseja encerrar sua conta?\n\nTodos os seus dados pessoais serão removidos, mas seu histórico de doações será mantido de forma anônima.')) return;
    if (!confirm('Confirme: seu acesso será bloqueado imediatamente e esta ação não pode ser desfeita. Continuar?')) return;
 
    fetch(API_CONTA, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'csrf_token=' + encodeURIComponent(CSRF)
    })
    .then(r => r.json())
    .then(json => {
        if (json.ok) {
            alert('Conta encerrada. Seus dados pessoais foram removidos.');
            window.location.href = 'login.php';
        } else {
            alert('Erro: ' + json.msg);
        }
    })
    .catch(() => alert('Erro de conexão. Tente novamente.'));
}
</script>
</body>
</html>
 