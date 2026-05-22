<?php

require_once __DIR__ . '/LogSanitizer.php';
require_once __DIR__ . '/ThreatDetector.php';
require_once __DIR__ . '/LogFormatter.php';
require_once __DIR__ . '/FileLogWriter.php';

final class SecurityLogger
{
    private const VALID_SEVERITIES = ['DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL'];

    public static function register(PDO $pdo, array $input): void
    {
        $event = self::buildEvent($input);
        $threat = ThreatDetector::analyze($event);
        $event['threat'] = $threat;

        if ($threat['security_event'] && !in_array($event['severity'], ['ERROR', 'CRITICAL'], true)) {
            $event['severity'] = $threat['risk'] === 'HIGH' ? 'CRITICAL' : 'WARNING';
            $event['category'] = 'SECURITY';
        }

        if ($event['severity'] === 'CRITICAL') {
            $event['alert_ready'] = true;
            $event['context']['critical_context'] = [
                'headers' => self::safeHeaders(),
                'query' => LogSanitizer::array($_GET ?? []),
                'post' => LogSanitizer::array($_POST ?? []),
            ];
        }

        try {
            (new FileLogWriter())->write($event);
        } catch (Throwable $e) {
            error_log('FileLogWriter: ' . LogSanitizer::text($e->getMessage()));
        }

        self::persistDatabaseSummary($pdo, $event, $input);
    }

