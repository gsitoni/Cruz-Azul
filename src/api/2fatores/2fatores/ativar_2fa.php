<?php
session_start();
require_once __DIR__ . '/../../database.php';

// função pra gerar secret
function gerarSecret($tamanho = 16) {
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";
    $secret = '';
    
    for ($i = 0; $i < $tamanho; $i++) {
        $secret .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $secret;
}

// verifica se usuário está logado
if (!isset($_SESSION['usuario']['id_usuario'])) {
    header("Location: ../../../public/pages/login.php");
    exit;
}

$id = $_SESSION['usuario']['id_usuario'];

//  verifica se já tem 2FA ativo
$sql = "SELECT chave_2fa FROM usuario WHERE id_usuario = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

$dados = $stmt->fetch(PDO::FETCH_ASSOC);

// se já tiver secret, usa ele
if (!empty($dados['chave_2fa'])) {
    $secret = $dados['chave_2fa'];
} else {
    // se não tiver, gera e salva
    $secret = gerarSecret();

    $sql = "UPDATE usuario SET chave_2fa = ? WHERE id_usuario = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$secret, $id]);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ativar 2FA</title>
</head>
<body>

<h2>Ativar Autenticação em Dois Fatores</h2>

<p>Escaneie o QR Code no Google Authenticator:</p>

<img src="gerarqr.php?secret=<?php echo urlencode($secret); ?>" alt="QR Code para configurar autenticacao em dois fatores">

<p>Ou use esse código manual:</p>
<b><?php echo $secret; ?></b>

<form action="verificar_2fa.php" method="POST">
    <input type="text" name="codigo" placeholder="Digite o código">
    <button type="submit">Confirmar</button>
</form>

</body>
</html>