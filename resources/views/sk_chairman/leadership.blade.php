@extends('layouts.app')

@section('title', 'Leadership | SK 360')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('content')
<div id="addModal" class="hidden fixed inset-0 bg-black/50 z-[100] flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl w-full max-w-md overflow-hidden shadow-2xl">
        <div class="bg-red-600 p-6 text-white">
            <h2 class="text-xl font-black uppercase tracking-tighter">Add Council</h2>
            <p class="text-[10px] opacity-80 uppercase font-bold">This will add the member directly to SK Councilors.</p>
        </div>
        <form method="POST" action="{{ route('sk_chairman.leadership.store') }}" class="p-6 space-y-4">
            @csrf
            <div class="space-y-1">
                <label class="text-[10px] font-black text-gray-400 uppercase">Full Name</label>
                <input type="text" name="name" required class="w-full border-b-2 border-gray-100 focus:border-red-500 outline-none py-1 text-sm font-bold" value="{{ old('name') }}">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-[10px] font-black text-gray-400 uppercase">Email</label>
                    <input type="email" name="email" class="w-full border-b-2 border-gray-100 focus:border-red-500 outline-none py-1 text-sm font-bold" value="{{ old('email') }}">
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] font-black text-gray-400 uppercase">Phone</label>
                    <input type="text" name="phone" class="w-full border-b-2 border-gray-100 focus:border-red-500 outline-none py-1 text-sm font-bold" value="{{ old('phone') }}">
                </div>
            </div>
            <div class="rounded-2xl bg-red-50 px-4 py-3 text-xs font-bold text-red-700">
                Position: SK Councilor
            </div>
            <div class="space-y-1">
                <label class="text-[10px] font-black text-gray-400 uppercase">Term Period</label>
                <input type="text" name="term" value="{{ old('term', '2023-2026') }}" class="w-full border-b-2 border-gray-100 focus:border-red-500 outline-none py-1 text-sm font-bold">
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="toggleModal()" class="flex-1 py-3 text-xs font-black uppercase text-gray-400">Cancel</button>
                <button type="submit" class="flex-1 bg-red-600 py-3 rounded-xl text-xs font-black uppercase text-white shadow-lg">Save Councilor</button>
            </div>
        </form>
    </div>
</div>

