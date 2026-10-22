@if(($pendingSetupCount ?? 0) > 3)
<form onclick="event.stopPropagation()" onkeydown="event.stopPropagation()" method="POST" action="{{ route('sk_pres.user-management.resend-pending-setup-links') }}"
    onsubmit="if (!confirm('Resend setup links for pending accounts? Only Chairmen or President accounts with valid pending assignments in the current administration will receive links. Previous links will be replaced.')) return false; this.querySelector('button').disabled=true; this.querySelector('[data-resend-label]').textContent='Sending links...';">
    @csrf
    <button type="submit" class="sk-btn sk-btn--secondary sk-btn--sm" title="Resend setup links to pending Chairmen and President accounts">
        @include('partials.ui.icon', ['icon'=>'mail','iconSize'=>15])
        <span data-resend-label>Resend Setup Links</span>
    </button>
</form>
@endif
