<?php

final class LogFormatter
{
    private const ICONS = [
        'DEBUG' => '[.]',
        'INFO' => '[i]',
        'WARNING' => '[!]',
        'ERROR' => '[x]',
        'CRITICAL' => '[!!!]',
    ];

    public static function json(array $event): string
    {
        return json_encode($event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }

    public static function human(array $event): string
    {
        $severity = $event['severity'] ?? 'INFO';
        $icon = self::ICONS[$severity] ?? '[i]';

        return sprintf(
            "%s [%s][%s][%s]\nEvento: %s\nUsuario: %s (#%s)\nIP: %s\nRota: %s %s\nAcao: %s\nStatus: %s\nRisco: %s\nMotivo: %s\nTempo: %sms\n%s\n",
            $icon,
            $severity,
            $event['category'] ?? 'SYSTEM',
            $event['timestamp'] ?? '',
            $event['event_id'] ?? '',
            $event['user'] ?? 'guest',
            $event['user_id'] ?? 'N/A',
            $event['ip'] ?? 'N/A',
            $event['method'] ?? 'CLI',
            $event['route'] ?? 'N/A',
            $event['action'] ?? 'N/A',
            $event['status'] ?? 'N/A',
            $event['threat']['risk'] ?? 'LOW',
            $event['reason'] ?? 'N/A',
            $event['execution_time_ms'] ?? 0,
            str_repeat('-', 80)
        );
    }
}
