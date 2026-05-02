{{-- File guide: Blade view template for resources/views/sk_secretary/partials/sidebar.blade.php. --}}
<div class="w-64 bg-red-600 text-white flex flex-col p-3 overflow-y-auto">
    {{-- Secretary sidebar logo --}}
    <div class="flex items-center gap-2 mb-3">
        <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" class="w-7 h-7" alt="logo">
        <h2 class="text-base font-bold">SK 360&deg;</h2>
    </div>

    {{-- Secretary user card --}}
    <div class="bg-red-500 rounded-lg p-2 flex items-center gap-2 mb-3 shadow text-xs">
        <div class="bg-yellow-400 text-red-600 p-1 rounded-full text-sm">&#128100;</div>
        <div>
            <p class="font-semibold text-xs">{{ $fullName }}</p>
            <p class="text-xs opacity-80">SK Secretary</p>
        </div>
    </div>

    {{-- Secretary navigation links --}}
    <nav class="space-y-1 text-xs">
        @foreach ($menuItems as $item)
            @php $isActive = $item['link'] === $currentUrl; @endphp
            <a href="{{ $item['link'] }}" class="flex items-center gap-2 p-2 rounded-lg {{ $isActive ? 'bg-red-500 shadow-inner' : 'hover:bg-red-500 transition' }}">
                <span class="{{ $isActive ? 'bg-yellow-400 text-red-600' : 'bg-red-400' }} p-1 rounded text-sm">{!! $item['icon'] !!}</span>
                <span class="{{ $isActive ? 'text-yellow-300 font-semibold' : '' }}">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>
</div>
