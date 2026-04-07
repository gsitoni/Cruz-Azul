const form = document.getElementById("form_codigo");
const inputs = document.querySelectorAll(".inputs .input");

const regex = /^[A-Z0-9]$/;


function voltar() {
    window.history.back();
}

inputs.forEach((input, index) => {

    input.addEventListener("input", () => {
        let valor = input.value.toUpperCase();

        if (!regex.test(valor)) {
            input.value = "";
            return;
        }

        input.value = valor;

        if (valor && index < inputs.length - 1) {
            inputs[index + 1].focus();
        }
    });

  
    input.addEventListener("keydown", (e) => {
        if (e.key === "Backspace" && !input.value && index > 0) {
            inputs[index - 1].focus();
        }
    });

});

form.addEventListener("submit", function(event) {

    let codigo = "";

    for (let input of inputs) {
        if (!regex.test(input.value)) {
            event.preventDefault();

            alert("Preencha todos os campos corretamente!");

            input.focus();
            return;
        }

        codigo += input.value;
    }

    console.log("Código digitado:", codigo);

    event.preventDefault();
    alert("Código válido!");

    window.location.href = "redefinicao_de_senha.html";
});