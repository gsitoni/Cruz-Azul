document.querySelectorAll("form[method='POST']").forEach((form) => {
    form.addEventListener("submit", (event) => {
        const submitter = event.submitter;
        if (!submitter || !submitter.dataset.acao) {
            return;
        }

        const linha = form.closest("tr");
        const nome = linha ? linha.querySelector("td:nth-child(2)")?.textContent?.trim() : "esta ONG";
        const acao = submitter.dataset.acao === "aprovar" ? "aprovar" : "rejeitar";

        const confirmou = window.confirm(`Deseja ${acao} ${nome}?`);
        if (!confirmou) {
            event.preventDefault();
            return;
        }

        submitter.disabled = true;
        submitter.textContent = "Processando...";
    });
});