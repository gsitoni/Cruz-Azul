<?php

require_once __DIR__ . '/conf.php';

function telegram_enviar_otp(string $chat_id, string $otp): bool
{
    $url = 'https://api.telegram.org/bot' . TELEGRAM_TOKEN . '/sendMessage';

    $corpo = json_encode([
        'chat_id'    => $chat_id,
        'parse_mode' => 'HTML',
        'text'       =>
            "🔐 <b>Cruz Azul Admin</b>\n\n" .
            "Seu código de acesso:\n" .
            "<code>{$otp}</code>\n\n" .
            "⏱ Válido por 5 minutos.\n" .
            "Se não foi você, ignore esta mensagem.",
    ]);

    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'content' => $corpo,
            'timeout' => 5,
        ],
    ]);

    $resp = @file_get_contents($url, false, $ctx);
    if ($resp === false) return false;

    $json = json_decode($resp, true);
    return !empty($json['ok']);
}