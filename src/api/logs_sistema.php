<?php

function registrarLogSistema(
    PDO $pdo,
    string $tipo,
    string $categoria,
    string $acao,
    ?string $descricao = null,
    ?string $tabelaAfetada = null,
    ?int $idReferencia = null,
    ?int $idUsuario = null
): void {
    $idUsuario ??= isset($_SESSION['usuario']['id_usuario'])
        ? (int) $_SESSION['usuario']['id_usuario']
        : null;

    $ipOrigem = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO logs_sistema (
                id_usuario,
                tipo,
                categoria,
                acao,
                descricao,
                tabela_afetada,
                id_referencia,
                ip_origem,
                user_agent
            ) VALUES (
                :id_usuario,
                :tipo,
                :categoria,
                :acao,
                :descricao,
                :tabela_afetada,
                :id_referencia,
                :ip_origem,
                :user_agent
            )
        ");

        $stmt->execute([
            ':id_usuario' => $idUsuario,
            ':tipo' => strtoupper($tipo),
            ':categoria' => strtoupper($categoria),
            ':acao' => $acao,
            ':descricao' => $descricao,
            ':tabela_afetada' => $tabelaAfetada,
            ':id_referencia' => $idReferencia,
            ':ip_origem' => $ipOrigem,
            ':user_agent' => $userAgent,
        ]);
    } catch (PDOException $e) {
        error_log('registrarLogSistema: ' . $e->getMessage());
    }
}
