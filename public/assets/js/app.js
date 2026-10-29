const bookingForm = document.querySelector('#booking-form');
const formMessage = document.querySelector('#form-message');
const bookingDate = bookingForm?.elements.date;
const bookingCourt = bookingForm?.elements.court;
const bookingTime = bookingForm?.elements.time;

async function refreshAvailability() {
    if (!bookingDate?.value || !bookingCourt || !bookingTime) return;

    bookingTime.innerHTML = '<option value="" disabled selected>Buscando horarios…</option>';
    try {
        const params = new URLSearchParams({ court: bookingCourt.value, date: bookingDate.value });
        const response = await fetch(`/disponibilidad.php?${params}`, { headers: { Accept: 'application/json' } });
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'No se pudo consultar la disponibilidad.');

        bookingTime.replaceChildren(new Option('Elegí un horario', '', true, true));
        bookingTime.options[0].disabled = true;
        result.hours.forEach((hour) => bookingTime.add(new Option(hour, hour)));
        document.querySelector('#booking-price').textContent = `${formatUYU(result.price)} UYU`;
        document.querySelector('#booking-deposit').textContent = `${formatUYU(result.deposit)} UYU`;
        formMessage.textContent = result.hours.length
            ? 'La seña confirma el turno; el saldo se paga en el complejo.'
            : 'No quedan horarios libres para este día. Probá con otra fecha.';
    } catch (error) {
        bookingTime.replaceChildren(new Option('No disponible', '', true, true));
        bookingTime.options[0].disabled = true;
        formMessage.textContent = error.message;
    }
}

function formatUYU(amount) {
    return new Intl.NumberFormat('es-UY', { maximumFractionDigits: 0 }).format(amount);
}

bookingDate?.addEventListener('change', refreshAvailability);
bookingCourt?.addEventListener('change', refreshAvailability);

bookingForm?.addEventListener('submit', (event) => {
    event.preventDefault();

    if (!bookingForm.reportValidity()) return;
    if (bookingForm.dataset.loggedIn !== 'true') {
        window.location.href = '/cuenta.php#iniciar-sesion';
        return;
    }

    const submitButton = bookingForm.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    formMessage.textContent = 'Estamos preparando el pago seguro de la seña…';
    fetch('/reservas.php', {
        method: 'POST',
        headers: {
            'X-CSRF-Token': bookingForm.dataset.csrf,
            Accept: 'application/json',
        },
        body: new FormData(bookingForm),
    })
        .then(async (response) => {
            const result = await response.json();
            if (!response.ok) throw new Error(result.error || 'No se pudo iniciar la reserva.');
            window.location.href = result.redirect;
        })
        .catch((error) => {
            formMessage.textContent = error.message;
            submitButton.disabled = false;
            refreshAvailability();
        });
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
