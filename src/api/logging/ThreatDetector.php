<?php

final class ThreatDetector
{
    public static function analyze(array $event): array
    {
        $signals = [];
        $risk = 'LOW';

        $route = (string) ($event['route'] ?? '');
        $action = strtoupper((string) ($event['action'] ?? ''));
        $status = strtoupper((string) ($event['status'] ?? ''));
        $payload = strtolower(json_encode($event['context'] ?? [], JSON_UNESCAPED_SLASHES) ?: '');
        $haystack = strtolower($route . ' ' . $payload . ' ' . (string) ($event['user_agent'] ?? ''));

        if (preg_match('/(\bunion\b.*\bselect\b|sleep\s*\(|benchmark\s*\(|or\s+1\s*=\s*1|--|\/\*|\bxp_)/i', $haystack)) {
            $signals[] = 'SQL_INJECTION_PATTERN';
            $risk = 'HIGH';
        }

        if (preg_match('/(\.\.\/|\.\.\\\\|%2e%2e|etc\/passwd|boot\.ini|win\.ini)/i', $haystack)) {
            $signals[] = 'PATH_TRAVERSAL_PATTERN';
            $risk = 'HIGH';
        }

        if (str_contains($action, 'LOGIN') && $status === 'FAIL') {
            $signals[] = 'LOGIN_FAILURE';
            $failures = (int) ($_SESSION['security_login_failures'] ?? 0) + 1;
            $_SESSION['security_login_failures'] = $failures;

            if ($failures >= 5) {
                $signals[] = 'BRUTE_FORCE_SUSPECTED';
                $risk = 'HIGH';
            }
        }

        if ($status === 'SUCCESS' && str_contains($action, 'LOGIN')) {
            $_SESSION['security_login_failures'] = 0;
        }

        if (str_contains($action, 'ACESSO ADMIN') || str_contains($route, '/admin/')) {
            $signals[] = 'ADMIN_AREA_ACCESS';
        }

        if (str_contains($action, 'BLOQUEADO') || str_contains($action, 'DESBLOQUEADO')) {
            $signals[] = 'PRIVILEGE_OR_STATUS_CHANGE';
            $risk = self::maxRisk($risk, 'MEDIUM');
        }

        if (str_contains($action, 'EXCLUIDO') || str_contains($action, 'EXCLUÍDO') || str_contains($action, 'DELETE')) {
            $signals[] = 'DESTRUCTIVE_ACTION';
            $risk = self::maxRisk($risk, 'HIGH');
        }

        if (str_contains($action, 'TOKEN') && $status === 'FAIL') {
            $signals[] = 'INVALID_TOKEN';
            $risk = self::maxRisk($risk, 'MEDIUM');
        }

        $securityEvent = $risk !== 'LOW' || in_array('BRUTE_FORCE_SUSPECTED', $signals, true);

        return [
            'security_event' => $securityEvent,
            'risk' => $risk,
            'signals' => array_values(array_unique($signals)),
        ];
    }

    private static function maxRisk(string $current, string $candidate): string
    {
        $rank = ['LOW' => 1, 'MEDIUM' => 2, 'HIGH' => 3, 'CRITICAL' => 4];
        return ($rank[$candidate] ?? 0) > ($rank[$current] ?? 0) ? $candidate : $current;
    }
}
