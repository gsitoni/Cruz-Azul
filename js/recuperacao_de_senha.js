let botao_voltar = document.querySelector("#botao_voltar").addEventListener("click", function() {
    window.location.href = "../pages/recuperacao_de_senha";
});

let botao_avancar = document.querySelector("#botao_avancar").addEventListener("click", function() {
    window.location.href = "http:///Cruz-Azul/pages/codigo_de_recuperacao";
});

function validar() {
    const valor = document.getElementById("input");
    const regex = /^(email)@(dominio)$/;
    if (regex.test(valor)) {
        alert("Válido");
    }
    else {
        alert("Inválido");
    }
}
