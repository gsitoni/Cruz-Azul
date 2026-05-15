<?php
require __DIR__ . '/../config/secret_manager.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Use via CLI.' . PHP_EOL);
}

$masked = $argv[1] ?? '';
if ($masked === '') {
    fwrite(STDERR, "Uso: php scripts/secret_unmask.php \"mask:v1:...\"" . PHP_EOL);
    exit(1);
}

echo caSecretUnmask($masked) . PHP_EOL;
