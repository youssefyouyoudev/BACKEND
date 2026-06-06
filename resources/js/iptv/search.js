export function initIptvSearch() {
    document.querySelectorAll('.iptv-search input').forEach((input) => {
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                input.value = '';
                input.blur();
            }
        });
    });
}
