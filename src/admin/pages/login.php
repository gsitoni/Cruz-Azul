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

            <form id="formLoginAdmin" novalidate>

                <!-- ETAPA 1 -->
                <div id="etapaEmail">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" placeholder="seu@email.com" required>
                    <div class="erro-campo" id="erroEmail">Informe um e-mail válido.</div>

                    <button type="submit" id="btnEntrar">
                        Enviar código via Telegram
                    </button>
                </div>

                <!-- ETAPA 2 -->
                <div id="etapaCodigo" style="display:none;">
                    <label for="codigo">Código recebido no Telegram</label>
                    <input type="text" id="codigo" name="codigo" placeholder="Digite o código de 6 dígitos" maxlength="6" pattern="[0-9]{6}">
                    <div class="erro-campo" id="erroCodigo">Digite um código válido de 6 dígitos.</div>
                    
                    <button type="button" id="btnValidar">
                        Validar código
                    </button>
                    <button type="button" id="btnReenviar" style="margin-top: 10px; background: transparent; color: var(--primary); border: 1px solid var(--primary);">
                        Reenviar código
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
        const erroEmail = document.getElementById('erroEmail');
        const erroCodigo = document.getElementById('erroCodigo');
        const btnEntrar = document.getElementById('btnEntrar');
        const btnValidar = document.getElementById('btnValidar');
        const btnReenviar = document.getElementById('btnReenviar');

        const etapaEmail = document.getElementById('etapaEmail');
        const etapaCodigo = document.getElementById('etapaCodigo');

        // Validação de e-mail em tempo real
        campoEmail.addEventListener('input', () => {
            validarEmail();
        });

        // Validação de código em tempo real
        campoCodigo.addEventListener('input', () => {
            validarCodigo();
        });

        function validarEmail() {
            const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (!emailRegex.test(campoEmail.value)) {
                campoEmail.classList.add('invalido');
                erroEmail.classList.add('visivel');
                return false;
            } else {
                campoEmail.classList.remove('invalido');
                erroEmail.classList.remove('visivel');
                return true;
            }
        }

        function validarCodigo() {
            const codigoRegex = /^[0-9]{6}$/;
            if (!codigoRegex.test(campoCodigo.value)) {
                campoCodigo.classList.add('invalido');
                erroCodigo.classList.add('visivel');
                return false;
            } else {
                campoCodigo.classList.remove('invalido');
                erroCodigo.classList.remove('visivel');
                return true;
            }
        }

        function mostrarMsg(texto, tipo) {
            msgDiv.textContent = texto;
            msgDiv.className = 'msg ' + tipo;
        }

        document.getElementById('formLoginAdmin').addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!validarEmail()) {
                mostrarMsg('Por favor, corrija o e-mail antes de continuar.', 'erro');
                return;
            }

            btnEntrar.disabled = true;
            btnEntrar.textContent = 'Enviando...';

            const dados = new FormData();
            dados.append('email', campoEmail.value);

            try {
                const response = await fetch('enviar_codigo.php', {
                    method: 'POST',
                    body: dados
                });

                const json = await response.json();

                if (json.ok) {
                    mostrarMsg("Código enviado no Telegram!", 'sucesso');
                    etapaEmail.style.display = "none";
                    etapaCodigo.style.display = "block";
                    campoCodigo.focus();
                } else {
                    mostrarMsg(json.msg, 'erro');
                }
            } catch (err) {
                mostrarMsg('Erro de conexão. Tente novamente.', 'erro');
            } finally {
                btnEntrar.disabled = false;
                btnEntrar.textContent = 'Enviar código via Telegram';
            }
        });

        btnValidar.addEventListener('click', async () => {
            if (!validarCodigo()) {
                mostrarMsg('Por favor, digite um código válido de 6 dígitos.', 'erro');
                return;
            }

            btnValidar.disabled = true;
            btnValidar.textContent = 'Validando...';

            const dados = new FormData();
            dados.append('codigo', campoCodigo.value);

            try {
                const response = await fetch('verificar_codigo.php', {
                    method: 'POST',
                    body: dados
                });

                const json = await response.json();

                if (json.ok) {
                    mostrarMsg('Autenticação bem-sucedida!', 'sucesso');
                    setTimeout(() => {
                        window.location.href = "../index.php";
                    }, 1000);
                } else {
                    mostrarMsg(json.msg, 'erro');
                }
            } catch (err) {
                mostrarMsg('Erro de conexão. Tente novamente.', 'erro');
            } finally {
                btnValidar.disabled = false;
                btnValidar.textContent = 'Validar código';
            }
        });

        btnReenviar.addEventListener('click', async () => {
            btnReenviar.disabled = true;
            btnReenviar.textContent = 'Reenviando...';

            const dados = new FormData();
            dados.append('email', campoEmail.value);

            try {
                const response = await fetch('enviar_codigo.php', {
                    method: 'POST',
                    body: dados
                });

                const json = await response.json();

                if (json.ok) {
                    mostrarMsg('Código reenviado com sucesso!', 'sucesso');
                } else {
                    mostrarMsg(json.msg, 'erro');
                }
            } catch (err) {
                mostrarMsg('Erro de conexão. Tente novamente.', 'erro');
            } finally {
                btnReenviar.disabled = false;
                btnReenviar.textContent = 'Reenviar código';
            }
        });
    </script>
</body>
</html>