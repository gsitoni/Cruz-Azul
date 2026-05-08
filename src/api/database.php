<?php
// db.php - Conexao com o banco de dados
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'cruzazul';

/** @var PDO|null $pdo */
$pdo = null;

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die(
            'Erro na conexão' . $e->getMessage()
        );
    }

function colunaExiste(PDO $pdo, string $tabela, string $coluna): bool
{
    static $cache = [];

    $chave = $tabela . '.' . $coluna;
    if (array_key_exists($chave, $cache)) {
        return $cache[$chave];
    }

    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$tabela` LIKE ?");
    $stmt->execute([$coluna]);

    $cache[$chave] = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    return $cache[$chave];
}

function obterColunaPerfilUsuario(PDO $pdo): string
{
    if (colunaExiste($pdo, 'usuario', 'tipo')) {
        return 'tipo';
    }

    if (colunaExiste($pdo, 'usuario', 'permissao')) {
        return 'permissao';
    }

    return 'tipo';
}

function obterValorPerfilDoador(PDO $pdo): string
{
    return obterColunaPerfilUsuario($pdo) === 'permissao' ? 'Doador' : 'doador';
}

function obterValorPerfilAdmin(PDO $pdo): string
{
    return obterColunaPerfilUsuario($pdo) === 'permissao' ? 'Admin' : 'admin';
}

function obterSelecaoPerfilUsuario(PDO $pdo, string $alias = 'usuario'): string
{
    $prefixo = $alias !== '' ? $alias . '.' : '';
    $coluna = obterColunaPerfilUsuario($pdo);

    return "{$prefixo}{$coluna} AS tipo";
}
