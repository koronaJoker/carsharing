document.addEventListener('click', (event) => {
    if (event.target.matches('[data-close-modal]') || event.target.classList.contains('modal-backdrop')) {
        const modal = event.target.closest('.modal-backdrop') || event.target;
        modal.remove();
    }
});

document.addEventListener('input', (event) => {
    if (event.target.id === 'card_number') {
        event.target.value = event.target.value
            .replace(/\D/g, '')
            .slice(0, 16)
            .replace(/(.{4})/g, '$1 ')
            .trim();
    }
});