<div class="flex h-screen bg-gray-100 overflow-hidden">
    <div class="w-64 bg-red-600 text-white flex flex-col p-3 overflow-y-auto">
        <div class="flex items-center gap-2 mb-3">
            <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" class="w-7 h-7" alt="logo">
            <h2 class="text-base font-bold">SK 360&deg;</h2>
        </div>

        <div class="bg-red-500 rounded-lg p-2 flex items-center gap-2 mb-3 shadow text-xs">
            <div class="bg-yellow-400 text-red-600 p-1 rounded-full text-sm">👤</div>
            <div>
                <p class="font-semibold text-xs">SK Chairman</p>
                <p class="text-xs opacity-80">Active Role</p>
            </div>
        </div>

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

    <div class="flex-1 flex flex-col overflow-hidden">
        <div class="bg-red-600 text-white px-6 py-3 flex justify-between items-center shadow">
            <input type="text" placeholder="Search..." class="px-4 py-2 rounded-full text-black w-1/3 focus:outline-none">

            <div class="flex items-center gap-3 relative">
                <div class="relative">
                    <button id="notifBtn" type="button" class="text-xl hover:bg-red-500 p-2 rounded-lg transition">
                        🔔
                    </button>

                    <div id="notifDropdown" class="hidden absolute right-0 mt-3 w-72 bg-white rounded-2xl shadow-xl border z-50 overflow-hidden">
                        <div class="px-4 py-3 font-semibold border-b text-gray-800">Notifications</div>
                        <div class="max-h-64 overflow-y-auto">
                            <div class="px-4 py-3 hover:bg-gray-100 text-sm text-gray-700">No notifications yet</div>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <button id="userMenuBtn" type="button" class="flex items-center gap-2 hover:bg-red-500 px-3 py-2 rounded-lg transition">
                        <span class="font-semibold">{{ $fullName }}</span>
                    </button>

                    <div id="userDropdown" class="hidden absolute right-0 mt-3 w-64 bg-white rounded-2xl shadow-xl border overflow-hidden z-50">
                        <div class="px-5 py-4 font-semibold text-gray-800 border-b">My Account</div>
                        <a href="#" class="flex items-center gap-3 px-5 py-3 hover:bg-gray-100 transition">
                            <span>👤</span>
                            <span class="text-gray-700">Profile Settings</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left flex items-center gap-3 px-5 py-3 text-red-500 hover:bg-gray-100 transition">
                                <span>↩️</span>
                                <span>Log Out</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <main class="p-8 overflow-y-auto h-full bg-gray-50">
            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="flex justify-between items-end mb-8">
                <div>
                    <h1 class="text-3xl font-black text-gray-800 uppercase tracking-tight">Council Leadership</h1>
                    <p class="text-gray-500 font-medium italic">Official Directory for Barangay {{ $barangayName }}</p>
                </div>
                <button type="button" onclick="toggleModal()" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-2xl font-black uppercase text-[10px] tracking-widest shadow-lg transition-all flex items-center gap-2">
                    <span class="text-base">+</span> Add Council
                </button>
            </div>

            <div class="bg-red-600 rounded-2xl p-6 text-white mb-8 shadow-md flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <div class="bg-white/20 p-3 rounded-xl text-2xl">&#128205;</div>
                    <div>
                        <h2 class="text-xl font-black uppercase tracking-tight">Barangay {{ $barangayName }}</h2>
                        <p class="text-xs opacity-80 font-medium">Current SK Administration</p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="bg-white/10 px-4 py-2 rounded-full text-[10px] font-bold border border-white/20 uppercase tracking-widest">
                        {{ $councilMembers->count() }} Total Members
                    </span>
                </div>
            </div>

            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-8 mb-8">
                <div class="flex items-center gap-2 mb-8 border-b border-gray-50 pb-4">
                    <span class="text-red-500 font-bold">&#128737;</span>
                    <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Executive Officers</h3>
                </div>
                <div class="grid grid-cols-1 gap-4">
                    @forelse ($executives as $member)
                        <div class="flex items-center gap-6 p-4 rounded-2xl border border-transparent hover:border-gray-100 hover:bg-gray-50 transition group">
                            <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-black text-xl border-4 border-white shadow-sm">
                                {{ strtoupper(substr($member['name'] ?? 'U', 0, 2)) }}
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-3">
                                    <h4 class="text-lg font-black text-gray-800 uppercase leading-none">{{ $member['name'] }}</h4>
                                    <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase bg-red-600 text-white">
                                        {{ $member['position'] }}
                                    </span>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 mt-3 text-[11px] text-gray-500 font-medium gap-2">
                                    <div class="flex items-center gap-2"><span>&#128231;</span> {{ $member['email'] ?: 'No email provided' }}</div>
                                    <div class="flex items-center gap-2"><span>&#128222;</span> {{ $member['phone'] ?: 'No phone provided' }}</div>
                                    <div class="flex items-center gap-2 uppercase tracking-tighter"><span>&#128197;</span> {{ $member['term'] ?: 'N/A' }}</div>
                                </div>
                            </div>
                            @if (!empty($member['council_id']))
                                <form method="POST" action="{{ route('sk_chairman.leadership.destroy', $member['council_id']) }}">
                                    @csrf
                                    <button type="submit" onclick="return confirm('Remove this member?')" class="text-gray-300 hover:text-red-600 transition">
                                        &#128465;
                                    </button>
                                </form>
                            @endif
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
                        <div class="bg-gray-50/50 p-5 rounded-2xl border border-gray-100 flex items-center gap-4 hover:shadow-md hover:bg-white transition group relative">
                            <div class="w-12 h-12 rounded-full bg-yellow-400 flex items-center justify-center text-white font-black shadow-sm border-2 border-white">
                                {{ strtoupper(substr($member['name'] ?? 'U', 0, 2)) }}
                            </div>
                            <div class="flex-1">
                                <h4 class="text-xs font-black text-gray-800 uppercase leading-none">{{ $member['name'] }}</h4>
                                <p class="text-[9px] text-yellow-600 font-black uppercase mt-1">{{ $member['position'] }}</p>
                                <p class="text-[10px] text-gray-400 mt-2 font-medium">&#128222; {{ $member['phone'] ?: 'No phone provided' }}</p>
                            </div>
                            @if (!empty($member['council_id']))
                                <form method="POST" action="{{ route('sk_chairman.leadership.destroy', $member['council_id']) }}" class="absolute top-4 right-4">
                                    @csrf
                                    <button type="submit" onclick="return confirm('Remove this member?')" class="text-[10px] text-gray-300 hover:text-red-600 opacity-0 group-hover:opacity-100 transition">
                                        &#128465;
                                    </button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-gray-400 italic">No councilors found.</p>
                    @endforelse
                </div>
            </div>
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const notifBtn = document.getElementById('notifBtn');
    const notifDropdown = document.getElementById('notifDropdown');
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userDropdown = document.getElementById('userDropdown');
    function toggleModal() {
        document.getElementById('addModal').classList.toggle('hidden');
    }


    if (notifBtn && notifDropdown && userDropdown) {
        notifBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            notifDropdown.classList.toggle('hidden');
            userDropdown.classList.add('hidden');
        });
    }

    if (userMenuBtn && userDropdown && notifDropdown) {
        userMenuBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            userDropdown.classList.toggle('hidden');
            notifDropdown.classList.add('hidden');
        });
    }

    document.addEventListener('click', function (e) {
        if (notifBtn && notifDropdown && !notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
            notifDropdown.classList.add('hidden');
        }

        if (userMenuBtn && userDropdown && !userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)) {
            userDropdown.classList.add('hidden');
        }
    });


    @if ($errors->any())
        document.getElementById('addModal').classList.remove('hidden');
    @endif

    @if (session('status') === 'added')
        Swal.fire({ title: 'Success!', text: 'New member has been added.', icon: 'success', confirmButtonColor: '#DC2626' });
    @elseif (session('status') === 'deleted')
        Swal.fire({ title: 'Deleted!', text: 'The member has been removed.', icon: 'success', confirmButtonColor: '#DC2626' });
    @endif
</script>
@endpush
