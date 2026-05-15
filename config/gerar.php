<?php
require_once __DIR__ . '/secret_manager.php';

$emailOriginal = 'cruz.azulttggb@gmail.com';
$senhaOriginal = 'qfen qtww axcx teqm';

$emailMascarado = caSecretMask($emailOriginal);
$senhaMascarada = caSecretMask($senhaOriginal);

echo "<h2>Novas Máscaras Geradas</h2>";

echo "MÁSCARA DO E-MAIL:<br>";
echo "<input type='text' style='width:100%' value='" . $emailMascarado . "' readonly><br><br>";

echo "MÁSCARA DA SENHA:<br>";
echo "<input type='text' style='width:100%' value='" . $senhaMascarada . "' readonly><br>";

echo "<p style='color:red'>Atenção: Após copiar, delete este arquivo por segurança.</p>";