{{-- File guide: Blade view template for resources/views/youth/leadership.blade.php. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leadership | SK 360&deg;</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 overflow-hidden">

<div class="flex h-screen">
    <div class="w-64 bg-red-600 text-white flex flex-col p-3">
        <div class="flex items-center gap-2 mb-3">
            <img src="{{ asset('logo.png') }}" class="w-7 h-7" alt="logo">
            <h2 class="text-base font-bold text-white">SK 360&deg;</h2>
        </div>

        <div class="bg-red-500 rounded-lg p-2 flex items-center gap-2 mb-3 shadow text-xs">
            <div class="bg-yellow-400 text-red-600 p-1 rounded-full font-bold">&#128100;</div>
            <div>
                <p class="font-semibold">{{ $userName }}</p>
                <p class="opacity-80 text-[10px]">{{ $roleLabel ?? 'Youth Member' }}</p>
            </div>
        </div>

        <nav class="space-y-1 text-xs">
            @foreach ($menuItems as $item)
                @php $isActive = $currentUrl === $item['link']; @endphp
                <a href="{{ $item['link'] }}" class="flex items-center gap-2 p-2 rounded-lg transition {{ $isActive ? 'bg-red-500' : 'hover:bg-red-500' }}">
                    <span class="{{ $isActive ? 'bg-yellow-400 text-red-600' : 'bg-red-400' }} p-1 rounded">{{ $item['icon'] }}</span>
                    <span class="{{ $isActive ? 'text-yellow-300 font-semibold' : '' }}">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <div class="flex-1 flex flex-col">
        <div class="bg-red-600 text-white px-6 py-3 flex justify-between items-center shadow relative">
            <div class="w-1/4"></div>
            <div class="w-1/3">
                <input type="text" placeholder="Search..." class="w-full px-4 py-2 rounded-full text-black focus:outline-none text-sm">
            </div>
            <div class="w-1/4 flex justify-end items-center gap-5 text-sm">
                <button class="hover:opacity-80">&#128276;</button>
                <div class="relative">
                    <button id="profileDropdownBtn" class="flex items-center gap-2 font-semibold focus:outline-none hover:opacity-80 transition">
                        <span>{{ $userName }}</span>
                        <span class="text-[10px]">&#9660;</span>
                    </button>
                    <div id="profileMenu" class="absolute right-0 mt-3 w-48 bg-white rounded-xl shadow-2xl py-2 z-[9999] hidden border border-gray-100">
                        <div class="px-4 py-3 border-b border-gray-50">
                            <p class="text-[10px] text-gray-400 uppercase font-black tracking-widest">Account Settings</p>
                        </div>
                        @if (!empty($profileRoute))
                            <a href="{{ $profileRoute }}" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 text-xs flex items-center gap-2 transition">
                                <span>&#128100;</span> View Profile
                            </a>
                        @endif
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-3 text-red-600 hover:bg-red-50 text-xs font-bold flex items-center gap-2 transition">
                                <span>&#128682;</span> Log Out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <main class="p-8 overflow-y-auto h-full bg-gray-50">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800 uppercase">SK Council Leadership Profile</h1>
                <p class="text-gray-500">Barangay {{ $barangayName }} Council Members</p>
            </div>

            <div class="bg-red-600 rounded-2xl p-6 text-white mb-8 shadow-md flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <div class="bg-white/20 p-3 rounded-xl text-2xl">&#128205;</div>
                    <div>
                        <h2 class="text-xl font-black uppercase tracking-tight">Barangay {{ $barangayName }}</h2>
                        <p class="text-xs opacity-80 font-medium">Official SK Council Directory</p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="bg-white/10 px-4 py-2 rounded-full text-[10px] font-bold border border-white/20 uppercase tracking-widest">
                        {{ $councilMembers->count() }} Members
                    </span>
                </div>
            </div>

            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-8 mb-8">
                <div class="flex items-center gap-2 mb-8 border-b border-gray-50 pb-4">
                    <span class="text-red-500 font-bold">&#128737;</span>
                    <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Executive Officers</h3>
                </div>
                <div class="grid grid-cols-1 gap-6">
                    @forelse ($executives as $member)
                        <div class="flex items-center gap-6 p-4 rounded-2xl hover:bg-gray-50 transition border border-transparent hover:border-gray-100">
                            <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-black text-xl border-4 border-white shadow-sm">
                                {{ strtoupper(substr($member['name'] ?? 'NA', 0, 2)) }}
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-3">
                                    <h4 class="text-lg font-black text-gray-800 uppercase leading-none">{{ $member['name'] }}</h4>
                                    <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase bg-red-600 text-white">
                                        {{ $member['position'] }}
                                    </span>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 mt-3 text-[11px] text-gray-500 font-medium gap-2">
                                    <div>&#128231; {{ $member['email'] ?: 'No email provided' }}</div>
                                    <div>&#128222; {{ $member['phone'] ?: 'No phone provided' }}</div>
                                    <div class="uppercase">&#128197; Term: {{ $member['term'] ?: 'N/A' }}</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-400 italic">No executive officers found.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-8">
                <div class="flex items-center gap-2 mb-8 border-b border-gray-50 pb-4">
                    <span class="text-yellow-500 font-bold">&#127775;</span>
                    <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">SK Councilors</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @forelse ($kagawads as $member)
                        <div class="bg-gray-50/50 p-5 rounded-2xl border border-gray-100 flex items-center gap-4 hover:shadow-sm transition">
                            <div class="w-12 h-12 rounded-full bg-yellow-400 flex items-center justify-center text-white font-black shadow-sm border-2 border-white">
                                {{ strtoupper(substr($member['name'] ?? 'NA', 0, 2)) }}
                            </div>
                            <div>
                                <h4 class="text-xs font-black text-gray-800 uppercase leading-none">{{ $member['name'] }}</h4>
                                <p class="text-[9px] text-yellow-600 font-black uppercase mt-1 tracking-tight">{{ $member['position'] }}</p>
                                <p class="text-[10px] text-gray-400 mt-2 font-medium">&#128222; {{ $member['phone'] ?: 'No phone provided' }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-400 italic">No councilors found.</p>
                    @endforelse
                </div>
            </div>
        </main>
    </div>
</div>

<script>
const dropdownBtn = document.getElementById('profileDropdownBtn');
const profileMenu = document.getElementById('profileMenu');

dropdownBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    profileMenu.classList.toggle('hidden');
});

window.addEventListener('click', (e) => {
    if (!profileMenu.contains(e.target) && !dropdownBtn.contains(e.target)) {
        profileMenu.classList.add('hidden');
    }
});
</script>

</body>
</html>

