<?php

require_once __DIR__ . '/logging/SecurityLogger.php';

function registrarLogSistema(
    PDO $pdo,
    string $tipo,
    string $categoria,
    string $acao,
    ?string $descricao = null,
    ?string $tabelaAfetada = null,
    ?int $idReferencia = null,
    ?int $idUsuario = null,
    array $contexto = [],
    ?string $status = null
): void {
    SecurityLogger::register($pdo, [
        'severity' => $tipo,
        'category' => $categoria,
        'action' => $acao,
        'description' => $descricao,
        'reason' => $descricao,
        'table' => $tabelaAfetada,
        'reference_id' => $idReferencia,
        'user_id' => $idUsuario,
        'context' => $contexto,
        'status' => $status,
    ]);
}
