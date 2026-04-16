const senha = document.getElementById("nova_senha");
const confirmar = document.getElementById("confirmacao_senha");
const erro = document.getElementById("erro");

const condTamanho = document.getElementById("condicao_tamanho");
const condMaiuscula = document.getElementById("condicao_maiuscula");
const condMinuscula = document.getElementById("condicao_minuscula");
const condNumero = document.getElementById("condicao_numero");
const condEspecial = document.getElementById("condicao_especial");
const condSequencia = document.getElementById("condicao_sequencia");

function temSequenciaFn(senha) {
    const lower = senha.toLowerCase();
    const len = lower.length;
    for (let i = 0; i <= len - 4; i++) {
        const seq = lower.substr(i, 4);
        let crescente = true;
        let decrescente = true;
        for (let j = 0; j < 3; j++) {
            if (seq.charCodeAt(j) + 1 !== seq.charCodeAt(j + 1)) {
                crescente = false;
            }
            if (seq.charCodeAt(j) - 1 !== seq.charCodeAt(j + 1)) {
                decrescente = false;
            }
        }
        if (crescente || decrescente) {
            return true;
        }
    }
    return false;
}


erro.style.display = "none";

function validarSenha() {
    const valor = senha.value;
    const temTamanho = valor.length >= 12;
    const temMaiuscula = /[A-Z]/.test(valor);
    const temMinuscula = /[a-z]/.test(valor);
    const temNumero = /\d/.test(valor);
    const temEspecial = /[!@#$%^&*()\-_=+{};:,<.>|]/.test(valor);
    const temSequencia = temSequenciaFn(valor);

    atualizar(condTamanho, temTamanho);
    atualizar(condMaiuscula, temMaiuscula);
    atualizar(condMinuscula, temMinuscula);
    atualizar(condNumero, temNumero);
    atualizar(condEspecial, temEspecial);
    atualizar(condSequencia, !temSequencia);

    return temTamanho && temMaiuscula && temMinuscula && temNumero && temEspecial && !temSequencia;
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