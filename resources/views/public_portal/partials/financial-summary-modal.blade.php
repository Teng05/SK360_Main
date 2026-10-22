<dialog id="public-financial-summary" class="public-finance-modal" aria-labelledby="public-finance-title">
    <div class="public-finance-header">
        <div><h2 id="public-finance-title">Financial Transparency</h2><p data-summary-context></p></div>
        <button type="button" data-summary-close aria-label="Close financial summary" autofocus>Close</button>
    </div>
    <p data-summary-status role="status" aria-live="polite"></p>
    <div data-summary-content hidden>
        <div class="public-finance-tabs" role="tablist" aria-label="Financial summary sections">
            <button type="button" id="public-summary-tab" role="tab" aria-controls="public-summary-panel" aria-selected="true">Summary</button>
            <button type="button" id="public-expenses-tab" role="tab" aria-controls="public-expenses-panel" aria-selected="false" tabindex="-1">Expenses</button>
        </div>
        <section id="public-summary-panel" role="tabpanel" aria-labelledby="public-summary-tab" tabindex="0">
            <div class="public-finance-metrics">
                <div><h3>Allotted Budget</h3><p data-summary-allotted></p></div>
                <div><h3>Reported Expenses</h3><p data-summary-verified></p></div>
                <div><h3>Estimated Remaining Budget</h3><p data-summary-remaining></p></div>
            </div>
            <label for="public-finance-progress">Estimated remaining budget</label>
            <progress id="public-finance-progress" max="100" value="0"></progress>
            <p data-summary-progress></p>
            <p class="public-finance-note">This dated, authorized snapshot excludes pending expenses. Estimated remaining budget is not actual cash on hand or the bank balance; fund releases may occur separately.</p>
        </section>
        <section id="public-expenses-panel" role="tabpanel" aria-labelledby="public-expenses-tab" tabindex="0" hidden>
            <p class="public-finance-note">Reported expenses and supporting documents authorized for public disclosure. Private and historical proofs are excluded.</p>
            <div data-public-expenses></div>
        </section>
    </div>
</dialog>
