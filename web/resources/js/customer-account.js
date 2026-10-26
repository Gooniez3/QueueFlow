const clearButton = document.querySelector('[data-clear-device-data]');

if (clearButton) {
    const dialog = document.getElementById(clearButton.dataset.dialogId);
    const cancelButton = dialog?.querySelector('[data-clear-device-data-cancel]');

    clearButton.addEventListener('click', () => {
        if (!dialog) return;
        if (typeof dialog.showModal === 'function') {
            dialog.showModal();
            return;
        }
        if (window.confirm(dialog.dataset.confirmMessage ?? 'Clear saved QueueFlow guest data from this browser?')) {
            dialog.querySelector('form')?.requestSubmit();
        }
    });

    cancelButton?.addEventListener('click', () => dialog.close());
}
