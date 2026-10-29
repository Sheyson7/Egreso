const bookingForm = document.querySelector('#booking-form');
const formMessage = document.querySelector('#form-message');

bookingForm?.addEventListener('submit', (event) => {
    event.preventDefault();

    if (!bookingForm.reportValidity()) return;

    const formData = new FormData(bookingForm);
    const date = new Date(`${formData.get('date')}T12:00:00`).toLocaleDateString('es-UY', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });

    formMessage.textContent = `Vista previa: ${formData.get('court')} · ${date} · ${formData.get('time')}. Esta consulta todavía no se envió.`;
    formMessage.classList.add('is-visible');
});

const menuToggle = document.querySelector('.menu-toggle');
const mainNav = document.querySelector('.main-nav');

menuToggle?.addEventListener('click', () => {
    const isOpen = menuToggle.getAttribute('aria-expanded') === 'true';
    menuToggle.setAttribute('aria-expanded', String(!isOpen));
    menuToggle.setAttribute('aria-label', isOpen ? 'Abrir menú' : 'Cerrar menú');
    mainNav?.classList.toggle('is-open', !isOpen);
});

mainNav?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
        menuToggle?.setAttribute('aria-expanded', 'false');
        menuToggle?.setAttribute('aria-label', 'Abrir menú');
        mainNav.classList.remove('is-open');
    });
});