    private static function buildEvent(array $input): array
    {
        $user = $_SESSION['usuario'] ?? [];
        $context = $input['context'] ?? [];
        $responsibleEmail = (string) ($context['email'] ?? $user['email'] ?? '');
        $responsibleLogin = (string) ($user['nome'] ?? $responsibleEmail ?: 'guest');
        $severity = strtoupper((string) ($input['severity'] ?? 'INFO'));
        $category = self::normalizeCategory((string) ($input['category'] ?? 'SYSTEM'));
        $started = (float) ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));

        $event = [
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'event_id' => self::eventId(),
            'correlation_id' => self::correlationId(),
            'severity' => in_array($severity, self::VALID_SEVERITIES, true) ? $severity : 'INFO',
            'category' => $category,
            'user' => LogSanitizer::text($responsibleLogin, 120),
            'user_login' => LogSanitizer::text($responsibleLogin, 120),
            'user_email' => LogSanitizer::text($responsibleEmail !== '' ? $responsibleEmail : 'guest', 160),
            'user_id' => $input['user_id'] ?? ($user['id_usuario'] ?? null),
            'ip' => self::clientIp(),
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'route' => LogSanitizer::text(parse_url($_SERVER['REQUEST_URI'] ?? 'CLI', PHP_URL_PATH) ?: 'CLI', 300),
            'endpoint' => LogSanitizer::text($_SERVER['SCRIPT_NAME'] ?? 'CLI', 300),
            'action' => LogSanitizer::text((string) ($input['action'] ?? 'SYSTEM_EVENT'), 180),
            'status' => self::inferStatus($input),
            'reason' => LogSanitizer::text($input['reason'] ?? $input['description'] ?? null, 500),
            'user_agent' => LogSanitizer::text($_SERVER['HTTP_USER_AGENT'] ?? 'CLI', 500),
            'session_token' => LogSanitizer::mask(session_id() ?: null),
            'execution_time_ms' => (int) round((microtime(true) - $started) * 1000),
            'context' => LogSanitizer::array($context),
            'target' => [
                'table' => LogSanitizer::text($input['table'] ?? null, 80),
                'reference_id' => $input['reference_id'] ?? null,
            ],
            'siem' => [
                'schema' => 'cruz-azul.audit.v1',
                'export_ready' => true,
            ],
        ];

        return $event;
    }

    private static function persistDatabaseSummary(PDO $pdo, array $event, array $input): void
    {
        $description = json_encode([
            'event_id' => $event['event_id'],
            'user_login' => $event['user_login'],
            'user_email' => $event['user_email'],
            'status' => $event['status'],
            'reason' => $event['reason'],
            'route' => $event['route'],
            'method' => $event['method'],
            'correlation_id' => $event['correlation_id'],
            'execution_time_ms' => $event['execution_time_ms'],
            'risk' => $event['threat']['risk'] ?? 'LOW',
            'signals' => $event['threat']['signals'] ?? [],
            'context' => $event['context'],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        try {
            $stmt = $pdo->prepare("
                INSERT INTO logs_sistema (
                    id_usuario, tipo, categoria, acao, descricao, tabela_afetada,
                    id_referencia, ip_origem, user_agent
                ) VALUES (
                    :id_usuario, :tipo, :categoria, :acao, :descricao, :tabela_afetada,
                    :id_referencia, :ip_origem, :user_agent
                )
            ");

            $stmt->execute([
                ':id_usuario' => $event['user_id'],
                ':tipo' => self::dbSeverity($event['severity']),
                ':categoria' => self::dbCategory($event['category'], (string) ($input['category'] ?? '')),
                ':acao' => $event['action'],
                ':descricao' => $description,
                ':tabela_afetada' => $event['target']['table'],
                ':id_referencia' => $event['target']['reference_id'],
                ':ip_origem' => $event['ip'],
                ':user_agent' => $event['user_agent'],
            ]);
        } catch (PDOException $e) {
            error_log('SecurityLogger DB: ' . LogSanitizer::text($e->getMessage()));
        }
    }

    private static function normalizeCategory(string $category): string
    {
        return match (strtoupper($category)) {
            'LOGIN', 'AUTH' => 'AUTH',
            'SEGURANCA', 'SECURITY' => 'SECURITY',
            'USUARIO', 'ONG', 'ADMIN' => 'ADMIN',
            'SISTEMA', 'SYSTEM' => 'SYSTEM',
            'AUDIT' => 'AUDIT',
            'API' => 'API',
            'DATABASE' => 'DATABASE',
            'ACCESS' => 'ACCESS',
            'ERROR' => 'ERROR',
            'CRITICAL' => 'CRITICAL',
            default => 'SYSTEM',
        };
    }

    private static function dbCategory(string $category, string $original): string
    {
        $original = strtoupper($original);
        $allowed = ['LOGIN', 'CADASTRO', 'DOACAO', 'ESTOQUE', 'DISTRIBUICAO', 'ONG', 'VOLUNTARIO', 'USUARIO', 'SISTEMA', 'SEGURANCA'];

        if (in_array($original, $allowed, true)) {
            return $original;
        }

        return match ($category) {
            'AUTH', 'ACCESS' => 'LOGIN',
            'SECURITY', 'CRITICAL' => 'SEGURANCA',
            'ADMIN', 'AUDIT' => 'USUARIO',
            default => 'SISTEMA',
        };
    }

    private static function dbSeverity(string $severity): string
    {
        return in_array($severity, ['INFO', 'WARNING', 'ERROR', 'CRITICAL'], true) ? $severity : 'INFO';
    }

    private static function inferStatus(array $input): string
    {
        if (!empty($input['status'])) {
            return strtoupper((string) $input['status']);
        }

        $severity = strtoupper((string) ($input['severity'] ?? 'INFO'));
        $text = strtolower((string) (($input['action'] ?? '') . ' ' . ($input['description'] ?? '')));

        if (in_array($severity, ['WARNING', 'ERROR', 'CRITICAL'], true) || preg_match('/falh|negad|erro|bloque|incorret|expirad|inv[aá]lid/', $text)) {
            return 'FAIL';
        }

        return 'SUCCESS';
    }

    private static function eventId(): string
    {
        return 'EVT-' . strtoupper(bin2hex(random_bytes(3)));
    }

    private static function correlationId(): string
    {
        if (empty($_SERVER['HTTP_X_CORRELATION_ID'])) {
            $_SERVER['HTTP_X_CORRELATION_ID'] = 'REQ-' . strtoupper(bin2hex(random_bytes(8)));
        }

        return LogSanitizer::text((string) $_SERVER['HTTP_X_CORRELATION_ID'], 80) ?? '';
    }

    private static function clientIp(): ?string
    {
        return LogSanitizer::text($_SERVER['REMOTE_ADDR'] ?? null, 80);
    }

    private static function safeHeaders(): array
    {
        return LogSanitizer::array([
            'accept' => $_SERVER['HTTP_ACCEPT'] ?? null,
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? null,
            'referer' => $_SERVER['HTTP_REFERER'] ?? null,
            'x_forwarded_for' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
        ]);
    }
}
