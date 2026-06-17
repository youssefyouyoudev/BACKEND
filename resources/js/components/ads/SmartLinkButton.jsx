export function mountSmartLinkButtons(config = {}) {
    document.querySelectorAll('[data-smartlink-button]').forEach((button) => {
        if (!button.getAttribute('href') && config.smartlinkUrl) {
            button.setAttribute('href', config.smartlinkUrl);
        }

        button.setAttribute('target', '_blank');
        button.setAttribute('rel', 'nofollow sponsored noopener noreferrer');
        button.addEventListener('click', () => {
            window.dispatchEvent(new CustomEvent('rifitv:ad-event', {
                detail: { name: 'smartlink_clicked', href: button.href },
            }));
        });
    });
}
