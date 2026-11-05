document.querySelectorAll('.account-form').forEach((form) => {
    const submitButton = form.querySelector('button[type="submit"]');
    if (!submitButton) return;

    const feedback = document.createElement('p');
    feedback.className = 'account-notice';
    feedback.setAttribute('role', 'status');
    feedback.setAttribute('aria-live', 'polite');
    form.prepend(feedback);

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        feedback.textContent = '';
        feedback.classList.remove('error', 'success');
        if (!form.reportValidity()) return;

        submitButton.disabled = true;
        submitButton.setAttribute('aria-busy', 'true');
        feedback.textContent = form.elements.action.value === 'register'
            ? 'Creando tu cuenta…'
            : 'Verificando tus datos…';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { Accept: 'application/json' },
                body: new FormData(form),
            });
            const envelope = await response.json();
            const result = envelope.data || {};
            if (!response.ok) throw new Error(envelope.errors?.[0] || 'No se pudo completar la solicitud.');

            const successCard = document.createElement('section');
            successCard.className = 'account-card account-card-register';
            successCard.setAttribute('role', 'status');
            successCard.setAttribute('aria-live', 'polite');

            const heading = document.createElement('h2');
            heading.textContent = '¡Listo!';
            const message = document.createElement('p');
            message.textContent = result.message || 'Tu sesión está iniciada.';
            const continueLink = document.createElement('a');
            continueLink.className = 'button button-primary account-submit';
            continueLink.href = result.redirect || '/';
            continueLink.textContent = 'Continuar a Seberih →';

            successCard.append(heading, message, continueLink);
            form.closest('.account-cards')?.replaceChildren(successCard);
        } catch (error) {
            feedback.classList.add('error');
            feedback.textContent = error instanceof Error ? error.message : 'Ocurrió un error. Intentá nuevamente.';
            submitButton.disabled = false;
            submitButton.removeAttribute('aria-busy');
        }
    });
});
