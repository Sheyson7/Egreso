document.querySelectorAll('.user-reservation').forEach((card) => {
    const editForm = card.querySelector('.reservation-reschedule-form');
    const cancelForm = card.querySelector('.reservation-cancel-form');
    if (!editForm || !cancelForm) return;

    const court = editForm.elements.court;
    const date = editForm.elements.date;
    const duration = editForm.elements.duration;
    const time = editForm.elements.time;
    const reservationId = editForm.elements.reservation_id.value;
    const message = card.querySelector('.reservation-action-message');
    const scheduleLabel = card.querySelector('.reservation-schedule');
    const durationLabel = card.querySelector('.reservation-duration-value');
    let availabilityRequest = 0;

    async function refreshAvailability() {
        const requestId = ++availabilityRequest;
        const previousTime = time.value || time.dataset.currentTime;
        time.replaceChildren(new Option('Buscando horarios…', '', true, true));
        time.options[0].disabled = true;

        try {
            const params = new URLSearchParams({
                court: court.value,
                date: date.value,
                duration: duration.value,
                exclude: reservationId,
            });
            const response = await fetch(`/disponibilidad.php?${params}`, { headers: { Accept: 'application/json' } });
            const envelope = await response.json();
            const result = envelope.data || {};
            if (!response.ok) throw new Error(envelope.errors?.[0] || 'No se pudo consultar la disponibilidad.');
            if (requestId !== availabilityRequest) return;

            time.replaceChildren(new Option('Elegí un horario', '', true, true));
            time.options[0].disabled = true;
            result.hours.forEach((hour) => time.add(new Option(hour, hour)));
            if (result.hours.includes(previousTime)) time.value = previousTime;
            const formatAmount = (amount) => new Intl.NumberFormat('es-UY', { maximumFractionDigits: 0 }).format(amount);
            card.querySelector('.reservation-total').textContent = `$ ${formatAmount(result.total)} UYU`;
            card.querySelector('.reservation-deposit').textContent = `$ ${formatAmount(result.deposit)} UYU`;
            message.textContent = result.hours.length
                ? 'Al guardar, el nuevo horario ocupará la cancha en el archivo local.'
                : 'No hay horarios que permitan esa duración para esta fecha. Probá con otro día o duración.';
        } catch (error) {
            if (requestId !== availabilityRequest) return;
            time.replaceChildren(new Option('No disponible', '', true, true));
            time.options[0].disabled = true;
            message.textContent = error.message;
        }
    }

    [court, date, duration].forEach((field) => field.addEventListener('change', refreshAvailability));
    refreshAvailability();

    editForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!editForm.reportValidity()) return;
        const button = editForm.querySelector('button[type="submit"]');
        button.disabled = true;
        message.textContent = 'Guardando los cambios…';
        try {
            const response = await fetch('/reservas.php', {
                method: 'POST',
                headers: { Accept: 'application/json' },
                body: new FormData(editForm),
            });
            const envelope = await response.json();
            const result = envelope.data || {};
            if (!response.ok) throw new Error(envelope.errors?.[0] || 'No se pudieron guardar los cambios.');

            const startAt = new Date(`${date.value}T${time.value}:00`);
            const endAt = new Date(startAt);
            endAt.setHours(endAt.getHours() + Number(duration.value));
            const dateLabel = new Intl.DateTimeFormat('es-UY', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(startAt);
            const timeLabel = (value) => new Intl.DateTimeFormat('es-UY', { hour: '2-digit', minute: '2-digit', hour12: false }).format(value);
            scheduleLabel.textContent = `CANCHA ${court.value} · ${dateLabel} · ${timeLabel(startAt)}–${timeLabel(endAt)}`;
            durationLabel.textContent = `${duration.value} h`;
            time.dataset.currentTime = time.value;
            message.textContent = result.message || 'Cambios guardados.';
        } catch (error) {
            message.textContent = error.message;
            button.disabled = false;
            refreshAvailability();
        }
    });

    cancelForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!window.confirm('¿Cancelar esta reserva y liberar el horario?')) return;
        const button = cancelForm.querySelector('button[type="submit"]');
        button.disabled = true;
        message.textContent = 'Cancelando la reserva…';
        try {
            const response = await fetch('/reservas.php', {
                method: 'POST',
                headers: { Accept: 'application/json' },
                body: new FormData(cancelForm),
            });
            const envelope = await response.json();
            const result = envelope.data || {};
            if (!response.ok) throw new Error(envelope.errors?.[0] || 'No se pudo cancelar la reserva.');
            card.classList.add('is-cancelled');
            const status = card.querySelector('.admin-status');
            status.textContent = 'Cancelada';
            status.classList.remove('is-active');
            status.classList.add('is-cancelled');
            editForm.remove();
            cancelForm.remove();
            message.textContent = result.message || 'Reserva cancelada. El horario quedó libre.';
        } catch (error) {
            message.textContent = error.message;
            button.disabled = false;
        }
    });
});
