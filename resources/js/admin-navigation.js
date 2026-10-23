import './admin.js';
import './content-manager.js';
import './totens.js';
import './relatorios.js';

let busy = false;
let pendingNavigation;
let fieldErrorSequence = 0;
const content = () => document.getElementById('adminMainContent');
const editorOpen = () => Boolean(document.getElementById('visualEditorApp'));
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;
// Production assets stay same-origin; local development may already trust a Vite dev server.
const assetOrigins = new Set([location.origin, ...[...document.querySelectorAll('script[src]')]
    .filter((script) => new URL(script.src).pathname === '/@vite/client')
    .map((script) => new URL(script.src).origin)]);
const eligible = (url) => url.origin === location.origin
    && /^\/(admin|midias|usuarios|notificacoes|relatorios|totens|gestao-conteudo|revisoes|minha-conta)(\/|$)/.test(url.pathname)
    && !/\/(edit|visualizar)$/.test(url.pathname)
    && !/\/totens\/\d+/.test(url.pathname);

function activateButtons() {
    document.querySelectorAll('[data-requires-js]').forEach((button) => {
        button.disabled = button.dataset.unavailable === '1';
    });
}

function showErrors(form, data) {
    form.querySelectorAll('[data-async-error]').forEach((node) => node.remove());
    form.querySelectorAll('.is-invalid').forEach((node) => {
        node.classList.remove('is-invalid');
        node.removeAttribute('aria-invalid');
        node.removeAttribute('aria-describedby');
    });
    for (const [name, messages] of Object.entries(data.errors || {})) {
        const field = form.elements.namedItem(name);
        if (!(field instanceof HTMLElement)) continue;
        const message = document.createElement('div');
        message.className = 'invalid-feedback';
        message.dataset.asyncError = '';
        message.id = `admin-field-error-${++fieldErrorSequence}`;
        message.textContent = messages.join(' ');
        field.classList.add('is-invalid');
        field.setAttribute('aria-invalid', 'true');
        field.setAttribute('aria-describedby', message.id);
        field.after(message);
    }
    form.querySelector('.is-invalid')?.focus();
    window.AdminNotice?.error(data.errors ? Object.values(data.errors).flat() : (data.message || 'Não foi possível concluir a ação.'));
}

async function render(response, { historyMode = 'replace', focus = false } = {}) {
    const html = await response.text();
    const doc = new DOMParser().parseFromString(html, 'text/html');
    if (!response.ok) throw new Error(`Não foi possível carregar o conteúdo (${response.status}).`);
    const next = doc.getElementById('adminMainContent');
    if (!next || doc.getElementById('visualEditorApp')) {
        // Authentication and the rich editor have their own document lifecycle (including unsaved-draft guards).
        location.assign(response.url);
        return;
    }
    doc.querySelectorAll('link[rel="stylesheet"]').forEach((link) => {
        if (!assetOrigins.has(new URL(link.href).origin)) return;
        if (![...document.querySelectorAll('link[rel="stylesheet"]')].some((existing) => existing.href === link.href)) {
            document.head.append(link.cloneNode(true));
        }
    });
    doc.querySelectorAll('head style').forEach((style) => {
        if (![...document.head.querySelectorAll('style')].some((existing) => existing.textContent === style.textContent)) {
            document.head.append(style.cloneNode(true));
        }
    });
    document.dispatchEvent(new Event('admin:before-replace'));
    content().replaceChildren(...next.childNodes);
    const heading = document.querySelector('.content-header');
    const nextHeading = doc.querySelector('.content-header');
    if (heading && nextHeading) heading.replaceChildren(...nextHeading.childNodes);
    const notices = document.getElementById('adminNotificationsConfig');
    if (notices) notices.textContent = doc.getElementById('adminNotificationsConfig')?.textContent || '[]';
    // Keep the shell nodes intact, updating only identity labels and menu selection.
    for (const selector of ['.editor-user-avatar', '.editor-user-summary strong', '.editor-user-summary small', '.editor-user-identity strong', '.editor-user-identity span']) {
        document.querySelectorAll(selector).forEach((node, index) => {
            const value = doc.querySelectorAll(selector)[index];
            if (value) node.textContent = value.textContent;
        });
    }
    document.querySelectorAll('.main-sidebar a.nav-link').forEach((link) => {
        const matching = [...doc.querySelectorAll('.main-sidebar a.nav-link')].find((item) => item.href === link.href);
        link.classList.toggle('active', matching?.classList.contains('active') || false);
    });
    const badge = document.querySelector('a[aria-label="Notificações"] .badge');
    if (badge) badge.textContent = doc.querySelector('a[aria-label="Notificações"] .badge')?.textContent || '0';
    document.title = doc.title;
    const url = new URL(response.url);
    history[historyMode === 'push' ? 'pushState' : 'replaceState']({}, '', url);
    activateButtons();
    document.dispatchEvent(new Event('admin:loaded'));
    if (focus) { content().focus({ preventScroll: true }); window.scrollTo({ top: 0 }); }
}

