<?php

final class FileLogWriter
{
    private const MAX_BYTES = 5242880;
    private const RETENTION_DAYS = 30;

    public function write(array $event): void
    {
        $dir = $this->logDir();
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $targets = [$this->fileForCategory((string) $event['category'])];
        if (($event['severity'] ?? '') === 'ERROR') {
            $targets[] = 'error.log';
        }
        if (($event['severity'] ?? '') === 'CRITICAL') {
            $targets[] = 'critical.log';
        }
        if (!empty($event['threat']['security_event'])) {
            $targets[] = 'security.log';
        }

        foreach (array_unique($targets) as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            $this->rotateIfNeeded($path);
            file_put_contents($path, LogFormatter::json($event), FILE_APPEND | LOCK_EX);
        }

        $this->cleanup($dir);
    }

    private function fileForCategory(string $category): string
    {
        return match ($category) {
            'AUTH' => 'auth.log',
            'ACCESS' => 'access.log',
            'AUDIT', 'ADMIN' => 'audit.log',
            'ERROR' => 'error.log',
            'CRITICAL' => 'critical.log',
            'SECURITY' => 'security.log',
            default => 'system.log',
        };
    }

    private function logDir(): string
    {
        return dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
    }

    private function rotateIfNeeded(string $path): void
    {
        if (!is_file($path) || filesize($path) < self::MAX_BYTES) {
            return;
        }

        $rotated = $path . '.' . gmdate('YmdHis') . '.gz';
        $content = file_get_contents($path);
        if ($content !== false) {
            file_put_contents($rotated, gzencode($content, 6), LOCK_EX);
            file_put_contents($path, '', LOCK_EX);
        }
    }

    private function cleanup(string $dir): void
    {
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*.gz') ?: [] as $file) {
            if (is_file($file) && filemtime($file) < time() - (self::RETENTION_DAYS * 86400)) {
                unlink($file);
            }
        }
    }
}
