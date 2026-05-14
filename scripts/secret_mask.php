<?php
require __DIR__ . '/../config/secret_manager.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Use via CLI.' . PHP_EOL);
}

$plain = $argv[1] ?? '';
if ($plain === '') {
    fwrite(STDERR, "Uso: php scripts/secret_mask.php \"segredo\"" . PHP_EOL);
    exit(1);
}

echo caSecretMask($plain) . PHP_EOL;
