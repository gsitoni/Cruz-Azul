<?php
// ============================================================
//  redefine_dados.php  –  src/api/
//  AVISO: Este arquivo era legado (mysqli + sessão antiga).
//  A troca de senha agora é feita por atualizar_usuario.php
//  (perfil) e redefinicao_de_senha.php (recuperação).
//  Mantido apenas como fallback — redireciona para o fluxo correto.
// ============================================================
session_start();
 
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
 
if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['id_usuario'])) {
    header('Location: ../../public/pages/login.php');
    exit();
}
 
// Redireciona para o perfil onde a troca de senha está implementada
header('Location: ../../public/pages/editar_perfil.php');
exit();
 