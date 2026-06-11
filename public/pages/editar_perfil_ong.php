<?php
// ============================================================
//  editar_perfil_ong.php  —  public/pages/editar_perfil_ong.php
// ============================================================
session_start();

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['ong'])) {
    header('Location: login_ong.php');
    exit;
}

require '../../src/api/database.php';
require_once '../../src/crypto/crypto_helpers.php';

function e(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$ongId = (int) ($_SESSION['ong']['id'] ?? 0);
$erro  = '';

// ============================================================
//  POST — salvar edição
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        empty($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        die("Requisição inválida (CSRF).");
    }

    $nome        = trim($_POST['nome_receptor'] ?? '');
    $email       = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $area        = trim($_POST['area_atuacao']  ?? '');
    $localizacao = trim($_POST['localizacao']   ?? '');
    $cidade      = trim($_POST['cidade']        ?? '');
    $estado      = strtoupper(trim($_POST['sigla_estado'] ?? ''));
    $endereco    = trim($_POST['endereco']      ?? '');
    $descricao   = trim($_POST['descricao']     ?? '');

    if ($nome === '' || $email === '') {
        $erro = 'Preencha pelo menos nome da ONG e e-mail.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido.';
    }

    if ($erro === '') {
        // Cifra os campos sensíveis antes de salvar
        $enc = cifrarOng([
            'nome'        => $nome,
            'email'       => $email,
            'area_atuacao'=> $area,
            'cidade'      => $cidade,
            'endereco'    => $endereco,
            'descricao'   => $descricao,
            'localizacao' => $localizacao,
        ]);

        $stmt = $pdo->prepare('
            UPDATE ong
            SET nome = ?, email = ?, area_atuacao = ?, localizacao = ?,
                cidade = ?, sigla_estado = ?, endereco = ?, descricao = ?,
                iv_dados = ?, chave_aes_cifrada = ?,
                data_atualizacao = NOW()
            WHERE id_ong = ?
        ');
        $stmt->execute([
            $enc['nome'],
            $enc['email'],
            $enc['area_atuacao'],
            $enc['localizacao'],
            $enc['cidade'],
            $estado,
            $enc['endereco'],
            $enc['descricao'],
            $enc['iv_dados'],
            $enc['chave_aes_cifrada'],
            $ongId,
        ]);

        $_SESSION['ong']['nome']         = $nome;
        $_SESSION['ong']['email']        = $email;
        $_SESSION['ong']['area_atuacao'] = $area;
        $_SESSION['ong']['cidade']       = $cidade;
        $_SESSION['ong']['estado']       = $estado;

        header('Location: perfil_ong.php?status=atualizado');
        exit;
    } else {
        // Mantém valores do POST para repopular o form
        $ong = [
            'nome'         => $nome,
            'email'        => $email,
            'area_atuacao' => $area,
            'localizacao'  => $localizacao,
            'cidade'       => $cidade,
            'sigla_estado' => $estado,
            'endereco'     => $endereco,
            'descricao'    => $descricao,
        ];
    }
}

// Busca dados atuais (GET ou POST com erro)
if (empty($ong)) {
    $stmt = $pdo->prepare('
        SELECT nome, email, area_atuacao, localizacao, cidade, sigla_estado,
               endereco, descricao, iv_dados, chave_aes_cifrada
        FROM ong WHERE id_ong = ?
    ');
    $stmt->execute([$ongId]);
    $ong = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ong) {
        header('Location: logout.php');
        exit;
    }

    // Decifra para preencher o formulário
    if (!empty($ong['chave_aes_cifrada']) && !empty($ong['iv_dados'])) {
        decifrarOng($ong);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil da ONG – Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/perfil.css">
</head>
<body>
<div class="perfil-container perfil-container--ong">

    <h1>Editar Perfil da ONG</h1>

    <?php if ($erro !== ''): ?>
        <div class="alerta-erro"><?= e($erro) ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

        <div class="campo">
            <label for="nome_receptor">Nome da ONG</label>
            <div class="campo-com-acao">
                <input type="text" id="nome_receptor" name="nome_receptor"
                       value="<?= e($ong['nome'] ?? '') ?>" required>
                <button type="button" class="btn-apagar-campo"
                        data-campo="nome" data-label="nome da ONG" title="Apagar nome">🗑</button>
            </div>
        </div>

        <div class="campo">
            <label for="email">E-mail</label>
            <div class="campo-com-acao">
                <input type="email" id="email" name="email"
                       value="<?= e($ong['email'] ?? '') ?>" required>
                <button type="button" class="btn-apagar-campo"
                        data-campo="email" data-label="e-mail" title="Apagar e-mail">🗑</button>
            </div>
            <p class="hint aviso-campo" id="aviso-email" style="display:none">
                ⚠️ Apagar o e-mail bloqueará o acesso da ONG ao sistema.
            </p>
        </div>

        <div class="campo">
            <label for="area_atuacao">Área de atuação</label>
            <div class="campo-com-acao">
                <input type="text" id="area_atuacao" name="area_atuacao"
                       value="<?= e($ong['area_atuacao'] ?? '') ?>">
                <button type="button" class="btn-apagar-campo"
                        data-campo="area_atuacao" data-label="área de atuação" title="Apagar">🗑</button>
            </div>
        </div>

        <div class="campos-grid">
            <div class="campo">
                <label for="cidade">Cidade</label>
                <div class="campo-com-acao">
                    <input type="text" id="cidade" name="cidade"
                           value="<?= e($ong['cidade'] ?? '') ?>">
                    <button type="button" class="btn-apagar-campo"
                            data-campo="cidade" data-label="cidade" title="Apagar cidade">🗑</button>
                </div>
            </div>
            <div class="campo">
                <label for="sigla_estado">Estado (UF)</label>
                <div class="campo-com-acao">
                    <input type="text" id="sigla_estado" name="sigla_estado"
                           maxlength="2" value="<?= e($ong['sigla_estado'] ?? '') ?>">
                    <button type="button" class="btn-apagar-campo"
                            data-campo="sigla_estado" data-label="estado" title="Apagar estado">🗑</button>
                </div>
            </div>
        </div>

        <div class="campo">
            <label for="localizacao">CEP / Localização</label>
            <div class="campo-com-acao">
                <input type="text" id="localizacao" name="localizacao"
                       value="<?= e($ong['localizacao'] ?? '') ?>">
                <button type="button" class="btn-apagar-campo"
                        data-campo="localizacao" data-label="localização" title="Apagar">🗑</button>
            </div>
        </div>

        <div class="campo">
            <label for="endereco">Endereço</label>
            <div class="campo-com-acao">
                <input type="text" id="endereco" name="endereco"
                       value="<?= e($ong['endereco'] ?? '') ?>">
                <button type="button" class="btn-apagar-campo"
                        data-campo="endereco" data-label="endereço" title="Apagar">🗑</button>
            </div>
        </div>

        <div class="campo">
            <label for="descricao">Descrição</label>
            <div class="campo-com-acao campo-com-acao--textarea">
                <textarea id="descricao" name="descricao"><?= e($ong['descricao'] ?? '') ?></textarea>
                <button type="button" class="btn-apagar-campo btn-apagar-campo--textarea"
                        data-campo="descricao" data-label="descrição" title="Apagar">🗑</button>
            </div>
        </div>

        <div class="acoes">
            <button type="submit" class="btn-primary">Salvar Alterações</button>
            <a href="perfil_ong.php" class="btn-secondary">Cancelar</a>
        </div>
    </form>

    <div class="zona-perigo">
        <h3>⚠️ Encerrar conta da ONG</h3>
        <p>
            Ao solicitar o encerramento, os <strong>dados da instituição serão todos removidos</strong>
            (nome, e-mail, endereço, descrição e credenciais). O histórico de distribuições é
            mantido de forma anônima para fins de controle interno, conforme a LGPD.
        </p>
        <p>O acesso será bloqueado imediatamente e esta ação não poderá ser desfeita.</p>
        <button class="btn-danger" onclick="confirmarExclusaoParcial()">
            Encerrar conta da ONG
        </button>
    </div>

</div>

<script>
const CSRF      = '<?= e($_SESSION['csrf_token']) ?>';
const API_CAMPO = '../../src/api/anonimizar_campo.php';
const API_CONTA = '../../src/api/deletar_conta.php';

document.querySelector('[data-campo="email"]')
    ?.addEventListener('mouseenter', () => {
        document.getElementById('aviso-email').style.display = 'block';
    });
document.querySelector('[data-campo="email"]')
    ?.addEventListener('mouseleave', () => {
        document.getElementById('aviso-email').style.display = 'none';
    });

document.querySelectorAll('.btn-apagar-campo').forEach(btn => {
    btn.addEventListener('click', () => {
        const campo = btn.dataset.campo;
        const label = btn.dataset.label;

        let aviso = `Deseja apagar ${label}?\n\nEsta informação será removida permanentemente do perfil da ONG.`;
        if (campo === 'email') {
            aviso = `Deseja apagar o e-mail?\n\n⚠️ ATENÇÃO: sem e-mail a ONG não conseguirá fazer login novamente.\n\nTem certeza?`;
        }
        if (campo === 'nome') {
            aviso = `Deseja apagar o nome da ONG?\n\nEle será substituído por "ONG Removida".`;
        }

        if (!confirm(aviso)) return;

        btn.disabled = true;
        btn.textContent = '⏳';

        fetch(API_CAMPO, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'csrf_token=' + encodeURIComponent(CSRF)
                + '&campo=' + encodeURIComponent(campo)
        })
        .then(r => r.json())
        .then(json => {
            if (json.ok) {
                const input = document.getElementById(campo)
                           || document.getElementById(campo === 'nome' ? 'nome_receptor' : campo);
                if (input) {
                    input.value = '';
                    input.classList.add('campo-apagado');
                }
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

function confirmarExclusaoParcial() {
    if (!confirm('Deseja encerrar a conta da ONG?\n\nTodos os dados da instituição serão removidos, mas o histórico de distribuições será mantido de forma anônima.')) return;
    if (!confirm('Confirme: o acesso será bloqueado imediatamente e esta ação não pode ser desfeita. Continuar?')) return;

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
            alert('Conta encerrada. Os dados da ONG foram removidos.');
            window.location.href = 'login_ong.php';
        } else {
            alert('Erro: ' + json.msg);
        }
    })
    .catch(() => alert('Erro de conexão. Tente novamente.'));
}
</script>
</body>
</html>