<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
</body>
</html>
