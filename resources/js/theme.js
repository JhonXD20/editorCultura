const themeStorageKey = 'editor-cultura-theme';
const root = document.documentElement;
const systemTheme = window.matchMedia('(prefers-color-scheme: dark)');

const storedTheme = () => {
    try {
        return localStorage.getItem(themeStorageKey);
    } catch (error) {
        return null;
    }
};

const activeTheme = () => storedTheme()
    || (systemTheme.matches ? 'dark' : 'light');

const refreshControls = (theme) => {
    document.querySelectorAll('[data-theme-toggle]').forEach((control) => {
        const isDark = theme === 'dark';
        const icon = control.querySelector('[data-theme-icon]');
        const label = control.querySelector('[data-theme-label]');
        const nextThemeLabel = isDark ? 'claro' : 'escuro';

        control.setAttribute('aria-pressed', String(isDark));
        control.setAttribute('aria-label', `Ativar tema ${nextThemeLabel}`);
        control.setAttribute('title', `Ativar tema ${nextThemeLabel}`);

        if (icon) {
            icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
        }

        if (label) {
            label.textContent = isDark ? 'Tema claro' : 'Tema escuro';
        }
    });
};

const applyTheme = (theme, persist = false) => {
    const normalizedTheme = theme === 'dark' ? 'dark' : 'light';

    root.dataset.theme = normalizedTheme;
    root.style.colorScheme = normalizedTheme;
    document.body?.classList.toggle('dark-mode', normalizedTheme === 'dark');

    if (persist) {
        try {
            localStorage.setItem(themeStorageKey, normalizedTheme);
        } catch (error) {
            // O tema ainda funciona durante a sessao quando o storage e bloqueado.
        }
    }

    refreshControls(normalizedTheme);
};

applyTheme(activeTheme());

document.querySelectorAll('[data-theme-toggle]').forEach((control) => {
    control.addEventListener('click', () => {
        applyTheme(root.dataset.theme === 'dark' ? 'light' : 'dark', true);
    });
});

systemTheme.addEventListener?.('change', (event) => {
    if (!storedTheme()) {
        applyTheme(event.matches ? 'dark' : 'light');
    }
});
