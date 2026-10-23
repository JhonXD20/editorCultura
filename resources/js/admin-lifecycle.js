// Initializers must also run after a partial navigation, with cleanup before replacing content.
export function onAdminPage(initialize) {
    let cleanup;
    const mount = () => { cleanup = initialize(); };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mount, { once: true });
    else queueMicrotask(mount);
    document.addEventListener('admin:loaded', mount);
    document.addEventListener('admin:before-replace', () => {
        cleanup?.();
        cleanup = undefined;
    });
}