async function navigate(url, options = {}) {
    if (busy) return;
    pendingNavigation?.abort();
    const controller = new AbortController();
    pendingNavigation = controller;
    content()?.setAttribute('aria-busy', 'true');
    try {
        const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store', signal: controller.signal, headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' } });
        if (!controller.signal.aborted) await render(response, options);
    } catch (error) {
        if (error.name !== 'AbortError') window.AdminNotice?.error('Não foi possível atualizar o conteúdo. Verifique a conexão e tente novamente.');
    } finally {
        if (pendingNavigation === controller) { pendingNavigation = null; content()?.removeAttribute('aria-busy'); }
    }
}

async function submit(form, submitter) {
    if (busy || !form.isConnected) return;
    busy = true;
    pendingNavigation?.abort();
    let buttons = [];
    try {
        const data = new FormData(form);
        if (submitter?.name) data.append(submitter.name, submitter.value);
        if (form.dataset.confirm) {
            const confirmed = await window.AdminNotice?.confirm({
                type: form.dataset.confirmEmail || form.hasAttribute('data-remove-card') ? 'danger' : 'warning',
                title: form.dataset.confirmTitle || 'Confirmar alterações?',
                message: form.dataset.confirm,
                confirmText: form.dataset.confirmLabel || 'Confirmar',
                confirmEmail: form.dataset.confirmEmail,
            });
            if (!confirmed) return;
            if (form.dataset.confirmEmail) {
                data.set('confirmation_email', document.getElementById('adminNoticeEmail').value);
                data.set('current_password', document.getElementById('adminNoticePassword').value);
            }
        }
        buttons = [...form.querySelectorAll('button')].map((button) => [button, button.disabled]);
        buttons.forEach(([button]) => { button.disabled = true; });
        form.setAttribute('aria-busy', 'true');
        const json = form.hasAttribute('data-async-json');
        const method = form.method.toUpperCase();
        const url = new URL(form.action);
        if (method === 'GET') url.search = new URLSearchParams(data).toString();
        const response = await fetch(url, {
            method, credentials: 'same-origin', cache: 'no-store',
            headers: { Accept: json ? 'application/json' : 'text/html', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
            ...(method === 'GET' ? {} : { body: data }),
        });
        if (json || response.headers.get('content-type')?.includes('application/json')) {
            const result = await response.json();
            if (!response.ok) {
                if (response.status === 401 || response.status === 419) {
                    window.AdminNotice?.error('Sua sessão expirou. Entre novamente.');
                    location.assign('/login');
                } else if (result.redirect) location.assign(result.redirect);
                else showErrors(form, result);
                return;
            }
            if (result.redirect) {
                busy = false;
                if (eligible(new URL(result.redirect))) await navigate(result.redirect);
                else location.assign(result.redirect);
            } else if (form.hasAttribute('data-remove-card')) {
                // Never remove a card optimistically: both status and deleted ID must match.
                const card = form.closest('[data-media-card]');
                if (card?.dataset.mediaCard === String(result.deleted_id)) card.remove();
                if (!document.querySelector('[data-media-card]')) {
                    busy = false;
                    const firstPage = new URL(location.href);
                    firstPage.searchParams.delete('page');
                    await navigate(firstPage);
                }
            } else {
                busy = false;
                await navigate(location.href);
            }
            window.AdminNotice?.success(result.message || 'Alterações salvas.');
        } else {
            await render(response, { historyMode: method === 'GET' ? 'push' : 'replace' });
        }
    } catch (error) {
        // Do not retry mutations automatically: the server may already have committed the request.
        window.AdminNotice?.error('Não foi possível confirmar a operação. Confira o estado dos dados antes de tentar novamente.');
    } finally {
        busy = false;
        form.removeAttribute('aria-busy');
        delete form.dataset.enviando;
        buttons.forEach(([button, disabled]) => { button.disabled = disabled; });
        const password = document.getElementById('adminNoticePassword');
        if (password) password.value = '';
    }
}

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (event.defaultPrevented || !form.closest('#adminMainContent') || editorOpen() || form.hasAttribute('data-full-navigation') || form.target) return;
    // Pairing intentionally ends the human session and loads the standalone player.
    if (new URL(form.action).pathname === '/pairing') return;
    event.preventDefault();
    void submit(form, event.submitter);
});
document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href]');
    if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || link.target || link.hasAttribute('download') || editorOpen()) return;
    if (link.getAttribute('href').startsWith('#')) return;
    const url = new URL(link.href);
    if (!eligible(url) || url.hash || link.hasAttribute('data-full-navigation')) return;
    event.preventDefault();
    void navigate(url, { historyMode: 'push', focus: true });
});
window.addEventListener('popstate', () => {
    if (!editorOpen()) void navigate(location.href, { focus: true });
});
window.AdminNavigation = { submit, refresh: () => navigate(location.href) };
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', activateButtons);
else activateButtons();
