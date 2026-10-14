<script>
    (() => {
        const storageKey = 'editor-cultura-theme';
        let theme = 'light';

        try {
            theme = localStorage.getItem(storageKey)
                || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        } catch (error) {
            theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }

        document.documentElement.dataset.theme = theme;
        document.documentElement.style.colorScheme = theme;
    })();
</script>
