import './stimulus_bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */

const sidebar = document.querySelector('#admin-sidebar');
const openSidebar = document.querySelector('[data-sidebar-open]');
const closeSidebar = document.querySelectorAll('[data-sidebar-close]');

openSidebar?.addEventListener('click', () => document.body.classList.add('sidebar-is-open'));
closeSidebar.forEach((element) => element.addEventListener('click', () => document.body.classList.remove('sidebar-is-open')));

const siteMenu = document.querySelector('[data-site-menu]');
siteMenu?.addEventListener('click', () => document.body.classList.toggle('site-menu-is-open'));
document.querySelectorAll('#site-navigation a').forEach((link) => link.addEventListener('click', () => document.body.classList.remove('site-menu-is-open')));

const initializeSystemSettings = () => {
    const form = document.querySelector('form[name="system_settings"]');
    if (!form || form.dataset.behaviourReady === 'true') return;
    form.dataset.behaviourReady = 'true';

    const fields = (names) => names.map((name) => form.querySelector(`[name="system_settings[${name}]"]`)?.closest('.settings-field')).filter(Boolean);
    const maintenanceFields = fields(['maintenance_date', 'maintenance_time', 'maintenance_message']);
    const sendmailFields = fields(['sendmail_path']);
    const smtpFields = fields(['smtp_host', 'smtp_user', 'smtp_password', 'smtp_port', 'smtp_ssl']);
    const selectedRadio = (name) => form.querySelector(`[name="system_settings[${name}]"]:checked`)?.value;
    const toggle = (elements, visible) => elements.forEach((element) => { element.hidden = !visible; });

    const refresh = () => {
        toggle(maintenanceFields, selectedRadio('maintenance') === '1');
        const mailer = form.querySelector('[name="system_settings[mailer]"]')?.value;
        toggle(sendmailFields, mailer === 'SMAIL');
        toggle(smtpFields, mailer === 'SMTP');
    };

    form.addEventListener('change', refresh);
    refresh();
};

document.addEventListener('turbo:load', initializeSystemSettings);
initializeSystemSettings();

