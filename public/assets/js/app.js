const bookingForm = document.querySelector('#booking-form');
const formMessage = document.querySelector('#form-message');
const bookingDate = bookingForm?.elements.date;
const bookingCourt = bookingForm?.elements.court;
const bookingTime = bookingForm?.elements.time;
const bookingDuration = bookingForm?.elements.duration;
let availabilityRequest = 0;

const toIsoDate = (date) => [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-');
const dateFromIso = (value) => {
    const [year, month, day] = value.split('-').map(Number);
    return new Date(year, month - 1, day);
};
function renderDateOptions() {
    if (!bookingForm) return;
    const quickOptions = document.querySelector('#date-quick-options');
    const calendarDays = document.querySelector('#calendar-days');
    const monthLabel = document.querySelector('#calendar-month');
    const previous = document.querySelector('#calendar-previous');
    const next = document.querySelector('#calendar-next');
    const maxDate = dateFromIso(bookingForm.dataset.dateMax);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    quickOptions.replaceChildren();
    for (let offset = 0; offset < 7; offset += 1) {
        const day = new Date(today);
        day.setDate(day.getDate() + offset);
        if (day > maxDate) break;
        const value = toIsoDate(day);
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `date-option${bookingDate.value === value ? ' is-selected' : ''}`;
        button.setAttribute('aria-pressed', String(bookingDate.value === value));
        button.innerHTML = `<span>${offset === 0 ? 'Hoy' : new Intl.DateTimeFormat('es-UY', { weekday: 'short' }).format(day)}</span><strong>${day.getDate()}</strong>`;
        button.addEventListener('click', () => chooseDate(value));
        quickOptions.append(button);
    }

    if (!calendarDays) return;
    const year = calendarMonth.getFullYear();
    const month = calendarMonth.getMonth();
    monthLabel.textContent = new Intl.DateTimeFormat('es-UY', { month: 'long' }).format(calendarMonth);
    previous.disabled = year === today.getFullYear() && month === today.getMonth();
    next.disabled = year === maxDate.getFullYear() && month === maxDate.getMonth();
    calendarDays.replaceChildren();

    const firstWeekday = (new Date(year, month, 1).getDay() + 6) % 7;
    for (let blank = 0; blank < firstWeekday; blank += 1) {
        const spacer = document.createElement('span');
        spacer.setAttribute('aria-hidden', 'true');
        calendarDays.append(spacer);
    }
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    for (let dayNumber = 1; dayNumber <= daysInMonth; dayNumber += 1) {
        const day = new Date(year, month, dayNumber);
        const value = toIsoDate(day);
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `calendar-day${bookingDate.value === value ? ' is-selected' : ''}`;
        button.textContent = dayNumber;
        button.disabled = day < today || day > maxDate;
        button.setAttribute('aria-label', new Intl.DateTimeFormat('es-UY', { weekday: 'long', day: 'numeric', month: 'long' }).format(day));
        button.setAttribute('aria-pressed', String(bookingDate.value === value));
        button.addEventListener('click', () => chooseDate(value));
        calendarDays.append(button);
    }
}

let calendarMonth = new Date();
calendarMonth.setDate(1);
calendarMonth.setHours(0, 0, 0, 0);

function chooseDate(value) {
    bookingDate.value = value;
    document.querySelector('#selected-date-label').textContent = new Intl.DateTimeFormat('es-UY', {
        weekday: 'long', day: 'numeric', month: 'long',
    }).format(dateFromIso(value));
    document.querySelector('#date-calendar').hidden = true;
    const calendarToggle = document.querySelector('#date-calendar-toggle');
    calendarToggle.setAttribute('aria-expanded', 'false');
    calendarToggle.innerHTML = 'Ver otras fechas <span aria-hidden="true">⌄</span>';
    renderDateOptions();
    refreshAvailability();
}

document.querySelector('#calendar-previous')?.addEventListener('click', () => {
    calendarMonth = new Date(calendarMonth.getFullYear(), calendarMonth.getMonth() - 1, 1);
    renderDateOptions();
});
document.querySelector('#calendar-next')?.addEventListener('click', () => {
    calendarMonth = new Date(calendarMonth.getFullYear(), calendarMonth.getMonth() + 1, 1);
    renderDateOptions();
});
document.querySelector('#date-calendar-toggle')?.addEventListener('click', (event) => {
    const button = event.currentTarget;
    const calendar = document.querySelector('#date-calendar');
    const isExpanded = button.getAttribute('aria-expanded') === 'true';
    button.setAttribute('aria-expanded', String(!isExpanded));
    button.innerHTML = isExpanded ? 'Ver otras fechas <span aria-hidden="true">⌄</span>' : 'Ocultar calendario <span aria-hidden="true">⌃</span>';
    calendar.hidden = isExpanded;
    if (!isExpanded) renderDateOptions();
});

renderDateOptions();

async function refreshAvailability() {
    updatePriceSummary();
    if (!bookingDate?.value || !bookingCourt || !bookingTime) return;

    const requestId = ++availabilityRequest;
    bookingTime.innerHTML = '<option value="" disabled selected>Buscando horarios…</option>';
    try {
        const params = new URLSearchParams({ court: bookingCourt.value, date: bookingDate.value, duration: bookingDuration.value });
        const response = await fetch(`/disponibilidad.php?${params}`, { headers: { Accept: 'application/json' } });
        const envelope = await response.json();
        const result = envelope.data || {};
        if (!response.ok) throw new Error(envelope.errors?.[0] || 'No se pudo consultar la disponibilidad.');
        if (requestId !== availabilityRequest) return;

        bookingTime.replaceChildren(new Option('Elegí un horario', '', true, true));
        bookingTime.options[0].disabled = true;
        result.hours.forEach((hour) => bookingTime.add(new Option(hour, hour)));
        document.querySelector('#booking-price').textContent = `${formatUYU(result.price)} UYU`;
        document.querySelector('#booking-total').textContent = `${formatUYU(result.total)} UYU`;
        document.querySelector('#booking-deposit').textContent = `${formatUYU(result.deposit)} UYU`;
        formMessage.textContent = result.hours.length
            ? 'La seña confirma el turno; el saldo se paga en el complejo.'
            : 'No quedan horarios libres para este día. Probá con otra fecha.';
    } catch (error) {
        if (requestId !== availabilityRequest) return;
        bookingTime.replaceChildren(new Option('No disponible', '', true, true));
        bookingTime.options[0].disabled = true;
        formMessage.textContent = error.message;
    }
}

function formatUYU(amount) {
    return new Intl.NumberFormat('es-UY', { maximumFractionDigits: 0 }).format(amount);
}

function updatePriceSummary() {
    if (!bookingForm || !bookingDuration) return;
    const total = Number(bookingForm.dataset.hourlyPrice) * Number(bookingDuration.value);
    const deposit = Math.round(total * Number(bookingForm.dataset.depositPercent) / 100);
    document.querySelector('#booking-total').textContent = `${formatUYU(total)} UYU`;
    document.querySelector('#booking-deposit').textContent = `${formatUYU(deposit)} UYU`;
}

bookingDate?.addEventListener('change', refreshAvailability);
bookingCourt?.addEventListener('change', refreshAvailability);
bookingDuration?.addEventListener('change', refreshAvailability);
updatePriceSummary();

bookingForm?.addEventListener('submit', (event) => {
    event.preventDefault();

    if (!bookingDate.value) {
        formMessage.textContent = 'Elegí el día del partido para consultar los horarios.';
        document.querySelector('#date-quick-options')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }
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
            const envelope = await response.json();
            const result = envelope.data || {};
            if (!response.ok) throw new Error(envelope.errors?.[0] || 'No se pudo iniciar la reserva.');
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
