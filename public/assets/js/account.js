const loginForm = document.querySelector('#login-form');
const registerForm = document.querySelector('#register-form');

loginForm?.addEventListener('submit', (event) => {
    event.preventDefault();
    const feedback = document.querySelector('#login-feedback');

    if (!loginForm.reportValidity()) return;

    feedback.textContent = 'El formulario está listo, pero todavía no hay un servicio de autenticación conectado.';
    feedback.classList.add('is-success');
});

registerForm?.addEventListener('submit', (event) => {
    event.preventDefault();
    const feedback = document.querySelector('#register-feedback');

    if (!registerForm.reportValidity()) return;

    const password = registerForm.elements.password.value;
    const confirmation = registerForm.elements.password_confirmation.value;

    if (password !== confirmation) {
        feedback.textContent = 'Las contraseñas no coinciden. Revisá ambos campos.';
        feedback.classList.add('is-error');
        feedback.classList.remove('is-success');
        return;
    }

    feedback.textContent = 'El formulario está listo, pero el registro aún no guarda los datos.';
    feedback.classList.add('is-success');
    feedback.classList.remove('is-error');
});
