<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login do Admin - Cruz Azul</title>
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
    <main class="login-shell">
        <section class="login-panel login-panel--brand">
            <span class="eyebrow">Painel administrativo</span>
            <h1>Cruz Azul Admin</h1>
            <p>
                Acesso sem senha. Um código será enviado via Telegram para autenticação.
            </p>
        </section>

        <section class="login-panel login-panel--form">
            <div class="form-header">
                <h2>Entrar no admin</h2>
                <p>Informe seu e-mail para receber o código no Telegram.</p>
            </div>

            <b><p id="email">E-mail</p></b>

            <form id="formLoginAdmin" novalidate>

                <!-- ETAPA 1 -->
                <div id="etapaEmail">
                    <input type="email" id="email" name="email" required>
                    <div class="erro-campo" id="erroEmail">Informe um e-mail válido.</div>

                    <button type="submit" id="btnEntrar">
                        Enviar código
                    </button>
                </div>

                <!-- ETAPA 2 -->
                <div id="etapaCodigo" style="display:none;">
                    <label for="codigo">Código recebido</label>
                    <input type="text" id="codigo" name="codigo" placeholder="Ex: 123456">
                    
                    <button type="button" id="btnValidar">
                        Validar código
                    </button>
                </div>

                <a id="link_cadastro" href="./cadastro_admin.php">Cadastrar administrador</a>

                <div class="msg" id="mensagem"></div>

            </form>
        </section>
    </main>

    <script>
        const campoEmail = document.getElementById('email');
        const campoCodigo = document.getElementById('codigo');
        const msgDiv = document.getElementById('mensagem');

        const etapaEmail = document.getElementById('etapaEmail');
        const etapaCodigo = document.getElementById('etapaCodigo');

        document.getElementById('formLoginAdmin').addEventListener('submit', async (e) => {
            e.preventDefault();

            const dados = new FormData();
            dados.append('email', campoEmail.value);

            const response = await fetch('login.php', {
                method: 'POST',
                body: dados
            });

            const json = await response.json();

            if (json.ok) {
                msgDiv.textContent = "Código enviado no Telegram!";
                etapaEmail.style.display = "none";
                etapaCodigo.style.display = "block";
            } else {
                msgDiv.textContent = json.msg;
            }
        });

        document.getElementById('btnValidar').addEventListener('click', async () => {
            const dados = new FormData();
            dados.append('codigo', campoCodigo.value);

            const response = await fetch('verificar_codigo.php', {
                method: 'POST',
                body: dados
            });

            const json = await response.json();

            if (json.ok) {
                window.location.href = "../index.php";
            } else {
                msgDiv.textContent = json.msg;
            }
        });
    </script>
</body>
</html>