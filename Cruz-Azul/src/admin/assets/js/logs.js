document.addEventListener("DOMContentLoaded", () => {

    const form = document.querySelector(".filters");
    const tabela = document.querySelector("tbody");

    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        const formData = new FormData(form);
        const params = new URLSearchParams(formData);

        try {
            const response = await fetch("logs.php?" + params.toString(), {
                method: "GET",
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                }
            });

            const data = await response.json();

            atualizarTabela(data);

        } catch (error) {
            console.error("Erro:", error);
        }
    });

    function atualizarTabela(logs) {
        tabela.innerHTML = "";

        if (logs.length === 0) {
            tabela.innerHTML = `
                <tr>
                    <td colspan="5">Nenhum log encontrado</td>
                </tr>
            `;
            return;
        }

        logs.forEach(log => {

            const badgeClass = getBadgeClass(log.nivel);

            const linha = `
                <tr>
                    <td>${escapeHTML(log.data)}</td>
                    <td>${escapeHTML(log.usuario)}</td>
                    <td>${escapeHTML(log.acao)}</td>
                    <td>${escapeHTML(log.ip)}</td>
                    <td>
                        <span class="badge ${badgeClass}">
                            ${log.nivel.toUpperCase()}
                        </span>
                    </td>
                </tr>
            `;

            tabela.innerHTML += linha;
        });
    }

    function getBadgeClass(nivel) {
        switch (nivel) {
            case "info": return "info";
            case "alerta": return "alerta";
            case "critico": return "critico";
            default: return "info";
        }
    }

    function escapeHTML(str) {
        return str.replace(/[&<>"']/g, function(m) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            }[m];
        });
    }

});