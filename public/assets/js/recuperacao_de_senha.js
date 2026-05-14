const form = document.getElementById("form_recuperacao");
const emailInput = document.getElementById("email_recuperacao");

const regexEmail = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

function voltar() {
    window.history.back();
}

form.addEventListener("submit", function(event) {
    const email = emailInput.value.trim();
    if (!regexEmail.test(email)) {
        event.preventDefault();
        alert("Digite um email válido! Ex: nome123@gmail.com");
        emailInput.focus();
        return;
    }

    if (grecaptcha.getResponse() === '') {
        event.preventDefault();
        alert("Confirme o CAPTCHA");
        return;
    }
});