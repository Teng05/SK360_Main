{{-- File guide: Blade view template for resources/views/layouts/app.blade.php. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SK 360 Dashboard')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}?v={{ @filemtime(public_path('images/logo.png')) }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}?v={{ @filemtime(public_path('images/logo.png')) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    @hasSection('page_css')
        @yield('page_css')
    @else
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @elseif (file_exists(resource_path('css/app.css')))
            <style>{!! file_get_contents(resource_path('css/app.css')) !!}</style>
        @endif
    @endif
    @if (!empty($menuItems) && request()->routeIs('sk_pres.*', 'sk_chairman.*', 'sk_secretary.*') && !request()->routeIs('*.meetings.call'))
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('css/sk360-web.css') }}?v={{ @filemtime(public_path('css/sk360-web.css')) }}">
    @endif
</head>
<style>
    .sk-password-toggle-wrap{position:relative!important}
    .sk-password-toggle-wrap>input[type=password],.sk-password-toggle-wrap>input[type=text]{padding-right:48px!important}
    .sk-password-toggle{position:absolute;right:12px;top:50%;display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;transform:translateY(-50%);border:0;border-radius:8px;background:transparent;color:#64748b;cursor:pointer}
    .sk-password-toggle:hover{background:rgba(148,163,184,.12);color:#1e293b}
    .sk-password-toggle:focus-visible{outline:3px solid rgba(201,35,54,.2);outline-offset:1px}
</style>
@php($skRoleWeb = !empty($menuItems) && request()->routeIs('sk_pres.*', 'sk_chairman.*', 'sk_secretary.*') && !request()->routeIs('*.meetings.call'))
<body class="{{ $skRoleWeb ? 'sk-web sk-app' : '' }}">
    @yield('content')
    @stack('scripts')
    @if($skRoleWeb)
    <script>
        (function(){
            const body=document.body;
            const closeDrawer=()=>body.classList.remove('sk-nav-open');
            document.addEventListener('click',function(event){
                if(event.target.closest('[data-sk-sidebar-open]')){body.classList.add('sk-nav-open');return;}
                if(event.target.closest('[data-sk-sidebar-close]')) closeDrawer();
                const trigger=event.target.closest('[data-sk-dropdown]');
                document.querySelectorAll('[data-sk-dropdown]').forEach(function(button){
                    const menu=document.getElementById(button.getAttribute('data-sk-dropdown'));
                    if(!menu)return;
                    if(button===trigger) menu.classList.toggle('hidden');
                    else if(!menu.contains(event.target)) menu.classList.add('hidden');
                });
            });
            document.addEventListener('keydown',event=>{if(event.key==='Escape')closeDrawer();});
        })();
    </script>
    @endif
    @auth
    <script>
        (function () {
            const notifBtn = document.getElementById('notifBtn');
            const notifDropdown = document.getElementById('notifDropdown');
            if (!notifBtn || !notifDropdown) return;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const feedUrl = @json(route('notifications.feed'));
            const readBaseUrl = @json(url('/notifications'));
            if (!notifBtn.classList.contains('relative')) notifBtn.classList.add('relative');
            let badge = notifBtn.querySelector('[data-notification-badge]');
            if (!badge) {
                badge = document.createElement('span');
                badge.setAttribute('data-notification-badge', 'true');
                badge.className = 'hidden absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-red-600 text-white text-[10px] font-bold flex items-center justify-center';
                notifBtn.appendChild(badge);
            }
            function render(payload) {
                const unreadCount = payload.unread_count || 0;
                const notifications = payload.notifications || [];
                badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
                badge.classList.toggle('hidden', unreadCount === 0);
                const body = notifications.length === 0
                    ? '<div class="sk-dropdown__empty">No notifications yet</div>'
                    : notifications.map((notification) => {
                        const stateClass = notification.is_read ? '' : 'is-unread';
                        return `<a href="${notification.url || '#'}" data-notification-link data-id="${notification.id}" class="sk-notification ${stateClass}"><span class="sk-notification__dot"></span><span class="min-w-0"><span class="sk-notification__title block">${notification.title}</span><span class="sk-notification__message block">${notification.message}</span><span class="sk-notification__time block">${notification.created_at || ''}</span></span></a>`;
                    }).join('');
                const caption = unreadCount > 0 ? `${unreadCount} unread` : 'You are all caught up';
                notifDropdown.innerHTML = `<div class="sk-dropdown__header">Notifications<span class="sk-dropdown__caption">${caption}</span></div><div class="sk-dropdown__list">${body}</div>`;
            }
            async function fetchFeed() {
                try {
                    const response = await fetch(feedUrl,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},credentials:'same-origin'});
                    if(response.ok) render(await response.json());
                } catch(error){console.error('Notification fetch failed',error);}
            }
            notifDropdown.addEventListener('click',async(event)=>{
                const link=event.target.closest('[data-notification-link]'); if(!link)return;
                try{await fetch(`${readBaseUrl}/${link.getAttribute('data-id')}/read`,{method:'POST',headers:{'X-CSRF-TOKEN':csrfToken,'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},credentials:'same-origin'});}catch(error){console.error('Notification read update failed',error);}
            });
            fetchFeed(); setInterval(fetchFeed,5000);
        })();
    </script>
    @endauth
    <script>
        (function(){
            function eyeSvg(open){return open
                ? '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>'
                : '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8M9.9 4.2A10.8 10.8 0 0 1 12 4c6.5 0 10 8 10 8a18 18 0 0 1-3.1 4.4M6.6 6.6C3.7 8.5 2 12 2 12s3.5 8 10 8a10.8 10.8 0 0 0 3-.4"/></svg>';}
            document.querySelectorAll('input[type="password"]').forEach(function(input){
                if(input.closest('.pf-pass')||input.dataset.passwordToggle==='false'||input.parentElement.querySelector('[data-toggle-pass]'))return;
                const wrap=input.parentElement;
                wrap.classList.add('sk-password-toggle-wrap');
                const button=document.createElement('button');
                button.type='button'; button.className='sk-password-toggle'; button.setAttribute('aria-label','Show password'); button.setAttribute('title','Show password');
                button.innerHTML=eyeSvg(false);
                button.addEventListener('click',function(){const show=input.type==='password';input.type=show?'text':'password';button.innerHTML=eyeSvg(show);button.setAttribute('aria-label',show?'Hide password':'Show password');button.setAttribute('title',show?'Hide password':'Show password');});
                wrap.appendChild(button);
            });
        })();
    </script>
</body>
</html>
