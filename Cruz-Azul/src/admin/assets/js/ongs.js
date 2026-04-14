// ==========================
// PEGAR CSRF TOKEN
// ==========================
const csrfToken = document.querySelector('input[name="csrf_token"]')?.value;

// ==========================
// FUNÇÃO BASE FETCH
// ==========================
async function enviarRequisicao(dados) {
    try {
        const response = await fetch("ongs.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: new URLSearchParams(dados)
        });

        return await response.text();

    } catch (erro) {
        console.error("Erro:", erro);
        alert("Erro ao comunicar com o servidor");
    }
}

// ==========================
// APROVAR / REJEITAR ONG
// ==========================
document.querySelectorAll(".ong-actions form").forEach(form => {

    form.addEventListener("submit", async function(e) {
        e.preventDefault();

        const botao = document.activeElement;
        const acao = botao.value;
        const nome = form.querySelector("input[name='nome']").value;

        if (!confirm(`Deseja ${acao} a ONG "${nome}"?`)) return;

        // Feedback visual
        botao.innerText = "Processando...";
        botao.disabled = true;

        await enviarRequisicao({
            csrf_token: csrfToken,
            nome: nome,
            acao: acao
        });

        // Atualizar interface
        const card = form.closest(".ong-card");
        const badge = card.querySelector(".badge");

        if (acao === "aprovar") {
            badge.innerText = "APROVADO";
            badge.style.background = "#e8f5e9";
            badge.style.color = "#2e7d32";
        } else {
            badge.innerText = "REJEITADO";
            badge.style.background = "#ffebee";
            badge.style.color = "#c62828";
        }

        // Remove botões depois da ação
        form.remove();

        // Animação leve
        card.style.opacity = "0.7";

        setTimeout(() => {
            card.style.opacity = "1";
        }, 300);
    });

});

// ==========================
// FILTRO DINÂMICO (SEM RELOAD)
// ==========================
const inputBusca = document.querySelector("input[name='busca']");

if (inputBusca) {
    inputBusca.addEventListener("keyup", () => {
        const termo = inputBusca.value.toLowerCase();
        const cards = document.querySelectorAll(".ong-card");

        cards.forEach(card => {
            const texto = card.innerText.toLowerCase();
            card.style.display = texto.includes(termo) ? "flex" : "none";
        });
    });
}

// ==========================
// FEEDBACK BOTÕES
// ==========================
document.querySelectorAll("button").forEach(btn => {
    btn.addEventListener("click", () => {
        btn.style.opacity = "0.6";
        setTimeout(() => btn.style.opacity = "1", 300);
    });
});