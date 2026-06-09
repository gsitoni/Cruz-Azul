<?php

// Gera 32 bytes aleatórios (256 bits)
$key = random_bytes(32);

// Converte para hexadecimal para visualização
$hexKey = bin2hex($key);

echo "CHAVE GERADA:\n";
echo $hexKey;

?>