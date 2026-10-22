(() => {
    const dialog = document.getElementById('public-financial-summary');
    if (!dialog) return;
    const status = dialog.querySelector('[data-summary-status]');
    const content = dialog.querySelector('[data-summary-content]');
    const context = dialog.querySelector('[data-summary-context]');
    const tabs = [...dialog.querySelectorAll('[role="tab"]')];
    const currency = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });
    let opener, pending;
    const selectTab = (index) => tabs.forEach((tab, i) => {
        tab.setAttribute('aria-selected', String(i === index));
        tab.tabIndex = i === index ? 0 : -1;
        document.getElementById(tab.getAttribute('aria-controls')).hidden = i !== index;
    });
    tabs.forEach((tab, i) => {
        tab.addEventListener('click', () => selectTab(i));
        tab.addEventListener('keydown', event => {
            if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
            event.preventDefault();
            const next = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : (i + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
            selectTab(next); tabs[next].focus();
        });
    });
    document.querySelectorAll('[data-financial-summary]').forEach(button => button.addEventListener('click', async () => {
        pending?.abort();
        const request = new AbortController(); pending = request; opener = button;
        content.hidden = true; context.textContent = ''; status.textContent = 'Loading published financial summary…';
        selectTab(0); dialog.showModal();
        try {
            const response = await fetch(button.dataset.financialSummary, { signal: request.signal, headers: { Accept: 'application/json' }, cache: 'no-store' });
            if (!response.ok) throw new Error('Unavailable');
            const data = await response.json();
            if (request.signal.aborted) return;
            const allotted = data.allotted === null ? null : Number(data.allotted), verified = Number(data.reported_expenses), remaining = data.remaining_estimate === null ? null : Number(data.remaining_estimate);
            if (![allotted, verified, remaining].every(value => value === null || Number.isFinite(value))) throw new Error('Invalid summary');
            context.textContent = `Barangay ${data.barangay_name} · FY ${data.fiscal_year} · Allocation published: ${data.published_at || 'Not yet published'}`;
            for (const [key, value] of Object.entries({ allotted, verified, remaining })) dialog.querySelector(`[data-summary-${key}]`).textContent = value === null ? 'Allocation not published' : currency.format(value);
            const percent = allotted > 0 ? Math.max(0, Math.min(100, remaining / allotted * 100)) : 0;
            dialog.querySelector('progress').value = percent;
            dialog.querySelector('[data-summary-progress]').textContent = allotted > 0
                ? `${percent.toFixed(1)}% of the published allotment remains after reported expenses.${remaining < 0 ? ' Reported expenses exceed the allotment.' : ''}`
                : `No positive allotment is published; a remaining percentage is unavailable.${remaining < 0 ? ' Reported expenses exceed the allotment.' : ''}`;
            const list = dialog.querySelector('[data-public-expenses]'); list.replaceChildren();
            const groups = new Map();
            const projects = new Map((data.projects || []).map(project => [String(project.project_id), project]));
            for (const expense of data.expenses || []) {
                const key = String(expense.project_id);
                if (!groups.has(key)) {
                    const group = document.createElement('article'); group.className = 'public-project-group';
                    const heading = document.createElement('h3'); heading.textContent = expense.project;
                    const category = document.createElement('p'); category.textContent = expense.category;
                    group.append(heading, category);
                    const project = projects.get(key);
                    if (project && project.expense_count > 1) {
                        const total = document.createElement('p'); total.textContent = 'Total reported: ' + currency.format(Number(project.reported_expenses)); group.append(total);
                    }
                    groups.set(key, group); list.append(group);
                }
                const article = document.createElement('div'); article.className = 'public-expense-record public-expense-record--with-action';
                const info = document.createElement('div'); info.className = 'public-expense-info';
                const title = document.createElement('h4'); title.textContent = expense.description;
                const details = document.createElement('p'); details.textContent = expense.expense_date;
                const amount = document.createElement('p'); amount.textContent = currency.format(Number(expense.amount));
                info.append(title, details, amount); article.append(info);
                if (expense.proof_url) {
                    const link = document.createElement('a'); link.className = 'public-expense-proof-button';
                    link.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>';
                    link.title = 'View Supporting Document';
                    link.href = expense.proof_url; link.target = '_blank'; link.rel = 'noopener noreferrer';
                    link.setAttribute('aria-label', 'View supporting document (opens in a new tab)'); article.append(link);
                } else { const note = document.createElement('p'); note.textContent = 'Supporting document is private.'; info.append(note); }
                groups.get(key).append(article);
            }
            if (!list.children.length) list.textContent = 'No reported expenses for this fiscal year.';
            status.textContent = ''; content.hidden = false;
        } catch (error) {
            if (error.name !== 'AbortError') status.textContent = 'This financial summary is unavailable. Close this window and try again.';
        }
    }));
    dialog.querySelector('[data-summary-close]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', event => {
        const rect = dialog.getBoundingClientRect();
        if (event.target === dialog && (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom)) dialog.close();
    });
    dialog.addEventListener('close', () => { pending?.abort(); content.hidden = true; opener?.focus(); });
})();
