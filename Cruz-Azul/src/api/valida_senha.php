<?php


// Impede o acesso direto a este arquivo via URL
if (basename($_SERVER['PHP_SELF']) == 'valida_senha.php') {
    exit('Acesso direto não permitido.');
}

function validarSenhaForte($senha) {
    // Definição dos critérios
    $tamanho     = strlen($senha) >= 12;
    $maiuscula   = preg_match('/[A-Z]/', $senha);
    $minuscula   = preg_match('/[a-z]/', $senha);
    $numero      = preg_match('/[0-9]/', $senha);
    $especial    = preg_match('/[!@#$%^&*()\-_=+{};:,<.>|]/', $senha);

    // Validações 
    if (!$tamanho) {
        return "A senha deve ter pelo menos 12 caracteres.";
    }
    
    if (!$maiuscula || !$minuscula) {
        return "A senha deve conter letras maiúsculas e minúsculas.";
    }
    
    if (!$numero) {
        return "A senha deve conter pelo menos um número.";
    }
    
    if (!$especial) {
        return "A senha deve conter pelo menos um caractere especial (ex: @, #, $, %).";
    }

    return true; 
}

function temSequencia($senha, $tamanhoMin = 4) {
    $senha = strtolower($senha);
    $len = strlen($senha);

    for ($i = 0; $i <= $len - $tamanhoMin; $i++) {
        $seq = substr($senha, $i, $tamanhoMin);

        $crescente = true;
        $decrescente = true;

        for ($j = 0; $j < $tamanhoMin - 1; $j++) {
            if (ord($seq[$j]) + 1 != ord($seq[$j + 1])) {
                $crescente = false;
            }
            if (ord($seq[$j]) - 1 != ord($seq[$j + 1])) {
                $decrescente = false;
            }
        }

        if ($crescente || $decrescente) {
            return true;
        }
    }

    return false;
}
?>
