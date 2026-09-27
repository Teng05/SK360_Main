{{-- File guide: Blade view template for resources/views/layouts/app.blade.php. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SK360')</title>
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
</body>
</html>
