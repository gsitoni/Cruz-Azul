document.addEventListener('DOMContentLoaded', () => {
    
    const sanitize = (str) => {
        if (!str) return "";
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    };

    const tabela = document.getElementById('corpo-tabela');

    fetch('../../../../api/lista_usuarios.php')
        .then(response => {
            if (!response.ok) throw new Error('Falha na autorização do servidor.');
            return response.json();
        })
        .then(data => {
            tabela.innerHTML = ''; 

            data.forEach(user => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${sanitize(user.id)}</td>
                    <td>${sanitize(user.nome)}</td>
                    <td>${sanitize(user.email)}</td>
                    <td><span class="status-tag">${sanitize(user.status)}</span></td>
                `;
                tabela.appendChild(tr);
            });
        })
        .catch(err => {
            console.error('Erro de Segurança:', err);
            tabela.innerHTML = '<tr><td colspan="4" style="color:red; text-align:center;">Sua sessão expirou ou você não tem permissão.</td></tr>';
        });
});