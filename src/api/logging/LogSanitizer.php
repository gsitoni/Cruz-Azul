<?php

final class LogSanitizer
{
    private const SENSITIVE_KEYS = [
        'senha', 'password', 'pass', 'token', 'secret', 'chave', 'api_key',
        'authorization', 'cookie', 'g-recaptcha-response', 'codigo'
    ];

    public static function text(?string $value, int $limit = 2000): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/', ' ', $value) ?? '';
        $value = str_replace(["\r", "\n", "\t"], ' ', $value);
        $value = trim($value);
        $value = self::maskSensitiveText($value);

        if (strlen($value) > $limit) {
            return substr($value, 0, $limit) . '...';
        }

        return $value;
    }

    public static function mask(?string $value, int $visibleStart = 6, int $visibleEnd = 4): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = self::text($value, 255) ?? '';
        $length = strlen($value);

        if ($length <= ($visibleStart + $visibleEnd)) {
            return str_repeat('*', max(4, $length));
        }

        return substr($value, 0, $visibleStart)
            . str_repeat('*', min(16, $length - $visibleStart - $visibleEnd))
            . substr($value, -$visibleEnd);
    }

    public static function array(array $data, int $depth = 0): array
    {
        if ($depth > 4) {
            return ['_truncated' => true];
        }

        $clean = [];
        foreach ($data as $key => $value) {
            $keyString = strtolower((string) $key);

            if (self::isSensitiveKey($keyString)) {
                $clean[$key] = '[MASKED]';
                continue;
            }

            if (is_array($value)) {
                $clean[$key] = self::array($value, $depth + 1);
            } elseif (is_scalar($value) || $value === null) {
                $clean[$key] = self::text((string) $value, 500);
            } else {
                $clean[$key] = '[UNSUPPORTED]';
            }
        }

        return $clean;
    }

    private static function isSensitiveKey(string $key): bool
    {
        foreach (self::SENSITIVE_KEYS as $needle) {
            if (str_contains($key, $needle)) {
                return true;
            }
        }

        return false;
    }

    private static function maskSensitiveText(string $value): string
    {
        $patterns = [
            '/\b(password|senha|token|secret|api_key|authorization|cookie|chave)\s*[:=]\s*([^\s;&]+)/i',
            '/\b(Bearer)\s+[A-Za-z0-9._\-]+/i',
        ];

        $value = preg_replace($patterns[0], '$1=[MASKED]', $value) ?? $value;
        $value = preg_replace($patterns[1], '$1 [MASKED]', $value) ?? $value;

        return $value;
    }
}
