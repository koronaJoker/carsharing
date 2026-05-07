let savedTheme = 'light';
try {
    savedTheme = localStorage.getItem('theme') || 'light';
} catch (error) {
    savedTheme = 'light';
}
document.documentElement.dataset.theme = savedTheme;

document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('[data-theme-toggle]');
    if (toggle) {
        toggle.setAttribute('aria-pressed', document.documentElement.dataset.theme === 'dark' ? 'true' : 'false');
    }
});

document.addEventListener('click', (event) => {
    const themeToggle = event.target.closest('[data-theme-toggle]');
    if (themeToggle) {
        const nextTheme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
        document.documentElement.dataset.theme = nextTheme;
        try {
            localStorage.setItem('theme', nextTheme);
        } catch (error) {
            // Theme still switches for the current page even when storage is unavailable.
        }
        themeToggle.setAttribute('aria-pressed', nextTheme === 'dark' ? 'true' : 'false');
        return;
    }

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

    if (event.target.matches('[data-price-range]')) {
        const output = document.querySelector('[data-price-output]');
        if (output) {
            output.textContent = Number(event.target.value).toFixed(2);
        }
    }
});
