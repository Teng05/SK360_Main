{{-- File guide: Blade view template for resources/views/sk_pres/user-management.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK 360 User Management')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
@endsection

@section('content')
<div class="flex h-screen bg-gray-100">
    <div class="w-64 bg-red-600 text-white flex flex-col p-3 overflow-y-auto">
        <div class="flex items-center gap-2 mb-3">
            <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" class="w-7 h-7" alt="logo">
            <h2 class="text-base font-bold">SK 360&deg;</h2>
        </div>

        <div class="bg-red-500 rounded-lg p-2 flex items-center gap-2 mb-3 shadow text-xs">
            <div class="bg-yellow-400 text-red-600 p-1 rounded-full text-sm">&#128100;</div>
            <div>
                <p class="font-semibold text-xs">{{ $fullName }}</p>
                <p class="text-xs opacity-80">SK President</p>
            </div>
        </div>

        <nav class="space-y-1 text-xs">
            @foreach ($menuItems as $item)
                @php $isActive = $item['link'] === $currentUrl; @endphp
                <a href="{{ $item['link'] }}" class="flex items-center gap-2 p-2 rounded-lg {{ $isActive ? 'bg-red-500' : 'hover:bg-red-500 transition' }}">
                    <span class="{{ $isActive ? 'bg-yellow-400 text-red-600' : 'bg-red-400' }} p-1 rounded text-sm">{!! $item['icon'] !!}</span>
                    <span class="{{ $isActive ? 'text-yellow-300 font-semibold' : '' }} text-xs">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <div class="flex-1 flex flex-col">
        <div class="bg-red-600 text-white px-6 py-3 flex justify-between items-center shadow relative">
            <div class="w-1/4"></div>
            <div class="w-1/3">
                <input type="text" placeholder="Search" class="w-full rounded-full px-4 py-2 text-black focus:outline-none text-sm">
            </div>
            <div class="w-1/4 flex justify-end items-center gap-5 text-sm">
                <button class="hover:opacity-80">&#128276;</button>
                <div class="relative">
                    <button id="profileDropdownBtn" type="button" class="flex items-center gap-2 font-semibold focus:outline-none hover:opacity-80 transition">
                        <span>{{ $fullName }}</span>
                        <span class="text-[10px]">&#9660;</span>
                    </button>
                    <div id="profileMenu" class="absolute right-0 mt-3 w-48 bg-white rounded-xl shadow-2xl py-2 z-[9999] hidden border border-gray-100">
                        <div class="px-4 py-3 border-b border-gray-50">
                            <p class="text-[10px] text-gray-400 uppercase font-black tracking-widest">Account Settings</p>
                        </div>
                        <a href="{{ route('sk_pres.profile') }}" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 text-xs flex items-center gap-2 transition">
                            <span>&#128100;</span> View Profile
                        </a>
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

        <main class="flex-1 overflow-y-auto bg-gray-50 p-8">
            <div class="mb-6 flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-4xl font-bold text-gray-900">User Management</h1>
                    <p class="text-gray-600 text-lg">Manage SK officials and Lipa Youth accounts across the platform</p>
                </div>
                <button id="openOfficialModal" type="button" class="rounded-lg bg-red-500 px-4 py-2 text-xs font-bold text-white hover:bg-red-600 transition">
                    &#10133; Add SK Official
                </button>
            </div>

            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($stats as $stat)
                    <div class="rounded-2xl border {{ $stat['border'] }} bg-white px-5 py-4 shadow-sm">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs text-gray-400">{{ $stat['label'] }}</p>
                                <h2 class="mt-1 text-4xl font-bold text-gray-900">{{ $stat['value'] }}</h2>
                            </div>
                            <div class="{{ $stat['iconBg'] }} rounded-xl p-3">
                                <span class="{{ $stat['iconColor'] }} text-xl">{!! $stat['icon'] !!}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <form method="GET" action="{{ route('sk_pres.user-management') }}" class="mb-6 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="relative w-full lg:max-w-xl">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-300">&#128269;</span>
                        <input
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Search by name, email, or barangay..."
                            class="w-full rounded-xl bg-gray-50 py-3 pl-10 pr-4 text-sm text-gray-700 outline-none ring-1 ring-transparent focus:ring-red-200"
                        >
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="submit" class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-xs font-medium text-gray-500">Search</button>
                        <button type="button" class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-xs font-medium text-gray-500">All Status</button>
                        <button type="button" class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-xs font-medium text-gray-500">&#128229; Export</button>
                        <a href="{{ route('sk_pres.user-management') }}" class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-xs font-medium text-gray-500">&#10227;</a>
                    </div>
                </div>
            </form>

            <div class="space-y-4">
                @foreach ($userGroups as $index => $group)
                    <details class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm" {{ $index < 2 ? 'open' : '' }}>
                        <summary class="flex cursor-pointer list-none items-center justify-between px-5 py-4">
                            <div class="flex items-center gap-3">
                                <span class="{{ $group['iconColor'] }} text-xl">{!! $group['icon'] !!}</span>
                                <div>
                                    <h3 class="text-sm font-black text-gray-800">
                                        {{ $group['label'] }}
                                        <span class="ml-2 inline-flex rounded-full {{ $group['badgeColor'] }} px-2 py-0.5 text-[10px] font-bold text-white">{{ $group['count'] }}</span>
                                    </h3>
                                    <p class="text-xs text-gray-500">{{ $group['description'] }}</p>
                                </div>
                            </div>
                            <span class="text-gray-400">&#9662;</span>
                        </summary>

                        <div class="space-y-3 border-t border-gray-100 px-5 py-4">
                            @forelse ($group['users'] as $groupUser)
                                @php
                                    $displayName = trim(($groupUser->first_name ?? '') . ' ' . ($groupUser->last_name ?? ''));
                                    $initials = strtoupper(substr($groupUser->first_name ?? 'U', 0, 1) . substr($groupUser->last_name ?? '', 0, 1));
                                    $roleLabel = ucwords(str_replace('_', ' ', $groupUser->role));
                                    $joinedDate = $groupUser->created_at ? \Carbon\Carbon::parse($groupUser->created_at)->format('n/j/Y') : 'N/A';
                                    $roleBadge = match ($groupUser->role) {
                                        'sk_president' => 'bg-red-100 text-red-600',
                                        'sk_chairman' => 'bg-green-100 text-green-600',
                                        'sk_secretary' => 'bg-blue-100 text-blue-600',
                                        default => 'bg-yellow-100 text-yellow-700',
                                    };
                                    $avatarBg = match ($groupUser->role) {
                                        'sk_president' => 'bg-red-500',
                                        'sk_chairman' => 'bg-green-500',
                                        'sk_secretary' => 'bg-blue-500',
                                        default => 'bg-yellow-500',
                                    };
                                @endphp
                                <div class="rounded-2xl border border-gray-100 bg-white px-4 py-4">
                                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                        <div class="flex items-start gap-4">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-full {{ $avatarBg }} text-xs font-black text-white">
                                                {{ $initials }}
                                            </div>
                                            <div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <h4 class="text-sm font-bold text-gray-800">{{ $displayName }}</h4>
                                                    <span class="rounded-full px-2 py-0.5 text-[9px] font-bold uppercase {{ $roleBadge }}">{{ $roleLabel }}</span>
                                                    <span class="rounded-full {{ $groupUser->status === 'active' ? 'bg-green-100 text-green-600' : 'bg-gray-200 text-gray-600' }} px-2 py-0.5 text-[9px] font-bold uppercase">{{ $groupUser->status }}</span>
                                                </div>
                                                <p class="mt-1 text-xs text-gray-500">&#128231; {{ $groupUser->email }}</p>
                                                <p class="mt-1 text-xs text-gray-400">&#128205; Barangay {{ $groupUser->barangay_name ?: 'Unassigned' }}</p>
                                                <p class="mt-1 text-[10px] text-gray-400">Joined: {{ $joinedDate }}{{ $groupUser->phone_number ? ' • ' . $groupUser->phone_number : '' }}</p>
                                            </div>
                                        </div>

                                        <div class="relative">
                                            <button type="button" class="action-menu-btn rounded-lg px-2 py-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">&#8942;</button>
                                            <div class="action-menu hidden absolute right-0 top-9 z-20 w-36 rounded-xl border border-gray-100 bg-white py-2 shadow-xl">
                                                <button type="button" class="block w-full px-4 py-2 text-left text-xs text-gray-600 hover:bg-gray-50">Edit User</button>
                                                <button type="button" class="block w-full px-4 py-2 text-left text-xs text-gray-600 hover:bg-gray-50">Deactivate</button>
                                                <button type="button" class="block w-full px-4 py-2 text-left text-xs text-red-600 hover:bg-red-50">Delete User</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-400 italic">No users found in this group.</p>
                            @endforelse
                        </div>
                    </details>
                @endforeach
            </div>
        </main>
    </div>
</div>

<div id="officialModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
    <div class="w-full max-w-xl rounded-3xl bg-white p-6 shadow-2xl">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-black text-gray-900">Add SK Official</h2>
                <p class="text-sm text-gray-500">Create a new SK official account.</p>
            </div>
            <button id="closeOfficialModal" type="button" class="text-2xl text-gray-400 hover:text-red-500">&times;</button>
        </div>

        <form action="{{ route('sk_pres.user-management.store-official') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="mb-2 block text-xs font-black uppercase text-gray-500">Role</label>
                <select name="role" class="w-full rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-300" required>
                    <option value="">Select role</option>
                    <option value="sk_chairman" {{ old('role') === 'sk_chairman' ? 'selected' : '' }}>SK Chairman</option>
                    <option value="sk_secretary" {{ old('role') === 'sk_secretary' ? 'selected' : '' }}>SK Secretary</option>
                </select>
            </div>

            <div>
                <label class="mb-2 block text-xs font-black uppercase text-gray-500">Full Name</label>
                <input type="text" name="full_name" value="{{ old('full_name') }}" class="w-full rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-300" required>
            </div>

            <div>
                <label class="mb-2 block text-xs font-black uppercase text-gray-500">Email Address</label>
                <input type="email" name="email" value="{{ old('email') }}" class="w-full rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-300" required>
            </div>

            <div>
                <label class="mb-2 block text-xs font-black uppercase text-gray-500">Barangay</label>
                <select name="barangay_id" class="w-full rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-300" required>
                    <option value="">Select barangay</option>
                    @foreach ($barangays as $barangay)
                        <option value="{{ $barangay->barangay_id }}" {{ (string) old('barangay_id') === (string) $barangay->barangay_id ? 'selected' : '' }}>
                            {{ $barangay->barangay_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-xs font-black uppercase text-gray-500">Phone Number</label>
                <input type="text" name="phone_number" value="{{ old('phone_number') }}" class="w-full rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-300" placeholder="Optional">
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full rounded-xl bg-red-600 px-4 py-3 text-sm font-bold text-white hover:bg-red-700 transition">
                    Create Official Account
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const userMgmtDropdownBtn = document.getElementById('profileDropdownBtn');
    const userMgmtProfileMenu = document.getElementById('profileMenu');
    const openOfficialModal = document.getElementById('openOfficialModal');
    const officialModal = document.getElementById('officialModal');
    const closeOfficialModal = document.getElementById('closeOfficialModal');
    const actionMenuButtons = document.querySelectorAll('.action-menu-btn');

    if (userMgmtDropdownBtn && userMgmtProfileMenu) {
        userMgmtDropdownBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            userMgmtProfileMenu.classList.toggle('hidden');
        });
    }

    function showOfficialModal() {
        officialModal.classList.remove('hidden');
        officialModal.classList.add('flex');
    }

    function hideOfficialModal() {
        officialModal.classList.add('hidden');
        officialModal.classList.remove('flex');
    }

    if (openOfficialModal) {
        openOfficialModal.addEventListener('click', showOfficialModal);
    }

    if (closeOfficialModal) {
        closeOfficialModal.addEventListener('click', hideOfficialModal);
    }

    if (officialModal) {
        officialModal.addEventListener('click', (e) => {
            if (e.target === officialModal) {
                hideOfficialModal();
            }
        });
    }

    actionMenuButtons.forEach((button) => {
        button.addEventListener('click', (e) => {
            e.stopPropagation();
            document.querySelectorAll('.action-menu').forEach((menu) => {
                if (menu !== button.nextElementSibling) {
                    menu.classList.add('hidden');
                }
            });
            button.nextElementSibling.classList.toggle('hidden');
        });
    });

    window.addEventListener('click', (e) => {
        if (userMgmtProfileMenu && !userMgmtProfileMenu.contains(e.target) && !userMgmtDropdownBtn.contains(e.target)) {
            userMgmtProfileMenu.classList.add('hidden');
        }

        document.querySelectorAll('.action-menu').forEach((menu) => {
            if (!menu.contains(e.target)) {
                menu.classList.add('hidden');
            }
        });
    });

    @if ($errors->any())
        showOfficialModal();
    @endif
</script>
@endpush
