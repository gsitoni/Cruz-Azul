<?php


// Impede o acesso direto a este arquivo via URL
if (basename($_SERVER['PHP_SELF']) == 'valida_senha.php') {
    exit('Acesso direto não permitido.');
}

function validarSenhaForte($senha) {
    // Definição dos critérios
    $tamanho     = strlen($senha) >= 8;
    $maiuscula   = preg_match('/[A-Z]/', $senha);
    $minuscula   = preg_match('/[a-z]/', $senha);
    $numero      = preg_match('/[0-9]/', $senha);
    
    $especial    = preg_match('/[!@#$%^&*()\-_=+{};:,<.>|]/', $senha);

    // Validações 
    if (!$tamanho) {
        return "A senha deve ter pelo menos 8 caracteres.";
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
?>
