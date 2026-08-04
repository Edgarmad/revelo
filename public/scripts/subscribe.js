(() => {
  const forms = document.querySelectorAll('[data-subscribe-form]');

  forms.forEach((form) => {
    const status = form.querySelector('[data-subscribe-status]');
    const submit = form.querySelector('[type="submit"]');

    form.addEventListener('submit', async (event) => {
      event.preventDefault();

      const data = new FormData(form);
      const email = String(data.get('email') || '').trim();

      if (!email) {
        setStatus(status, 'Ingresa tu correo electronico.', true);
        return;
      }

      submit?.setAttribute('disabled', 'true');
      setStatus(status, 'Enviando...', false);

      try {
        const response = await fetch('/api/subscribe.php', {
          method: 'POST',
          body: data,
          headers: { Accept: 'application/json' },
        });
        const result = await response.json().catch(() => ({}));

        if (!response.ok || !result.ok) {
          throw new Error(result.message || 'No pudimos completar la suscripcion.');
        }

        form.reset();
        setStatus(status, result.message || 'Gracias por suscribirte.', false);

        const banner = form.closest('[data-floating-banner]');
        if (banner) {
          window.setTimeout(() => banner.classList.add('floating-banner--hidden'), 1800);
        }
      } catch (error) {
        setStatus(status, error.message || 'Intenta de nuevo mas tarde.', true);
      } finally {
        submit?.removeAttribute('disabled');
      }
    });
  });

  function setStatus(element, message, isError) {
    if (!element) return;
    element.textContent = message;
    element.hidden = false;
    element.dataset.error = isError ? 'true' : 'false';
  }
})();
