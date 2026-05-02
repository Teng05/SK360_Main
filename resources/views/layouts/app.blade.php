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
            <style>
                {!! file_get_contents(resource_path('css/app.css')) !!}
            </style>
        @endif
    @endif
</head>
<body>
    @yield('content')

    @stack('scripts')
    @auth
    <script>
        (function () {
            const notifBtn = document.getElementById('notifBtn');
            const notifDropdown = document.getElementById('notifDropdown');

            if (!notifBtn || !notifDropdown) {
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const feedUrl = @json(route('notifications.feed'));
            const readBaseUrl = @json(url('/notifications'));

            if (!notifBtn.classList.contains('relative')) {
                notifBtn.classList.add('relative');
            }

            let badge = notifBtn.querySelector('[data-notification-badge]');
            if (!badge) {
                badge = document.createElement('span');
                badge.setAttribute('data-notification-badge', 'true');
                badge.className = 'hidden absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-yellow-400 text-red-700 text-[10px] font-bold flex items-center justify-center';
                notifBtn.appendChild(badge);
            }

            function render(payload) {
                const unreadCount = payload.unread_count || 0;
                const notifications = payload.notifications || [];

                badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
                badge.classList.toggle('hidden', unreadCount === 0);

                const body = notifications.length === 0
                    ? '<div class="px-4 py-3 text-sm text-gray-700">No notifications yet</div>'
                    : notifications.map((notification) => {
                        const wrapperClasses = notification.is_read
                            ? 'px-4 py-3 border-b border-gray-100 bg-white'
                            : 'px-4 py-3 border-b border-gray-100 bg-red-50';

                        return `
                            <a href="${notification.url || '#'}" data-notification-link data-id="${notification.id}" class="block ${wrapperClasses}">
                                <div class="text-sm font-semibold text-gray-800">${notification.title}</div>
                                <div class="mt-1 text-xs text-gray-600">${notification.message}</div>
                                <div class="mt-1 text-[11px] text-gray-400">${notification.created_at || ''}</div>
                            </a>
                        `;
                    }).join('');

                notifDropdown.innerHTML = `
                    <div class="px-4 py-3 font-semibold border-b text-gray-800">Notifications</div>
                    <div class="max-h-64 overflow-y-auto">${body}</div>
                `;
            }

            async function fetchFeed() {
                try {
                    const response = await fetch(feedUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        return;
                    }

                    render(await response.json());
                } catch (error) {
                    console.error('Notification fetch failed', error);
                }
            }

            notifDropdown.addEventListener('click', async (event) => {
                const link = event.target.closest('[data-notification-link]');

                if (!link) {
                    return;
                }

                const id = link.getAttribute('data-id');

                try {
                    await fetch(`${readBaseUrl}/${id}/read`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                    });
                } catch (error) {
                    console.error('Notification read update failed', error);
                }
            });

            fetchFeed();
            setInterval(fetchFeed, 5000);
        })();
    </script>
    @endauth
</body>
</html>
