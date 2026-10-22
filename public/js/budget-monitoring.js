(() => {
    const page = document.querySelector('[data-budget-page]');
    if (!page) return;
    const filters = page.querySelector('[data-financial-filters]');
    if (filters) {
        const year = filters.querySelector('[name="year"]');
        const barangay = filters.querySelector('[name="barangay_id"]');
        const clear = filters.querySelector('[data-clear-financial-filters]');
        const appliedNonDefault = year.value !== filters.dataset.defaultYear || Number(barangay?.value || 0) !== 0;
        const syncClear = () => {
            clear.hidden = !appliedNonDefault && year.value === filters.dataset.defaultYear && Number(barangay?.value || 0) === 0;
        };
        filters.addEventListener('change', syncClear);
        syncClear();
    }
    const openDialog = (id, trigger) => {
        const dialog = document.getElementById(id);
        if (!(dialog instanceof HTMLDialogElement) || !dialog.classList.contains('budget-dialog')) return;
        if (id !== 'expense-proof') document.querySelectorAll('.budget-dialog[open]').forEach(open => open.close());
        dialog._returnFocus = trigger;
        dialog.showModal();
    };
    document.addEventListener('click', event => {
        const proof = event.target.closest('[data-view-proof]');
        if (proof) previewProof(proof);
        const trigger = event.target.closest('[data-open-dialog]');
        if (trigger) openDialog(trigger.dataset.openDialog, trigger);
        if (event.target.closest('[data-close-dialog]')) event.target.closest('dialog')?.close();
    });
    document.querySelectorAll('[data-expense-form]').forEach(form => {
        const project = form.querySelector('[data-project-select]');
        const category = form.querySelector('[data-category-select]');
        const sync = () => {
            const isNew = project.value === 'new';
            form.querySelector('[data-new-project]').hidden = !isNew;
            form.querySelector('[data-existing-category]').hidden = isNew || !project.value;
            form.querySelector('[data-existing-category]').textContent = !isNew && project.value ? 'Category: ' + project.selectedOptions[0].dataset.category : '';
            form.querySelectorAll('[data-new-project] input, [data-new-project] select').forEach(field => { field.disabled = !isNew; field.required = isNew; });
            const custom = isNew && category.value === 'new';
            form.querySelector('[data-new-category]').hidden = !custom;
            const name = form.querySelector('[name="new_category"]'); name.disabled = !custom; name.required = custom;
        };
        project.addEventListener('change', sync); category.addEventListener('change', sync); sync();
        form.querySelector('[name="proof"]').addEventListener('change', () => { form.querySelector('[name="public_proof"]').checked = false; });
    });
    const proofDialog = document.getElementById('expense-proof');
    const image = document.getElementById('proof-image');
    const pdf = document.getElementById('proof-pdf');
    const loading = document.getElementById('proof-loading');
    const error = document.getElementById('proof-error');
    const viewport = document.getElementById('proof-viewport');
    const toolbar = document.getElementById('proof-toolbar');
    const pdfHelp = document.getElementById('proof-pdf-help');
    let proofRequest, objectUrl, zoom = 1, generation = 0;
    const clearProof = () => {
        generation++;
        proofRequest?.abort();
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = null;
        if (image) { image.onload = null; image.onerror = null; image.removeAttribute('src'); image.hidden = true; image.style.width = ''; }
        if (pdf) { pdf.onload = null; pdf.removeAttribute('src'); pdf.hidden = true; }
        if (viewport) viewport.hidden = true;
        if (toolbar) toolbar.hidden = true;
        if (pdfHelp) pdfHelp.hidden = true;
        document.getElementById('proof-open')?.removeAttribute('href');
        zoom = 1;
    };
    const proofError = message => {
        loading.hidden = true;
        viewport.hidden = true;
        toolbar.hidden = true;
        error.textContent = message;
        error.hidden = false;
    };
    async function previewProof(trigger) {
        if (!proofDialog) return;
        clearProof();
        const version = generation;
        error.hidden = true;
        loading.hidden = false;
        openDialog('expense-proof', trigger);
        try {
            const context = JSON.parse(trigger.dataset.viewProof);
            for (const key of ['description', 'barangay', 'amount', 'date', 'reference', 'filename']) {
                document.getElementById(`proof-${key}`).textContent = context[key] || 'Not supplied';
            }
            const url = new URL(context.url, location.href);
            if (url.origin !== location.origin) throw new Error('Invalid proof location.');
            proofRequest = new AbortController();
            const response = await fetch(url, {credentials: 'same-origin', cache: 'no-store', signal: proofRequest.signal, headers: {Accept: 'image/jpeg,image/png,application/pdf'}});
            if (!response.ok) throw new Error(response.status === 404 ? 'This proof is missing or unavailable.' : 'Unable to open this proof. Check your access and try again.');
            const mime = (response.headers.get('Content-Type') || '').split(';')[0].trim();
            if (!['image/jpeg', 'image/png', 'application/pdf'].includes(mime)) throw new Error('The file format is unsupported or your session has expired.');
            const blob = await response.blob();
            if (version !== generation || !proofDialog.open) return;
            viewport.hidden = false;
            if (mime === 'application/pdf') {
                pdf.onload = () => { if (version === generation) loading.hidden = true; };
                pdf.hidden = false;
                // Use the authenticated endpoint so native viewers can request PDF byte ranges.
                pdf.src = url.href + '#toolbar=0';
                pdfHelp.hidden = false;
                document.getElementById('proof-open').href = url.href;
            } else {
                objectUrl = URL.createObjectURL(blob);
                image.alt = `Proof of expense: ${context.description}`;
                image.onload = () => { if (version === generation) { loading.hidden = true; toolbar.hidden = false; } };
                image.onerror = () => { if (version === generation) proofError('The image could not be displayed. Please try again.'); };
                image.hidden = false;
                image.src = objectUrl;
            }
        } catch (failure) {
            if (failure.name !== 'AbortError' && version === generation) proofError(failure.message || 'Unable to preview proof.');
        }
    }
    proofDialog?.addEventListener('close', clearProof);
    document.querySelectorAll('[data-proof-zoom], [data-proof-reset]').forEach(button => button.addEventListener('click', () => {
        zoom = button.hasAttribute('data-proof-reset') ? 1 : Math.max(0.5, Math.min(4, zoom + Number(button.dataset.proofZoom)));
        image.style.width = `${zoom * 100}%`;
    }));
    document.querySelectorAll('.budget-dialog').forEach(dialog => {
        dialog.addEventListener('close', () => {
            const trigger = dialog._returnFocus;
            if (trigger && !trigger.closest('dialog:not([open])')) trigger.focus();
            else page.querySelector(`[data-open-dialog="${dialog.id.replace('edit-', '')}"]`)?.focus();
        });
    });
    if (page.dataset.reopen) {
        openDialog(page.dataset.reopen);
        const dialog = document.getElementById(page.dataset.reopen);
        if (dialog?.open) {
            const errors = page.querySelector('[role="alert"]');
            if (errors) {
                const notice = errors.cloneNode(true);
                notice.classList.add('m-6');
                dialog.querySelector('.budget-dialog-head')?.after(notice);
            }
        }
    }
})();