const initializeMenuSorting = () => {
    document.querySelectorAll('[data-menu-sort]').forEach((container) => {
        if (container.dataset.sortReady === 'true') return;
        container.dataset.sortReady = 'true';
        const status = container.querySelector('[data-menu-sort-status]');
        let draggedRow = null;

        const clearDropState = () => {
            container.classList.remove('is-menu-dragging');
            container.querySelectorAll('.is-drop-parent, .is-drop-group, .is-drop-before, .is-drop-after').forEach((element) => {
                element.classList.remove('is-drop-parent', 'is-drop-group', 'is-drop-before', 'is-drop-after');
            });
        };

        const moveItem = async (parentId, place) => {
            const itemId = draggedRow?.dataset.menuId;
            if (!itemId) return;
            const body = new URLSearchParams({
                _token: container.dataset.reorderToken,
                item: itemId,
                parent: parentId || '0',
                place,
            });
            status.textContent = 'Przenoszenie…';
            status.className = 'menu-sort-status is-saving';
            try {
                const response = await fetch(container.dataset.moveUrl, { method: 'POST', body, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || 'Nie udało się przenieść pozycji.');
                status.textContent = 'Hierarchia zapisana';
                status.className = 'menu-sort-status is-saved';
                window.location.reload();
            } catch (error) {
                status.textContent = error.message;
                status.className = 'menu-sort-status is-error';
                clearDropState();
            }
        };

        const reorderItems = async (targetRow, insertAfter) => {
            const sourceParent = draggedRow?.dataset.menuParent || '0';
            const group = targetRow.closest('[data-menu-sort-group]');
            const siblings = [...group.querySelectorAll('[data-menu-row]')]
                .filter((row) => row.dataset.menuParent === sourceParent && row !== draggedRow);
            const targetIndex = siblings.indexOf(targetRow);
            siblings.splice(targetIndex + (insertAfter ? 1 : 0), 0, draggedRow);
            const body = new URLSearchParams({ _token: container.dataset.reorderToken });
            siblings.forEach((row) => body.append('items[]', row.dataset.menuId));
            status.textContent = 'Zapisywanie…';
            status.className = 'menu-sort-status is-saving';
            try {
                const response = await fetch(container.dataset.reorderUrl, { method: 'POST', body, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || 'Nie udało się zapisać kolejności.');
                status.textContent = 'Kolejność zapisana';
                status.className = 'menu-sort-status is-saved';
                window.location.reload();
            } catch (error) {
                status.textContent = error.message;
                status.className = 'menu-sort-status is-error';
                clearDropState();
            }
        };

        container.querySelectorAll('[data-menu-drag-handle]').forEach((handle) => {
            handle.addEventListener('dragstart', (event) => {
                draggedRow = handle.closest('[data-menu-row]');
                draggedRow?.classList.add('is-dragging');
                container.classList.add('is-menu-dragging');
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', draggedRow?.dataset.menuId || '');
            });
            handle.addEventListener('dragend', () => {
                draggedRow?.classList.remove('is-dragging');
                draggedRow = null;
                clearDropState();
            });
        });

        container.querySelectorAll('[data-menu-sort-group]').forEach((group) => {
            const groupDrop = group.querySelector('[data-menu-group-drop]');
            groupDrop?.addEventListener('dragover', (event) => {
                if (!draggedRow) return;
                event.preventDefault();
                event.stopPropagation();
                event.dataTransfer.dropEffect = 'move';
                groupDrop.classList.add('is-drop-group');
            });
            groupDrop?.addEventListener('dragleave', () => groupDrop.classList.remove('is-drop-group'));
            groupDrop?.addEventListener('drop', (event) => {
                if (!draggedRow) return;
                event.preventDefault();
                event.stopPropagation();
                void moveItem('0', group.dataset.menuPlace);
            });

            group.querySelectorAll('[data-menu-row]').forEach((targetRow) => {
                const nestTarget = targetRow.querySelector('[data-menu-nest-target]');
                const markAsParent = (event) => {
                    if (!draggedRow || targetRow === draggedRow) return false;
                    event.preventDefault();
                    event.stopPropagation();
                    event.dataTransfer.dropEffect = 'move';
                    targetRow.classList.add('is-drop-parent');
                    return true;
                };
                nestTarget?.addEventListener('dragover', markAsParent);
                nestTarget?.addEventListener('dragleave', () => targetRow.classList.remove('is-drop-parent'));
                nestTarget?.addEventListener('drop', (event) => {
                    if (!markAsParent(event)) return;
                    void moveItem(targetRow.dataset.menuId, group.dataset.menuPlace);
                });

                targetRow.addEventListener('dragover', (event) => {
                    if (!draggedRow || targetRow === draggedRow) return;
                    const samePlace = draggedRow.closest('[data-menu-sort-group]') === group;
                    const sameParent = draggedRow.dataset.menuParent === targetRow.dataset.menuParent;
                    if (!samePlace || !sameParent) {
                        markAsParent(event);
                        return;
                    }
                    event.preventDefault();
                    event.dataTransfer.dropEffect = 'move';
                    const after = event.clientY >= targetRow.getBoundingClientRect().top + targetRow.getBoundingClientRect().height / 2;
                    targetRow.classList.toggle('is-drop-before', !after);
                    targetRow.classList.toggle('is-drop-after', after);
                });
                targetRow.addEventListener('dragleave', (event) => {
                    if (!targetRow.contains(event.relatedTarget)) targetRow.classList.remove('is-drop-parent');
                });
                targetRow.addEventListener('drop', (event) => {
                    if (!draggedRow || targetRow === draggedRow) return;
                    const samePlace = draggedRow.closest('[data-menu-sort-group]') === group;
                    const sameParent = draggedRow.dataset.menuParent === targetRow.dataset.menuParent;
                    if (!samePlace || !sameParent) {
                        if (markAsParent(event)) void moveItem(targetRow.dataset.menuId, group.dataset.menuPlace);
                        return;
                    }
                    event.preventDefault();
                    event.stopPropagation();
                    const after = event.clientY >= targetRow.getBoundingClientRect().top + targetRow.getBoundingClientRect().height / 2;
                    void reorderItems(targetRow, after);
                });
            });
        });
    });
};

document.addEventListener('turbo:load', initializeMenuSorting);
initializeMenuSorting();

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');
