<script>
    (function () {
        try {
            var saved = localStorage.getItem('rifitv-theme') || localStorage.getItem('rifi-theme');
            var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            var theme = saved === 'light' || saved === 'dark'
                ? saved
                : (prefersDark ? 'dark' : 'light');
            var root = document.documentElement;

            root.setAttribute('data-theme', theme);
            root.classList.remove('theme-light', 'theme-dark', 'light', 'dark');
            root.classList.add('theme-' + theme, theme);
            root.style.colorScheme = theme;
        } catch (e) {
            document.documentElement.setAttribute('data-theme', 'dark');
            document.documentElement.classList.add('theme-dark', 'dark');
            document.documentElement.style.colorScheme = 'dark';
        }
    })();
</script>
