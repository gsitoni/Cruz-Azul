const senha = document.getElementById("nova_senha");
const confirmar = document.getElementById("confirmacao_senha");
const erro = document.getElementById("erro");

const condTamanho = document.getElementById("condicao_tamanho");
const condEspecial = document.getElementById("condicao_especial");
const condNumero = document.getElementById("condicao_numero");
const condSequencia = document.getElementById("condicao_sequencia");

const form = document.querySelector(".form");


erro.style.display = "none";

function validarSenha() {
    const valor = senha.value;
    const temTamanho = valor.length >= 10;
    const temEspecial = /[@#$%]/.test(valor);
    const temNumero = /\d/.test(valor);
    const temSequencia = /(abcd|1234)/i.test(valor);

    atualizar(condTamanho, temTamanho);
    atualizar(condEspecial, temEspecial);
    atualizar(condNumero, temNumero);
    atualizar(condSequencia, !temSequencia);

    return temTamanho && temEspecial && temNumero && !temSequencia;
}

function atualizar(elemento, valido) {
    elemento.style.color = valido ? "green" : "red";
}

function validarConfirmacao() {
    if (confirmar.value === "") {
        erro.style.display = "none";
        return false;
    }

    if (senha.value !== confirmar.value) {
        erro.style.display = "block";
        return false;
    } else {
        erro.style.display = "none";
        return true;
    }
}

senha.addEventListener("input", () => {
    validarSenha();
    validarConfirmacao();
});

confirmar.addEventListener("input", validarConfirmacao);

function voltar() {
    window.history.back();
}

form.addEventListener("submit", function(event) {

    const senhaValida = validarSenha();
    const confirmacaoValida = validarConfirmacao();

    if (!senhaValida || !confirmacaoValida) {
        event.preventDefault();
        alert("Corrija os erros antes de continuar!");
        return;
    }

    event.preventDefault();
    alert("Senha redefinida com sucesso!");

    window.location.href = "login.html";
});