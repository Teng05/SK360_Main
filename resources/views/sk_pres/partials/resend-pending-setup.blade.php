@if(($pendingSetupCount ?? 0) > 3)
<form method="POST" action="{{ route('sk_pres.user-management.resend-pending-setup-links') }}"
    onsubmit="if (!confirm('Send new setup links to all {{ (int) $pendingSetupCount }} pending accounts in the current administration? Previous setup links will be replaced.')) return false; this.querySelector('button').disabled=true; this.querySelector('[data-resend-label]').textContent='Sending links...';">
    @csrf
    <button type="submit" class="sk-btn sk-btn--secondary sk-btn--sm" title="Resend setup links to pending Chairmen and President accounts">
        @include('partials.ui.icon', ['icon'=>'mail','iconSize'=>15])
        <span data-resend-label>Resend Setup Links ({{ $pendingSetupCount }})</span>
    </button>
</form>
@endif
