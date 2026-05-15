<?php
require_once __DIR__ . '/secret_manager.php';

// Insira as chaves reais em texto plano fornecidas pelo painel do Google reCAPTCHA
$site_key_plana = "CHAVE_PUBLICA_AQUI";
$secret_key_plana = "CHAVE_SECRETA_AQUI";

echo "Substitua no recaptcha.php:<br><br>";
echo "\$RECAPTCHA_SITE_KEY = caSecretResolve('" . caSecretMask($site_key_plana) . "');<br>";
echo "\$RECAPTCHA_SECRET_KEY = caSecretResolve('" . caSecretMask($secret_key_plana) . "');<br>";
?>