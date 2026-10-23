(() => {
    let theme;
    try { theme = localStorage.getItem('editor-cultura-theme'); } catch { /* armazenamento pode estar bloqueado */ }
    if (!['light', 'dark'].includes(theme)) theme = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    document.documentElement.dataset.theme = theme;
    document.documentElement.style.colorScheme = theme;
})();
