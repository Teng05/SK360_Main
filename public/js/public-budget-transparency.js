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
            const allotted = Number(data.allotted), verified = Number(data.verified_expenses), remaining = Number(data.remaining_after_verified);
            if (![allotted, verified, remaining].every(Number.isFinite)) throw new Error('Invalid summary');
            context.textContent = `Barangay ${data.barangay_name} · FY ${data.fiscal_year} · Published ${data.published_at}`;
            for (const [key, value] of Object.entries({ allotted, verified, remaining })) dialog.querySelector(`[data-summary-${key}]`).textContent = currency.format(value);
            const percent = allotted > 0 ? Math.max(0, Math.min(100, remaining / allotted * 100)) : 0;
            dialog.querySelector('progress').value = percent;
            dialog.querySelector('[data-summary-progress]').textContent = allotted > 0
                ? `${percent.toFixed(1)}% of the published allotment remains after verified expenses.${remaining < 0 ? ' Verified expenses exceed the allotment.' : ''}`
                : `No positive allotment is published; a remaining percentage is unavailable.${remaining < 0 ? ' Verified expenses exceed the allotment.' : ''}`;
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
