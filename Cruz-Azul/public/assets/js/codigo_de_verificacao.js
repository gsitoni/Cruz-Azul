const form   = document.getElementById("form_codigo");
const inputs = document.querySelectorAll(".inputs .input");
const hidden = document.getElementById("codigo_final");
 
const regex = /^[0-9]$/;
 
function voltar() {
    window.history.back();
}
 
inputs.forEach((input, index) => {
 
    input.addEventListener("input", () => {
        input.value = input.value.replace(/\D/g, ''); // só números
 
        if (input.value && index < inputs.length - 1) {
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
            alert("Preencha todos os 6 dígitos corretamente!");
            input.focus();
            return;
        }
        codigo += input.value;
    }
 
    // Coloca o código montado no campo hidden antes de enviar
    hidden.value = codigo;
});