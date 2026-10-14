document.addEventListener('DOMContentLoaded', () => {
    const manager = document.getElementById('contentManager');

    if (!manager) {
        return;
    }

    const dialog = document.getElementById('createPageDialog');
    const searchField = document.getElementById('pageSearch');
    const pageRows = [...document.querySelectorAll('[data-page-row]')];
    const filterButtons = [...document.querySelectorAll('[data-page-filter]')];
    const noResults = document.getElementById('noPageResults');
    const visibleCount = document.getElementById('visiblePagesCount');
    let activeFilter = 'todas';

    const normalize = (value) => String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLocaleLowerCase('pt-BR')
        .trim();

    const openCreateDialog = () => {
        if (!dialog) {
            return;
        }

        if (!dialog.open) {
            dialog.showModal();
        }

        window.setTimeout(() => {
            document.getElementById('newPageTitle')?.focus();
        }, 30);
    };

    const closeCreateDialog = () => {
        if (dialog?.open) {
            dialog.close();
        }
    };

    document.getElementById('openCreatePage')?.addEventListener('click', openCreateDialog);
    document.querySelectorAll('[data-open-create-page]').forEach((button) => {
        button.addEventListener('click', openCreateDialog);
    });
    document.querySelectorAll('[data-close-dialog]').forEach((button) => {
        button.addEventListener('click', closeCreateDialog);
    });

    dialog?.addEventListener('click', (event) => {
        if (event.target === dialog) {
            closeCreateDialog();
        }
    });

    if (manager.dataset.openCreate === '1') {
        openCreateDialog();
    }

    const applyFilters = () => {
        const term = normalize(searchField?.value);
        let shown = 0;

        pageRows.forEach((row) => {
            const matchesSearch = normalize(row.dataset.pageName).includes(term);
            const filters = String(row.dataset.pageFilters || '').split(/\s+/);
            const matchesFilter = activeFilter === 'todas' || filters.includes(activeFilter);
            const shouldShow = matchesSearch && matchesFilter;

            row.hidden = !shouldShow;

            if (shouldShow) {
                shown += 1;
            }
        });

        if (visibleCount) {
            visibleCount.textContent = `${shown} ${shown === 1 ? 'exibida' : 'exibidas'}`;
        }

        if (noResults) {
            noResults.hidden = shown > 0 || pageRows.length === 0;
        }
    };

    searchField?.addEventListener('input', applyFilters);

    filterButtons.forEach((button) => {
        button.addEventListener('click', () => {
            activeFilter = button.dataset.pageFilter || 'todas';

            filterButtons.forEach((item) => {
                const selected = item === button;
                item.classList.toggle('is-active', selected);
                item.setAttribute('aria-pressed', String(selected));
            });

            applyFilters();
        });
    });

    document.querySelectorAll('[data-delete-page-form]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const pageTitle = form.dataset.pageTitle || 'esta página';
            const confirmed = await window.AdminNotice?.confirm({
                type: 'danger',
                icon: 'fas fa-trash-alt',
                title: 'Excluir página?',
                message: `Excluir “${pageTitle}” permanentemente?\n\nO conteúdo salvo será removido e botões de outras páginas que apontem para ela deixarão de funcionar.`,
                confirmText: 'Excluir página',
            });

            if (!confirmed) return;

            form.querySelector('button[type="submit"]')?.setAttribute('disabled', 'disabled');
            form.submit();
        });
    });

    const createForm = document.getElementById('createPageForm');
    const createSubmit = document.getElementById('createPageSubmit');

    createForm?.addEventListener('submit', () => {
        if (createSubmit) {
            createSubmit.disabled = true;
            createSubmit.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Criando…';
        }
    });

    applyFilters();
});
