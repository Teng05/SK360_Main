{{-- File guide: Blade view template for resources/views/sk_secretary/partials/sidebar.blade.php. --}}
<div class="w-64 bg-red-600 text-white flex flex-col p-3 overflow-y-auto">
    {{-- Secretary sidebar logo --}}
    <div class="flex items-center gap-3 mb-4">
        <img src="{{ asset('images/logo.png') }}" class="w-8 h-8 rounded-full object-cover" alt="logo">
        <div class="leading-tight">
            <h2 class="text-lg font-extrabold tracking-wide">SK 360&deg;</h2>
            <p class="text-[10px] opacity-80">Management System</p>
        </div>
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
            <a href="{{ $item['link'] }}" class="flex items-center gap-2 p-2 rounded-lg {{ $isActive ? 'bg-red-500' : 'hover:bg-red-500 transition' }}">
                <span class="{{ $isActive ? 'bg-yellow-400 text-red-600' : 'bg-red-400' }} p-1 rounded text-sm">{!! $item['icon'] !!}</span>
                <span class="{{ $isActive ? 'text-yellow-300 font-semibold' : '' }}">{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>
</div>
