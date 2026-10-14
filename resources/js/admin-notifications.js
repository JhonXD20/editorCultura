const TYPE_CONFIG = {
    success: { className: 'success', icon: 'fas fa-check-circle', title: 'Sucesso' },
    danger: { className: 'danger', icon: 'fas fa-exclamation-circle', title: 'Erro' },
    warning: { className: 'warning', icon: 'fas fa-exclamation-triangle', title: 'Atenção' },
    info: { className: 'info', icon: 'fas fa-info-circle', title: 'Informação' },
};

const normalizeType = (type) => {
    if (type === 'error') return 'danger';
    return TYPE_CONFIG[type] ? type : 'info';
};

const escapeHtml = (value) => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

const messageHtml = (message) => {
    if (Array.isArray(message)) {
        return `<ul class="mb-0 pl-3">${message.map((item) => `<li>${escapeHtml(item)}</li>`).join('')}</ul>`;
    }
    return escapeHtml(message).replaceAll('\n', '<br>');
};

const fallbackToast = ({ type, message }) => {
    let host = document.getElementById('adminNoticeFallback');
    if (!host) {
        host = document.createElement('div');
        host.id = 'adminNoticeFallback';
        document.body.appendChild(host);
    }

    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible shadow`;
    alert.setAttribute('role', type === 'danger' ? 'alert' : 'status');
    alert.appendChild(document.createTextNode(String(message ?? '')));

    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'close';
    close.setAttribute('aria-label', 'Fechar');
    close.innerHTML = '<span aria-hidden="true">&times;</span>';
    close.addEventListener('click', () => alert.remove());
    alert.appendChild(close);
    host.appendChild(alert);

    window.setTimeout(() => alert.remove(), type === 'danger' ? 9000 : 5000);
};

const toast = (options = {}) => {
    const type = normalizeType(options.type);
    const config = TYPE_CONFIG[type];
    const $ = window.jQuery;

    if (!$ || typeof $.fn?.Toasts !== 'function') {
        fallbackToast({ type, message: options.message });
        return;
    }

    $(document).Toasts('create', {
        class: `bg-${config.className}`,
        icon: options.icon || config.icon,
        title: escapeHtml(options.title || config.title),
        body: messageHtml(options.message ?? ''),
        autohide: options.autohide ?? type !== 'danger',
        autoremove: true,
        delay: options.delay ?? (type === 'warning' ? 6500 : 4500),
        close: true,
        position: 'topRight',
        fixed: true,
        fade: true,
    });
};

let modalResolver = null;
let modalResult = false;

const resolveModal = () => {
    if (!modalResolver) return;
    const resolve = modalResolver;
    modalResolver = null;
    resolve(modalResult);
};

const showModal = (options = {}, alertOnly = false) => {
    const modal = document.getElementById('adminNoticeModal');
    const $ = window.jQuery;

    if (!modal || !$ || typeof $.fn?.modal !== 'function') {
        toast({ ...options, type: options.type || 'warning', autohide: false });
        return Promise.resolve(false);
    }

    if (modalResolver) {
        modalResult = false;
        resolveModal();
    }

    const type = normalizeType(options.type || (alertOnly ? 'info' : 'warning'));
    const config = TYPE_CONFIG[type];
    const header = document.getElementById('adminNoticeHeader');
    const icon = document.getElementById('adminNoticeIcon');
    const title = document.getElementById('adminNoticeTitleText');
    const message = document.getElementById('adminNoticeMessage');
    const cancel = document.getElementById('adminNoticeCancel');
    const confirm = document.getElementById('adminNoticeConfirm');

    header.className = `modal-header bg-${config.className}`;
    icon.className = `${options.icon || config.icon} mr-2`;
    title.textContent = options.title || (alertOnly ? config.title : 'Confirmar ação');
    message.textContent = String(options.message ?? '');
    cancel.textContent = options.cancelText || 'Cancelar';
    cancel.hidden = alertOnly;
    confirm.textContent = options.confirmText || (alertOnly ? 'Entendi' : 'Confirmar');
    confirm.className = `btn btn-${config.className}`;
    modalResult = false;

    return new Promise((resolve) => {
        modalResolver = resolve;
        $(modal).modal({ backdrop: 'static', keyboard: true, show: true });
    });
};

const modal = document.getElementById('adminNoticeModal');
const $ = window.jQuery;

if (modal && $ && typeof $.fn?.modal === 'function') {
    $(modal).on('show.bs.modal', () => document.body.classList.add('admin-notice-open'));
    $(modal).on('hidden.bs.modal', () => {
        document.body.classList.remove('admin-notice-open');
        resolveModal();
    });

    document.getElementById('adminNoticeConfirm')?.addEventListener('click', () => {
        modalResult = true;
        $(modal).modal('hide');
    });
}

window.AdminNotice = {
    toast,
    success: (message, title = 'Sucesso') => toast({ type: 'success', title, message }),
    error: (message, title = 'Erro') => toast({ type: 'danger', title, message, autohide: false }),
    warning: (message, title = 'Atenção') => toast({ type: 'warning', title, message }),
    info: (message, title = 'Informação') => toast({ type: 'info', title, message }),
    confirm: (options) => showModal(options, false),
    alert: (options) => showModal(options, true),
};

document.addEventListener('DOMContentLoaded', () => {
    const configElement = document.getElementById('adminNotificationsConfig');
    if (!configElement) return;

    try {
        const notifications = JSON.parse(configElement.textContent || '[]');
        notifications.forEach((notification, index) => {
            window.setTimeout(() => toast(notification), index * 180);
        });
    } catch (error) {
        console.error('Não foi possível carregar as notificações da página.', error);
    }
});
