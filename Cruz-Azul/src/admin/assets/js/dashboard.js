// ==========================
// CONFIG GLOBAL
// ==========================
const csrfToken = document.querySelector('input[name="csrf_token"]')?.value;

// ==========================
// FUNÇÃO BASE FETCH
// ==========================
async function enviarRequisicao(url, dados) {
    try {
        const response = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: new URLSearchParams(dados)
        });

        const texto = await response.text();
        return texto;

    } catch (erro) {
        console.error("Erro:", erro);
        alert("Erro na comunicação com o servidor");
    }
}

// ==========================
// APROVAR / REJEITAR ONG
// ==========================
document.querySelectorAll("form[action='aprovar_ong.php']").forEach(form => {

    form.addEventListener("submit", async function(e) {
        e.preventDefault();

        const botao = document.activeElement;
        const acao = botao.value;
        const ong = form.querySelector("input[name='ong']").value;

        if (!confirm(`Deseja ${acao} a ONG ${ong}?`)) return;

        const resultado = await enviarRequisicao("aprovar_ong.php", {
            csrf_token: csrfToken,
            ong: ong,
            acao: acao
        });

        alert("Ação realizada com sucesso!");

        // Remove o card da ONG (efeito visual)
        form.closest("article").remove();
    });
});

// ==========================
// GERENCIAR USUÁRIOS
// ==========================
document.querySelectorAll("form[action='gerenciar_usuario.php']").forEach(form => {

    form.addEventListener("submit", async function(e) {
        e.preventDefault();

        const user = form.querySelector("input[name='user']").value;

        if (!confirm(`Alterar status do usuário ${user}?`)) return;

        const resultado = await enviarRequisicao("gerenciar_usuario.php", {
            csrf_token: csrfToken,
            user: user,
            acao: "toggle"
        });

        alert("Usuário atualizado!");

        // Atualiza status visual (simples)
        const linha = form.closest("tr");
        const statusCell = linha.children[2];

        if (statusCell.innerText === "Ativo") {
            statusCell.innerText = "Bloqueado";
        } else {
            statusCell.innerText = "Ativo";
        }
    });
});

// ==========================
// FILTRO DE TABELAS (SIMPLES)
// ==========================
function filtrarTabela(inputSelector, tabelaSelector) {
    const input = document.querySelector(inputSelector);

    if (!input) return;

    input.addEventListener("keyup", () => {
        const termo = input.value.toLowerCase();
        const linhas = document.querySelectorAll(`${tabelaSelector} tbody tr`);

        linhas.forEach(linha => {
            const texto = linha.innerText.toLowerCase();
            linha.style.display = texto.includes(termo) ? "" : "none";
        });
    });
}

// Ativa filtro para usuários e logs
filtrarTabela("input[placeholder='Buscar usuário...']", "table");
filtrarTabela("input[placeholder='Buscar por usuário ou ação...']", "table");

// ==========================
// FEEDBACK VISUAL BOTÕES
// ==========================
document.querySelectorAll("button").forEach(btn => {
    btn.addEventListener("click", () => {
        btn.style.opacity = "0.6";
        setTimeout(() => btn.style.opacity = "1", 300);
    });
});