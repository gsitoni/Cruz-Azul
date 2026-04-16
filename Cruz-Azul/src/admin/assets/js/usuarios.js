document.addEventListener("DOMContentLoaded", () => {

    const formFiltro = document.querySelector(".filters");
    const tabela = document.querySelector("tbody");

    // ==========================
    // FILTRO AJAX
    // ==========================
    formFiltro.addEventListener("submit", async (e) => {
        e.preventDefault();

        const formData = new FormData(formFiltro);
        const params = new URLSearchParams(formData);

        try {
            const response = await fetch("usuarios.php?" + params.toString(), {
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                }
            });

            const data = await response.json();
            atualizarTabela(data);

        } catch (err) {
            console.error("Erro:", err);
        }
    });

    // ==========================
    // AÇÕES (delegação de eventos)
    // ==========================
    tabela.addEventListener("click", async (e) => {

        if (e.target.tagName !== "BUTTON") return;

        const btn = e.target;
        const usuario = btn.dataset.usuario;
        const acao = btn.dataset.acao;
        const csrf = document.querySelector('input[name="csrf_token"]').value;

        if (!usuario || !acao) return;

        if (!confirm(`Deseja ${acao} o usuário ${usuario}?`)) return;

        try {
            const formData = new FormData();
            formData.append("usuario", usuario);
            formData.append("acao", acao);
            formData.append("csrf_token", csrf);

            const response = await fetch("usuarios.php", {
                method: "POST",
                body: formData,
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                }
            });

            const result = await response.json();

            alert(result.msg);

            // recarrega lista
            formFiltro.dispatchEvent(new Event("submit"));

        } catch (err) {
            console.error("Erro:", err);
        }
    });

    // ==========================
    // ATUALIZA TABELA
    // ==========================
    function atualizarTabela(usuarios) {
        tabela.innerHTML = "";

        if (usuarios.length === 0) {
            tabela.innerHTML = `<tr><td colspan="6">Nenhum usuário encontrado</td></tr>`;
            return;
        }

        usuarios.forEach(u => {

            const linha = `
                <tr>
                    <td>${escapeHTML(u.nome)}</td>
                    <td>${escapeHTML(u.email)}</td>

                    <td>
                        <span class="badge ${getTipoClass(u.tipo)}">
                            ${u.tipo.toUpperCase()}
                        </span>
                    </td>

                    <td>
                        <span class="badge ${getStatusClass(u.status)}">
                            ${u.status.toUpperCase()}
                        </span>
                    </td>

                    <td>${escapeHTML(u.ultimo)}</td>

                    <td>
                        ${renderBotoes(u)}
                    </td>
                </tr>
            `;

            tabela.innerHTML += linha;
        });
    }

    // ==========================
    // BOTÕES DINÂMICOS
    // ==========================
    function renderBotoes(u) {

        let botoes = "";

        if (u.status === "ativo") {
            botoes += `<button class="btn-bloquear" data-usuario="${u.nome}" data-acao="bloquear">Bloquear</button>`;
        } else {
            botoes += `<button class="btn-ativar" data-usuario="${u.nome}" data-acao="ativar">Ativar</button>`;
        }

        if (u.tipo !== "admin") {
            botoes += `<button class="btn-promover" data-usuario="${u.nome}" data-acao="promover">Promover</button>`;
        }

        return botoes;
    }

    function getTipoClass(tipo) {
        switch (tipo) {
            case "admin": return "admin";
            case "ong": return "ong";
            default: return "comum";
        }
    }

    function getStatusClass(status) {
        return status === "ativo" ? "ativo" : "bloqueado";
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