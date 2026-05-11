<?php
// ============================================================
//  anonimizar_campo.php  –  src/api/anonimizar_campo.php
//  Apaga (anonimiza) um único campo do perfil do usuário.
// ============================================================
session_start();
 
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Content-Type: application/json');
 
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'Método inválido.']);
    exit();
}
 
// --- CSRF ---
if (
    empty($_SESSION['csrf_token']) ||
    empty($_POST['csrf_token'])    ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    echo json_encode(['ok' => false, 'msg' => 'Token inválido.']);
    exit();
}
 
require_once __DIR__ . '/database.php';
 
$campo = $_POST['campo'] ?? '';
 
// ============================================================
//  RAMO DOADOR  ($_SESSION['usuario'])
// ============================================================
if (!empty($_SESSION['usuario']['id_usuario'])) {
 
    $id_usuario = (int) $_SESSION['usuario']['id_usuario'];
 
    // Campos permitidos e seus valores neutros
    // email e senha ficam na tabela `usuario`, os demais em `doador`
    $camposDoador = [
        'nome'            => ['tabela' => 'doador',  'valor' => 'Usuário Removido'],
        'cpf'             => ['tabela' => 'doador',  'valor' => null],
        'telefone'        => ['tabela' => 'doador',  'valor' => '00000000000'],
        'data_nascimento' => ['tabela' => 'doador',  'valor' => null],
        'email'           => ['tabela' => 'usuario', 'valor' => null], // gera email fake
    ];
 
    if (!array_key_exists($campo, $camposDoador)) {
        echo json_encode(['ok' => false, 'msg' => 'Campo inválido.']);
        exit();
    }
 
    try {
        $info   = $camposDoador[$campo];
        $tabela = $info['tabela'];
        $valor  = $info['valor'];
 
        // Email recebe valor fake único
        if ($campo === 'email') {
            $sufixo = bin2hex(random_bytes(8));
            $valor  = 'removido_' . $sufixo . '@excluido.invalid';
        }
 
        if ($tabela === 'doador') {
            $pdo->prepare("UPDATE doador SET {$campo} = ? WHERE id_usuario = ?")
                ->execute([$valor, $id_usuario]);
        } else {
            $pdo->prepare("UPDATE usuario SET {$campo} = ? WHERE id_usuario = ?")
                ->execute([$valor, $id_usuario]);
        }
 
        echo json_encode(['ok' => true, 'campo' => $campo]);
 
    } catch (PDOException $e) {
        error_log("anonimizar_campo.php PDOException: " . $e->getMessage());
        echo json_encode(['ok' => false, 'msg' => 'Erro interno. Tente novamente.']);
    }
 
// ============================================================
//  RAMO ONG  ($_SESSION['ong'])
// ============================================================
} elseif (!empty($_SESSION['ong']['id'])) {
 
    $id_ong = (int) $_SESSION['ong']['id'];
 
    // Campos permitidos para ONG e seus valores neutros
    $camposONG = [
        'nome'         => 'ONG Removida',
        'email'        => null, // gera email fake
        'area_atuacao' => null,
        'localizacao'  => '',
        'cidade'       => null,
        'sigla_estado' => null,
        'endereco'     => null,
        'descricao'    => null,
    ];
 
    if (!array_key_exists($campo, $camposONG)) {
        echo json_encode(['ok' => false, 'msg' => 'Campo inválido.']);
        exit();
    }
 
    try {
        $valor = $camposONG[$campo];
 
        if ($campo === 'email') {
            $sufixo = bin2hex(random_bytes(8));
            $valor  = 'removido_ong_' . $sufixo . '@excluido.invalid';
        }
 
        $pdo->prepare("UPDATE ong SET {$campo} = ?, data_atualizacao = NOW() WHERE id_ong = ?")
            ->execute([$valor, $id_ong]);
 
        echo json_encode(['ok' => true, 'campo' => $campo]);
 
    } catch (PDOException $e) {
        error_log("anonimizar_campo.php PDOException: " . $e->getMessage());
        echo json_encode(['ok' => false, 'msg' => 'Erro interno. Tente novamente.']);
    }
 
} else {
    echo json_encode(['ok' => false, 'msg' => 'Não autorizado.']);
}
 