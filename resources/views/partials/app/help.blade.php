@php
$helpRole=auth()->user()->role;
$helpTopics=\App\Support\HelpSupport::topics($helpRole);
$helpRoleTopics=array_keys($helpTopics);
$helpCurrent=\App\Support\HelpSupport::currentTopic(request()->route()?->getName() ?? '', (string) request('section', ''));
$helpVisibleTopics=array_values(array_filter($helpRoleTopics,fn($topic)=>$topic!==$helpCurrent));
@endphp
<style>
.sk-help-answer p{margin:0 0 12px}.sk-help-answer p:last-child{margin-bottom:0}
.sk-help-button{position:fixed;right:24px;bottom:24px;z-index:80;width:54px;height:54px;display:flex;align-items:center;justify-content:center;border:0;border-radius:50%;background:#c92336;color:#fff;box-shadow:0 8px 24px rgba(15,23,42,.22);cursor:pointer;transition:transform .18s,background .18s}.sk-help-button:hover{background:#aa1e2e;transform:translateY(-2px)}.sk-help-button:focus-visible,.sk-help-close:focus-visible,.sk-help-topic:focus-visible{outline:3px solid rgba(201,35,54,.3);outline-offset:3px}.sk-help-backdrop{position:fixed;inset:0;z-index:90;background:rgba(15,23,42,.38);opacity:0;visibility:hidden;transition:opacity .22s,visibility .22s}.sk-help-drawer{position:fixed;inset:0 0 0 auto;z-index:91;width:min(420px,100vw);height:100dvh;display:flex;flex-direction:column;background:#fff;box-shadow:-16px 0 40px rgba(15,23,42,.16);transform:translateX(102%);transition:transform .24s ease}.sk-help-open .sk-help-backdrop{opacity:1;visibility:visible}.sk-help-open .sk-help-drawer{transform:translateX(0)}.sk-help-lock{overflow:hidden}.sk-help-header{display:flex;align-items:center;justify-content:space-between;padding:22px 22px 16px;border-bottom:1px solid #e5e7eb}.sk-help-header h2{margin:0;color:#111827;font-size:18px;font-weight:700}.sk-help-close{display:flex;width:36px;height:36px;align-items:center;justify-content:center;border:0;border-radius:9px;background:#f3f4f6;color:#374151;cursor:pointer}.sk-help-search{padding:16px 20px}.sk-help-search input{width:100%;height:42px;padding:0 13px;border:1px solid #d1d5db;border-radius:10px;color:#111827;outline:none}.sk-help-search input:focus{border-color:#c92336;box-shadow:0 0 0 3px rgba(201,35,54,.12)}.sk-help-content{overflow:auto;padding:0 20px 24px}.sk-help-label{margin:16px 0 9px;color:#6b7280;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase}.sk-help-card{margin-bottom:10px;overflow:hidden;border:1px solid #e5e7eb;border-radius:12px}.sk-help-topic{width:100%;display:flex;align-items:center;justify-content:space-between;padding:14px;text-align:left;border:0;background:#fff;color:#1f2937;font:inherit;font-size:14px;font-weight:600;cursor:pointer}.sk-help-topic:hover{background:#f9fafb}.sk-help-topic svg{flex:none;transition:transform .18s}.sk-help-card[open] .sk-help-topic svg{transform:rotate(180deg)}.sk-help-answer{padding:0 14px 14px;color:#4b5563;font-size:13px;line-height:1.6}.sk-help-empty{display:none;padding:12px 2px;color:#6b7280;font-size:13px}@media(max-width:640px){.sk-help-button{right:16px;bottom:max(16px,env(safe-area-inset-bottom));width:48px;height:48px}.sk-help-header{padding:18px 16px 14px}.sk-help-search{padding:14px 16px}.sk-help-content{padding:0 16px 20px}}
</style>
<div id="skHelpRoot">
    <button class="sk-help-button" type="button" data-help-open aria-label="Open Help & Support" title="Help & Support">
        @include('partials.ui.icon',['icon'=>'circle-help','iconSize'=>25,'iconClass'=>'text-white'])
    </button>
    <div class="sk-help-backdrop" data-help-close></div>
    <aside class="sk-help-drawer" role="dialog" aria-modal="true" aria-labelledby="skHelpTitle" aria-hidden="true" tabindex="-1" inert>
        <div class="sk-help-header"><h2 id="skHelpTitle">Help &amp; Support</h2><button class="sk-help-close" type="button" data-help-close aria-label="Close help">@include('partials.ui.icon',['icon'=>'x','iconSize'=>18])</button></div>
        <label class="sk-help-search"><input type="search" data-help-search placeholder="Search help topics..." aria-label="Search help topics"></label>
        <div class="sk-help-content" data-help-content>
            @if($helpCurrent && isset($helpTopics[$helpCurrent]) && in_array($helpCurrent,$helpRoleTopics,true))
                <p class="sk-help-label">Help for this page</p><details class="sk-help-card" data-help-card open><summary class="sk-help-topic">{{ $helpTopics[$helpCurrent][0] }}@include('partials.ui.icon',['icon'=>'chevron-down','iconSize'=>17])</summary><div class="sk-help-answer">@foreach($helpTopics[$helpCurrent][1] as $instruction)<p>{{ $instruction }}</p>@endforeach</div></details>
            @endif
            <p class="sk-help-label">Other Topics</p>
            @foreach($helpVisibleTopics as $topic)
                <details class="sk-help-card" data-help-card><summary class="sk-help-topic">{{ $helpTopics[$topic][0] }}@include('partials.ui.icon',['icon'=>'chevron-down','iconSize'=>17])</summary><div class="sk-help-answer">@foreach($helpTopics[$topic][1] as $instruction)<p>{{ $instruction }}</p>@endforeach</div></details>
            @endforeach
            <p class="sk-help-empty" data-help-empty>No matching help topics.</p>
        </div>
    </aside>
</div>
<script>
(function(){
    const root=document.getElementById('skHelpRoot');
    if(!root)return;
    const drawer=root.querySelector('.sk-help-drawer');
    const search=root.querySelector('[data-help-search]');
    const cards=[...root.querySelectorAll('[data-help-card]')];
    let previousFocus=null;
    let alreadyLocked=false;
    function close(){
        root.classList.remove('sk-help-open');
        if(!alreadyLocked)document.body.classList.remove('sk-help-lock');
        drawer.setAttribute('aria-hidden','true');
        drawer.inert=true;
        previousFocus?.focus();
    }
    root.addEventListener('click',event=>{
        if(event.target.closest('[data-help-open]')){
            previousFocus=document.activeElement;
            alreadyLocked=document.body.classList.contains('sk-help-lock');
            drawer.inert=false;
            root.classList.add('sk-help-open');
            document.body.classList.add('sk-help-lock');
            drawer.setAttribute('aria-hidden','false');
            search.focus();
        }
        if(event.target.closest('[data-help-close]'))close();
    });
    document.addEventListener('keydown',event=>{
        if(!root.classList.contains('sk-help-open'))return;
        if(event.key==='Escape'){event.preventDefault();close();return;}
        if(event.key!=='Tab')return;
        const controls=[...drawer.querySelectorAll('button,input,summary,a[href],[tabindex="0"]')]
            .filter(element=>!element.disabled && element.getClientRects().length);
        const first=controls[0],last=controls[controls.length-1];
        if(event.shiftKey&&(document.activeElement===first||!drawer.contains(document.activeElement))){event.preventDefault();last?.focus();}
        else if(!event.shiftKey&&(document.activeElement===last||!drawer.contains(document.activeElement))){event.preventDefault();first?.focus();}
    });
    search.addEventListener('input',()=>{
        const query=search.value.trim().toLocaleLowerCase();
        let shown=0;
        cards.forEach(card=>{
            const match=card.textContent.toLocaleLowerCase().includes(query);
            card.hidden=!match;
            if(match){shown++;if(query)card.open=true;}
        });
        root.querySelector('[data-help-empty]').style.display=shown?'none':'block';
        root.querySelectorAll('.sk-help-label').forEach(label=>{
            let sibling=label.nextElementSibling,hasVisible=false;
            while(sibling&&!sibling.classList.contains('sk-help-label')){
                if(sibling.matches('[data-help-card]')&&!sibling.hidden)hasVisible=true;
                sibling=sibling.nextElementSibling;
            }
            label.hidden=!hasVisible;
        });
    });
})();
</script>
