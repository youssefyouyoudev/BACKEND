export function initPlaylistForms() {
    document.querySelectorAll('form [data-playlist-input-type]').forEach((selector) => {
        const form = selector.closest('form');
        if (!form || form.dataset.playlistFormReady) return;
        form.dataset.playlistFormReady = '1';

        const syncPlaylistFields = () => {
            const inputType = selector.value;

            form.querySelectorAll('[data-playlist-field]').forEach((field) => {
                const modes = field.dataset.playlistField.split(/\s+/);
                const visible = modes.includes(inputType);

                field.hidden = !visible;
                field.querySelectorAll('input, select, textarea').forEach((input) => {
                    input.disabled = !visible;
                    input.required = visible && (
                        (input.name === 'm3u_url' && inputType === 'm3u_url')
                        || (input.name === 'playlist_file' && inputType === 'upload' && !input.dataset.hasExistingFile)
                        || (input.name === 'active_code' && inputType === 'active_code' && input.placeholder !== 'Leave unchanged')
                        || (['server_url', 'username', 'password'].includes(input.name) && inputType === 'xtream' && input.placeholder !== 'Leave unchanged')
                    );
                });
            });

            const preview = form.querySelector('[data-xtream-preview]');
            if (preview) {
                const server = form.querySelector('[name="server_url"]')?.value?.replace(/\/+$/, '') || 'http://server';
                const username = form.querySelector('[name="username"]')?.value || 'USERNAME';
                const output = form.querySelector('[name="output"]')?.value || 'mpegts';
                preview.textContent = inputType === 'xtream'
                    ? `${server}/get.php?username=${encodeURIComponent(username)}&password=****&type=m3u_plus&output=${output}`
                    : '';
            }
        };

        selector.addEventListener('change', syncPlaylistFields);
        form.addEventListener('input', syncPlaylistFields);
        syncPlaylistFields();
    });
}
