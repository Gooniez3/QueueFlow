document.querySelectorAll('[data-faq-trigger]').forEach((trigger) => {
    trigger.addEventListener('click', () => {
        const panel = document.getElementById(trigger.getAttribute('aria-controls'));

        if (!panel) return;

        const expanded = trigger.getAttribute('aria-expanded') === 'true';
        trigger.setAttribute('aria-expanded', String(!expanded));
        panel.hidden = expanded;
    });
});
