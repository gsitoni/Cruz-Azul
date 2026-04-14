<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cruz Azul ✙</title>
<link rel="stylesheet" href="../assets/css/index.css">
</head>

<body>

<!-- HEADER -->
<header class="header">
    <h1>Cruz Azul ✙</h1>
    <nav>
        <a href="#sobre">Sobre</a>
        <a href="#doar">Doar</a>
        <a href="#funcionalidades">Funcionalidades</a>
        <a href="./escolher_tipo.php">Cadastrar-se</a>
        <a href="./escolher_tipo_login.php">Entrar</a>
    </nav>
</header>

<!-- HERO -->
<section class="hero">
    <div class="hero-text">
        <h2>Transforme vidas com um clique</h2>
        <p>Conectamos doadores a ONGs confiáveis de forma segura e transparente.</p>
        <a href="./escolher_tipo.php" class="btn">Quero Doar</a>
    </div>
</section>

<!-- SOBRE -->
<section id="sobre" class="section">
    <h2>Quem somos</h2>
    <p>
        A Cruz Azul ✙ é uma plataforma que conecta pessoas a causas sociais,
        garantindo segurança, transparência e impacto real nas doações.
    </p>
</section>

<!-- DOADOR -->
<section id="doar" class="section cards">

    <h2>O que você pode fazer</h2>

    <div class="card">
        <h3>💰 Doar</h3>
        <p>Contribua com ONGs verificadas de forma segura.</p>
    </div>

    <div class="card">
        <h3>🔍 Acompanhar</h3>
        <p>Veja para onde sua doação está indo.</p>
    </div>

    <div class="card">
        <h3>🤝 Conectar</h3>
        <p>Encontre causas que realmente importam para você.</p>
    </div>

</section>

<!-- FUNCIONALIDADES -->
<section id="funcionalidades" class="section">

    <h2>Funcionalidades da Plataforma</h2>

    <ul class="features">
        <li>✔ Sistema de autenticação segura</li>
        <li>✔ Logs de segurança e monitoramento</li>
        <li>✔ Controle de usuários e permissões</li>
        <li>✔ Aprovação de ONGs</li>
        <li>✔ Proteção contra ataques (CSRF, força bruta)</li>
    </ul>

</section>


<!-- CARROSSEL -->
<section class="carousel">

    <div class="slides">
        <div class="slide active">💙 Juntos salvamos vidas</div>
        <div class="slide">🌍 Impacto real na sociedade</div>
        <div class="slide">🔐 Segurança em primeiro lugar</div>
    </div>

</section>

<!-- CTA -->
<section class="cta">
    <h2>Faça parte dessa transformação</h2>
    <a href="./escolher_tipo.php" class="btn">Começar agora</a>
</section>

<!-- FOOTER -->
<footer>
    <p>© 2026 Cruz Azul ✙</p>
</footer>

<script>
let index = 0;
const slides = document.querySelectorAll(".slide");

setInterval(() => {
    slides[index].classList.remove("active");
    index = (index + 1) % slides.length;
    slides[index].classList.add("active");
}, 3000);
</script>

</body>
</html>