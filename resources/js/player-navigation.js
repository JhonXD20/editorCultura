/** Keep the player shell/fullscreen session while loading cached or online pages. */
export function createPlayerNavigation({ beforeReplace, afterReplace }) {
    let pending;
    let sequence = 0;
    const eligible = (url) => url.origin === location.origin
        && (/^\/totem\/exibir\/\d+$/.test(url.pathname) || /^\/gestao-conteudo\/\d+\/visualizar$/.test(url.pathname));

    async function navigate(value, { historyMode = 'push' } = {}) {
        const url = new URL(value, location.href);
        if (!eligible(url)) { location.assign(url); return; }
        pending?.abort();
        const controller = new AbortController();
        pending = controller;
        const current = document.getElementById('totemPublicoViewport');
        current.setAttribute('aria-busy', 'true');
        const request = ++sequence;
        try {
            const response = await fetch(url, { credentials: 'same-origin', signal: controller.signal, headers: { Accept: 'text/html' } });
            if (!response.ok || response.redirected) throw new Error('Página indisponível');
            const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
            if (request !== sequence) return;
            const next = doc.getElementById('totemPublicoViewport');
            const stage = doc.getElementById('totemStage');
            // A new release may require a different JS/CSS bundle and a full lifecycle.
            if (!next || !stage || next.dataset.manifestVersion !== current.dataset.manifestVersion) {
                location.assign(url); return;
            }
            beforeReplace();
            for (const key of Object.keys(current.dataset)) delete current.dataset[key];
            Object.assign(current.dataset, next.dataset);
            document.getElementById('totemStage').replaceChildren(...stage.childNodes);
            const nav = current.querySelector('.totem-navigation');
            const nextNav = next.querySelector('.totem-navigation');
            nav.className = nextNav.className;
            nav.replaceChildren(...nextNav.childNodes);
            current.querySelector('#sectionTouchHint')?.remove();
            const hint = next.querySelector('#sectionTouchHint');
            if (hint) current.append(hint);
            document.title = doc.title;
            if (historyMode === 'push') history.pushState({}, '', url);
            afterReplace();
            const display = document.getElementById('totemDisplayArea');
            if (!matchMedia('(prefers-reduced-motion: reduce)').matches) display.animate?.([{ opacity: .3 }, { opacity: 1 }], { duration: 220 });
        } catch (error) {
            if (error.name !== 'AbortError') location.assign(url);
        } finally {
            if (pending === controller) { pending = null; current.removeAttribute('aria-busy'); }
        }
    }

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey
            || link.target === '_blank' || link.hasAttribute('download') || !link.closest('#totemPublicoViewport')) return;
        const url = new URL(link.href);
        if (!eligible(url) || url.hash) return;
        event.preventDefault();
        void navigate(url);
    });
    window.addEventListener('popstate', () => { void navigate(location.href, { historyMode: 'none' }); });
    return navigate;
}

export const PLAYER_INTERACTIVE_SELECTOR = 'a, button, input, select, textarea, video, audio, details, summary, [contenteditable="true"], [role="button"], [role="link"], [data-cultura-navigation="1"], .card-botao-totem, [data-cultura-carousel="1"]';

/** Only stationary, primary taps may change the section; scrolling and controls win. */
export function sectionTapDirection({ start, end, bounds, elapsed, interactive = false, selectedText = false }) {
    if (!start || interactive || selectedText || elapsed > 600 || elapsed < 0 || bounds.width <= 0) return null;
    if (Math.hypot(end.x - start.x, end.y - start.y) > 12) return null;
    const ratio = (end.x - bounds.left) / bounds.width;
    if (ratio < 0 || ratio > 1) return null;
    return ratio < .49 ? 'previous' : ratio > .51 ? 'next' : null;
}
