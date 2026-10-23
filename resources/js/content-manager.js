import { onAdminPage } from './admin-lifecycle';

onAdminPage(() => {
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

    const sectionField = document.getElementById('newPageSection');
    const pageTypeField = document.getElementById('newPageType');
    const syncSectionField = () => {
        if (!sectionField) return;
        sectionField.disabled = pageTypeField?.value === 'home';
        if (sectionField.disabled) sectionField.value = '';
    };
    pageTypeField?.addEventListener('change', syncSectionField);
    syncSectionField();

    const openCreateDialog = (event) => {
        const section = event?.currentTarget?.dataset.createSection;
        if (section) {
            pageTypeField.value = 'interna';
            sectionField.value = section;
            syncSectionField();
        }
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
        document.querySelectorAll('[data-section-heading]').forEach((heading) => {
            heading.hidden = !pageRows.some((row) => !row.hidden && row.dataset.pageSection === heading.dataset.sectionHeading);
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

            await window.AdminNavigation?.submit(form);
        });
    });

    document.querySelectorAll('[data-section-order-form]').forEach((form) => {
        const list = form.querySelector('[data-section-order-list]');
        const save = form.querySelector('[data-save-section-order]');
        if (!save) return;
        let dragging;
        const changed = () => {
            save.disabled = false;
            form.querySelector('[data-section-order-status]').textContent = 'Ordem alterada. Clique em Salvar ordem para aplicar.';
        };
        list.addEventListener('click', (event) => {
            const button = event.target.closest('[data-move-page]');
            if (!button) return;
            const item = button.closest('[data-section-page]');
            const next = Number(button.dataset.movePage) < 0 ? item.previousElementSibling : item.nextElementSibling;
            if (!next) return;
            if (Number(button.dataset.movePage) < 0) next.before(item);
            else next.after(item);
            changed();
            button.focus();
        });
        list.addEventListener('dragstart', (event) => {
            dragging = event.target.closest('[data-section-page]');
            if (dragging) { event.dataTransfer.effectAllowed = 'move'; event.dataTransfer.setData('text/plain', dragging.dataset.sectionPage); }
        });
        list.addEventListener('dragover', (event) => { if (dragging) event.preventDefault(); });
        list.addEventListener('drop', (event) => {
            event.preventDefault();
            const target = event.target.closest('[data-section-page]');
            if (!dragging || !target || dragging === target) return;
            const before = event.clientY < target.getBoundingClientRect().top + target.offsetHeight / 2;
            if (before) target.before(dragging);
            else target.after(dragging);
            changed();
        });
        list.addEventListener('dragend', () => { dragging = null; });
    });

    applyFilters();
});
