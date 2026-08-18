{{-- File guide: Blade view template for resources/views/sk_pres/user-management.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK 360 User Management')

@section('page_css')
<script src="https://cdn.tailwindcss.com"></script>
@endsection

@section('content')
<div class="flex h-screen bg-gray-100">
    <!-- Sidebar -->
    <div class="w-64 bg-red-600 text-white flex flex-col p-3 overflow-y-auto shrink-0">
        <div class="flex items-center gap-3 mb-4">
            <img src="{{ asset('images/logo.png') }}" class="w-8 h-8 rounded-full object-cover" alt="logo">
            <div class="leading-tight">
                <h2 class="text-lg font-extrabold tracking-wide">SK 360°</h2>
                <p class="text-[10px] opacity-80">Management System</p>
            </div>
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
                @php $isActive=$item['link'] === $currentUrl; @endphp
                <a href="{{ $item['link'] }}" class="flex items-center gap-2 p-2 rounded-lg {{ $isActive ? 'bg-red-500' : 'hover:bg-red-500 transition' }}">
                    <span class="{{ $isActive ? 'bg-yellow-400 text-red-600' : 'bg-red-400' }} p-1 rounded text-sm">{!! $item['icon'] !!}</span>
                    <span class="{{ $isActive ? 'text-yellow-300 font-semibold' : '' }} text-xs">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <!-- Main Section -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Header Nav -->
        <div class="bg-red-600 text-white px-6 py-3 flex justify-between items-center shadow relative z-10">
            <div class="w-1/4"></div>

            <div class="w-1/3">
                <input
                    type="text"
                    class="live-user-search w-full rounded-full px-4 py-2 text-black focus:outline-none text-sm"
                    placeholder="Search officials..."
                    autocomplete="off"
                >
            </div>

            <div class="w-1/4 flex justify-end items-center gap-5 text-sm">
                <button type="button" class="hover:opacity-80">&#128276;</button>

                <div class="relative">
                    <button id="profileDropdownBtn" type="button" class="flex items-center gap-2 font-semibold focus:outline-none hover:opacity-80 transition">
                        <span>{{ $fullName }}</span>
                        <span class="text-[10px]">&#9660;</span>
                    </button>

                    <div id="profileMenu" class="absolute right-0 mt-3 w-48 bg-white rounded-xl shadow-2xl py-2 z-[9999] hidden border border-gray-100">
                        <div class="px-4 py-3 border-b border-gray-50">
                            <p class="text-[10px] text-gray-400 uppercase font-black tracking-widest">Account Settings</p>
                        </div>

                        <a href="{{ route('sk_pres.profile') }}" class="px-4 py-3 text-gray-700 hover:bg-gray-50 text-xs flex items-center gap-2 transition">
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

        <!-- Scrollable Content Area -->
        <main class="flex-1 overflow-y-auto bg-gray-50 p-8">
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-4xl font-bold text-gray-900">User Management</h1>
                    <p class="text-gray-600 text-lg">Manage SK officials across the platform</p>
                </div>

                <div class="flex items-center gap-2">
                    <button id="openBulkModal" type="button" class="rounded-lg bg-green-600 px-4 py-2 text-xs font-bold text-white hover:bg-green-700 transition flex items-center gap-1">
                        <span>&#128101;</span> Bulk Add
                    </button>

                    <button id="openImportModal" type="button" class="rounded-lg bg-gray-800 px-4 py-2 text-xs font-bold text-white hover:bg-gray-900 transition flex items-center gap-1">
                        <span>&#128229;</span> Import CSV
                    </button>

                    <button id="openOfficialModal" type="button" class="rounded-lg bg-red-500 px-4 py-2 text-xs font-bold text-white hover:bg-red-600 transition flex items-center gap-1">
                        <span>&#10133;</span> Single Add
                    </button>
                </div>
            </div>

            <!-- Alerts -->
            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 flex items-center justify-between">
                    <span>{{ session('success') }}</span>
                    <button type="button" onclick="this.parentElement.remove()" class="text-green-500 hover:text-green-700">&times;</button>
                </div>
            @endif

            @if (session('warning'))
                <div class="mb-6 rounded-2xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-700">
                    {{ session('warning') }}
                </div>
            @endif

            @if ($errors->getBag('default')->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->getBag('default')->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Dashboard Stats -->
            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                @foreach ($stats as $stat)
                    <div class="rounded-2xl border {{ $stat['border'] ?? 'border-gray-100' }} bg-white px-5 py-4 shadow-sm">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs text-gray-400">{{ $stat['label'] }}</p>
                                <h2 class="mt-1 text-4xl font-bold text-gray-900">{{ $stat['value'] }}</h2>
                            </div>

                            <div class="{{ $stat['iconBg'] ?? 'bg-gray-100' }} rounded-xl p-3">
                                <span class="{{ $stat['iconColor'] ?? 'text-gray-600' }} text-xl">{!! $stat['icon'] !!}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Filters Bar -->
            <form method="GET" action="{{ route('sk_pres.user-management') }}" class="mb-6 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="relative w-full lg:max-w-xl">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-300">&#128269;</span>

                        <input
                            type="text"
                            class="live-user-search w-full rounded-xl bg-gray-50 py-3 pl-10 pr-4 text-sm text-gray-700 outline-none ring-1 ring-transparent focus:ring-red-200 transition"
                            placeholder="Search by name, email, barangay, phone or role..."
                            autocomplete="off"
                        >
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <select name="status" onchange="this.form.submit()" class="rounded-xl border border-gray-200 bg-white px-3 py-3 text-xs font-medium text-gray-600 focus:outline-none">
                            <option value="">All Statuses</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>

                        <a href="{{ route('sk_pres.user-management') }}" title="Reset filters" class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-xs font-medium text-gray-500 hover:bg-gray-50 transition">
                            &#10227; Reset
                        </a>
                    </div>
                </div>
            </form>

            <!-- User Groups Accordion -->
            <div class="space-y-4">
                @foreach ($userGroups as $index=>$group)
                    <details
                        class="user-group rounded-2xl border border-gray-100 bg-white shadow-sm"
                        data-default-open="{{ $index < 2 ? '1' : '0' }}"
                        {{ $index < 2 ? 'open' : '' }}
                    >
                        <summary class="flex cursor-pointer list-none items-center justify-between px-5 py-4 hover:bg-gray-50/50 transition">
                            <div class="flex items-center gap-3">
                                <span class="{{ $group['iconColor'] ?? 'text-red-500' }} text-xl">{!! $group['icon'] !!}</span>

                                <div>
                                    <h3 class="text-sm font-black text-gray-800 flex items-center gap-2">
                                        {{ $group['label'] }}

                                        <span
                                            class="group-count inline-flex rounded-full {{ $group['badgeColor'] ?? 'bg-red-500' }} px-2 py-0.5 text-[10px] font-bold text-white"
                                            data-original-count="{{ $group['count'] }}"
                                        >
                                            {{ $group['count'] }}
                                        </span>
                                    </h3>

                                    <p class="text-xs text-gray-500">{{ $group['description'] }}</p>
                                </div>
                            </div>

                            <span class="text-gray-400 transition-transform duration-200">&#9662;</span>
                        </summary>

                        <div class="space-y-3 border-t border-gray-100 px-5 py-4 bg-gray-50/30">
                            @forelse ($group['users'] as $groupUser)
                                @php
                                    $userId=$groupUser->id ?? $groupUser->user_id ?? 0;
                                    $displayName=trim(($groupUser->first_name ?? '').' '.($groupUser->last_name ?? ''));
                                    $initials=strtoupper(substr($groupUser->first_name ?? 'U',0,1).substr($groupUser->last_name ?? '',0,1));
                                    $roleLabel=ucwords(str_replace('_',' ',$groupUser->role ?? ''));
                                    $joinedDate=$groupUser->created_at ? \Carbon\Carbon::parse($groupUser->created_at)->format('M j, Y') : 'N/A';
                                    $isPending=($groupUser->role ?? '') === 'sk_chairman' && (int)($groupUser->is_verified ?? 0) === 0;
                                    $statusLabel=$isPending ? 'pending' : ($groupUser->status ?? 'active');

                                    $roleBadge=match($groupUser->role ?? ''){
                                        'sk_president'=>'bg-red-100 text-red-600',
                                        'sk_chairman'=>'bg-green-100 text-green-600',
                                        'sk_secretary'=>'bg-blue-100 text-blue-600',
                                        default=>'bg-gray-100 text-gray-600',
                                    };

                                    $avatarBg=match($groupUser->role ?? ''){
                                        'sk_president'=>'bg-red-500',
                                        'sk_chairman'=>'bg-green-500',
                                        'sk_secretary'=>'bg-blue-500',
                                        default=>'bg-gray-500',
                                    };

                                    $searchText=strtolower(
                                        $displayName.' '.
                                        ($groupUser->email ?? '').' '.
                                        ($groupUser->barangay_name ?? '').' '.
                                        ($groupUser->phone_number ?? '').' '.
                                        $roleLabel.' '.
                                        $statusLabel
                                    );

                                    $userDataPayload=json_encode([
                                        'id'=>$userId,
                                        'first_name'=>$groupUser->first_name ?? '',
                                        'last_name'=>$groupUser->last_name ?? '',
                                        'email'=>$groupUser->email ?? '',
                                        'role'=>$groupUser->role ?? '',
                                        'barangay_id'=>$groupUser->barangay_id ?? '',
                                        'phone_number'=>$groupUser->phone_number ?? '',
                                        'status'=>$groupUser->status ?? 'active',
                                        'is_verified'=>(int)($groupUser->is_verified ?? 0),
                                    ]);
                                @endphp

                                <div
                                    class="user-card rounded-2xl border border-gray-100 bg-white px-4 py-4 hover:border-gray-200 transition relative"
                                    data-search="{{ $searchText }}"
                                >
                                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                        <div class="flex items-start gap-4">
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $avatarBg }} text-xs font-black text-white shadow-sm">
                                                {{ $initials ?: 'U' }}
                                            </div>

                                            <div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <h4 class="text-sm font-bold text-gray-800">
                                                        {{ $displayName ?: 'Unnamed User' }}
                                                    </h4>

                                                    <span class="rounded-full px-2 py-0.5 text-[9px] font-bold uppercase {{ $roleBadge }}">
                                                        {{ $roleLabel }}
                                                    </span>

                                                    @if ($isPending)
                                                        <span class="rounded-full bg-yellow-100 text-yellow-700 px-2 py-0.5 text-[9px] font-bold uppercase">
                                                            Pending
                                                        </span>
                                                    @else
                                                        <span class="rounded-full {{ ($groupUser->status ?? 'active') === 'active' ? 'bg-green-100 text-green-600' : 'bg-gray-200 text-gray-600' }} px-2 py-0.5 text-[9px] font-bold uppercase">
                                                            {{ $groupUser->status ?? 'active' }}
                                                        </span>
                                                    @endif
                                                </div>

                                                <p class="mt-1 text-xs text-gray-500 flex items-center gap-1">
                                                    <span>&#128231;</span> {{ $groupUser->email }}
                                                </p>

                                                <p class="mt-1 text-xs text-gray-400 flex items-center gap-1">
                                                    <span>&#128205;</span> Barangay {{ $groupUser->barangay_name ?? 'Unassigned' }}
                                                </p>

                                                <p class="mt-1 text-[10px] text-gray-400">
                                                    Joined: {{ $joinedDate }}
                                                    {{ !empty($groupUser->phone_number) ? ' • '.$groupUser->phone_number : '' }}
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Row Actions Dropdown -->
                                        <div class="relative self-end md:self-center">
                                            <button type="button" class="action-menu-btn rounded-lg px-2.5 py-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition text-lg leading-none">
                                                &#8942;
                                            </button>

                                            <div class="action-menu hidden absolute right-0 top-8 z-40 w-40 rounded-xl border border-gray-100 bg-white py-1.5 shadow-xl">
                                                <button
                                                    type="button"
                                                    data-user="{{ $userDataPayload }}"
                                                    class="edit-user-btn block w-full px-4 py-2 text-left text-xs text-gray-700 hover:bg-gray-50 transition"
                                                >
                                                    Edit Details
                                                </button>

                                                @if ($isPending)
                                                    <form
                                                        action="{{ route('sk_pres.user-management.resend-setup-link',$userId) }}"
                                                        method="POST"
                                                        onsubmit="return confirm('Send a new password setup link to {{ $groupUser->email }}?');"
                                                    >
                                                        @csrf

                                                        <button type="submit" class="block w-full px-4 py-2 text-left text-xs font-semibold text-blue-600 hover:bg-blue-50 transition">
                                                            Resend Setup Link
                                                        </button>
                                                    </form>
                                                @else
                                                    @if (($groupUser->role ?? '') !== 'sk_president')
                                                        <form action="{{ route('sk_pres.user-management.toggle-status',$userId) }}" method="POST">
                                                            @csrf
                                                            @method('PATCH')

                                                            <button type="submit" class="block w-full px-4 py-2 text-left text-xs text-gray-700 hover:bg-gray-50 transition">
                                                                {{ ($groupUser->status ?? 'active') === 'active' ? 'Deactivate' : 'Activate' }}
                                                            </button>
                                                        </form>
                                                    @endif
                                                @endif

                                                @if (($groupUser->role ?? '') !== 'sk_president')
                                                    <div class="my-1 border-t border-gray-100"></div>

                                                    <form
                                                        action="{{ route('sk_pres.user-management.destroy',$userId) }}"
                                                        method="POST"
                                                        onsubmit="return confirm('Are you sure you want to delete this user?');"
                                                    >
                                                        @csrf
                                                        @method('DELETE')

                                                        <button type="submit" class="block w-full px-4 py-2 text-left text-xs font-semibold text-red-600 hover:bg-red-50 transition">
                                                            Delete User
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="no-users-message text-xs text-gray-400 italic py-2">
                                    No users found in this group.
                                </p>
                            @endforelse

                            <p class="live-search-empty hidden text-xs text-gray-400 italic py-2">
                                No matching officials found.
                            </p>
                        </div>
                    </details>
                @endforeach
            </div>

            <div id="noSearchResults" class="hidden mt-5 rounded-2xl border border-gray-200 bg-white p-6 text-center text-sm text-gray-500">
                No officials matched your search.
            </div>
        </main>
    </div>
</div>

<!-- Modal: Add SK Chairman -->
<div id="officialModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
    <div class="w-full max-w-xl rounded-3xl bg-white p-6 shadow-2xl transition-all">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-black text-gray-900">Add SK Chairman</h2>
                <p class="text-xs text-gray-500">Create a new SK Chairman account for a barangay.</p>
            </div>

            <button id="closeOfficialModal" type="button" class="text-2xl text-gray-400 hover:text-red-500 transition">
                &times;
            </button>
        </div>

        <form action="{{ route('sk_pres.user-management.store-official') }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="mb-1.5 block text-xs font-black uppercase text-gray-500">First Name</label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" class="w-full rounded-xl border border-red-100 bg-red-50/50 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-300" required>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-black uppercase text-gray-500">Last Name</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" class="w-full rounded-xl border border-red-100 bg-red-50/50 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-300" required>
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-black uppercase text-gray-500">Email Address</label>
                <input type="email" name="email" value="{{ old('email') }}" class="w-full rounded-xl border border-red-100 bg-red-50/50 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-300" required>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-black uppercase text-gray-500">Barangay</label>

                <select name="barangay_id" class="w-full rounded-xl border border-red-100 bg-red-50/50 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-300" required>
                    <option value="">Select barangay</option>

                    @foreach ($barangays as $barangay)
                        <option value="{{ $barangay->barangay_id }}" {{ (string)old('barangay_id') === (string)$barangay->barangay_id ? 'selected' : '' }}>
                            {{ $barangay->barangay_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-black uppercase text-gray-500">Phone Number</label>
                <input type="text" name="phone_number" value="{{ old('phone_number') }}" class="w-full rounded-xl border border-red-100 bg-red-50/50 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-300" placeholder="09xxxxxxxxx (Optional)">
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full rounded-xl bg-red-600 px-4 py-3 text-sm font-bold text-white hover:bg-red-700 transition shadow-md hover:shadow-lg">
                    Create Chairman Account
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit User Details -->
<div id="editUserModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
    <div class="w-full max-w-xl rounded-3xl bg-white p-6 shadow-2xl transition-all">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-black text-gray-900">Edit User Details</h2>
                <p class="text-xs text-gray-500">Update official account information.</p>
            </div>

            <button id="closeEditModal" type="button" class="text-2xl text-gray-400 hover:text-red-500 transition">
                &times;
            </button>
        </div>

        <form id="editUserForm" action="" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="mb-1.5 block text-xs font-black uppercase text-gray-500">Role</label>

                    <select id="edit_role" name="role" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-300" required>
                        <option value="sk_president">SK President</option>
                        <option value="sk_chairman">SK Chairman</option>
                        <option value="sk_secretary">SK Secretary</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-black uppercase text-gray-500">Status</label>

                    <select id="edit_status" name="status" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-300" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>

                    <p id="pendingStatusNote" class="hidden mt-1 text-[10px] text-yellow-600">
                        Pending chairmen are activated automatically after setting their password.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="mb-1.5 block text-xs font-black uppercase text-gray-500">First Name</label>
                    <input type="text" id="edit_first_name" name="first_name" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-300" required>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-black uppercase text-gray-500">Last Name</label>
                    <input type="text" id="edit_last_name" name="last_name" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-300" required>
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-black uppercase text-gray-500">Email Address</label>
                <input type="email" id="edit_email" name="email" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-300" required>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-black uppercase text-gray-500">Barangay</label>

                <select id="edit_barangay_id" name="barangay_id" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-300" required>
                    <option value="">Select barangay</option>

                    @foreach ($barangays as $barangay)
                        <option value="{{ $barangay->barangay_id }}">{{ $barangay->barangay_name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-black uppercase text-gray-500">Phone Number</label>
                <input type="text" id="edit_phone_number" name="phone_number" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-300" placeholder="09xxxxxxxxx">
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full rounded-xl bg-red-600 px-4 py-3 text-sm font-bold text-white hover:bg-red-700 transition shadow-md hover:shadow-lg">
                    Update User Details
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Import CSV -->
<div id="importModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
    <div class="w-full max-w-xl rounded-3xl bg-white p-6 shadow-2xl transition-all">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-black text-gray-900">Import SK Chairmen</h2>
                <p class="text-xs text-gray-500">Download the template, fill it out, then upload it below.</p>
            </div>

            <button id="closeImportModal" type="button" class="text-2xl text-gray-400 hover:text-red-500 transition">
                &times;
            </button>
        </div>

        <div class="mb-4">
            <a href="{{ route('sk_pres.user-management.csv-template') }}" class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-xs font-bold text-red-600 hover:bg-red-100 transition">
                <span>&#128229;</span> Download CSV Template
            </a>
        </div>

        @if ($errors->csvImport->any())
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                <div class="flex items-start gap-2">
                    <span class="text-red-500">&#9888;</span>

                    <div>
                        <p class="text-xs font-black text-red-700">CSV Import Failed</p>

                        <p class="mt-1 text-[11px] text-red-600">
                            No accounts were imported. Please fix the following errors:
                        </p>

                        <ul class="mt-2 list-disc list-inside space-y-1 text-[11px] text-red-600">
                            @foreach ($errors->csvImport->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <form action="{{ route('sk_pres.user-management.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div class="rounded-2xl border-2 border-dashed border-red-200 bg-red-50/50 p-6 text-center">
                <span class="text-3xl">&#128194;</span>

                <label for="csv_file" class="mt-2 block text-xs font-black uppercase text-gray-600 cursor-pointer">
                    Select File
                </label>

                <input
                    type="file"
                    name="csv_file"
                    id="csv_file"
                    accept=".csv,.txt"
                    required
                    class="mt-3 block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-red-500 file:text-white hover:file:bg-red-600 cursor-pointer"
                >

                <p class="mt-2 text-[10px] text-gray-400">
                    Supported formats: .csv, .txt
                </p>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full rounded-xl bg-red-600 px-4 py-3 text-sm font-bold text-white hover:bg-red-700 transition shadow-md hover:shadow-lg">
                    Upload & Import Accounts
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Prepare old Bulk Add values -->
@php
    $oldOfficials=old('officials',[
        [
            'full_name'=>'',
            'email'=>'',
            'phone_number'=>'',
            'barangay_id'=>'',
            'term_start'=>'',
            'term_end'=>'',
        ]
    ]);

    $bulkIndexes=array_map('intval',array_keys($oldOfficials));
    $bulkNextIndex=empty($bulkIndexes) ? 0 : max($bulkIndexes)+1;
@endphp

<!-- Modal: Bulk Add SK Chairmen -->
<div id="bulkModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
    <div class="w-full max-w-6xl rounded-3xl bg-white p-6 shadow-2xl transition-all max-h-[90vh] overflow-y-auto">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-black text-gray-900">Bulk Add SK Chairmen</h2>
                <p class="text-xs text-gray-500">Create multiple SK Chairman accounts at once.</p>
            </div>

            <button id="closeBulkModal" type="button" class="text-2xl text-gray-400 hover:text-red-500 transition">
                &times;
            </button>
        </div>

        @if ($errors->bulkAdd->any())
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                <div class="flex items-start gap-2">
                    <span class="text-red-500">&#9888;</span>

                    <div>
                        <p class="text-xs font-black text-red-700">Bulk Add Failed</p>

                        <p class="mt-1 text-[11px] text-red-600">
                            Your entries were kept. Please fix the following:
                        </p>

                        <ul class="mt-2 list-disc list-inside space-y-1 text-[11px] text-red-600">
                            @foreach ($errors->bulkAdd->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <form action="{{ route('sk_pres.user-management.store-bulk-officials') }}" method="POST">
            @csrf

            <div id="bulkRows" class="space-y-4">
                @foreach ($oldOfficials as $index=>$official)
                    <div class="bulk-row rounded-2xl border border-gray-200 bg-gray-50 p-4">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="bulk-title text-sm font-black text-gray-700">
                                Chairman #{{ $loop->iteration }}
                            </h3>

                            <button type="button" class="removeBulkRow {{ $loop->first ? 'hidden' : '' }} text-xs font-bold text-red-500 hover:text-red-700">
                                Remove
                            </button>
                        </div>

                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                            <div>
                                <label class="mb-1.5 block text-xs font-black uppercase text-gray-500">Full Name</label>

                                <input
                                    type="text"
                                    name="officials[{{ $index }}][full_name]"
                                    value="{{ $official['full_name'] ?? '' }}"
                                    class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-300"
                                    required
                                >
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-black uppercase text-gray-500">Email Address</label>

                                <input
                                    type="email"
                                    name="officials[{{ $index }}][email]"
                                    value="{{ $official['email'] ?? '' }}"
                                    class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-300"
                                    required
                                >
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-black uppercase text-gray-500">Phone Number</label>

                                <input
                                    type="text"
                                    name="officials[{{ $index }}][phone_number]"
                                    value="{{ $official['phone_number'] ?? '' }}"
                                    class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-300"
                                    placeholder="09xxxxxxxxx (Optional)"
                                >
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-black uppercase text-gray-500">Barangay</label>

                                <select
                                    name="officials[{{ $index }}][barangay_id]"
                                    class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-300"
                                    required
                                >
                                    <option value="">Select barangay</option>

                                    @foreach ($barangays as $barangay)
                                        <option
                                            value="{{ $barangay->barangay_id }}"
                                            {{ (string)($official['barangay_id'] ?? '') === (string)$barangay->barangay_id ? 'selected' : '' }}
                                        >
                                            {{ $barangay->barangay_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-black uppercase text-gray-500">Term Start</label>

                                <input
                                    type="date"
                                    name="officials[{{ $index }}][term_start]"
                                    value="{{ $official['term_start'] ?? '' }}"
                                    class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-300"
                                    required
                                >
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-black uppercase text-gray-500">Term End</label>

                                <input
                                    type="date"
                                    name="officials[{{ $index }}][term_end]"
                                    value="{{ $official['term_end'] ?? '' }}"
                                    class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-300"
                                    required
                                >
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-5 flex items-center justify-between gap-3">
                <button id="addBulkRow" type="button" class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-xs font-bold text-gray-600 hover:bg-gray-50 transition">
                    + Add Another Chairman
                </button>

                <button type="submit" class="rounded-xl bg-red-600 px-6 py-3 text-sm font-bold text-white hover:bg-red-700 transition shadow-md hover:shadow-lg">
                    Create All Accounts
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const profileDropdownBtn=document.getElementById('profileDropdownBtn');
    const profileMenu=document.getElementById('profileMenu');

    const openOfficialModal=document.getElementById('openOfficialModal');
    const officialModal=document.getElementById('officialModal');
    const closeOfficialModal=document.getElementById('closeOfficialModal');

    const openImportModal=document.getElementById('openImportModal');
    const importModal=document.getElementById('importModal');
    const closeImportModal=document.getElementById('closeImportModal');

    const openBulkModal=document.getElementById('openBulkModal');
    const bulkModal=document.getElementById('bulkModal');
    const closeBulkModal=document.getElementById('closeBulkModal');
    const addBulkRow=document.getElementById('addBulkRow');
    const bulkRows=document.getElementById('bulkRows');

    const editUserModal=document.getElementById('editUserModal');
    const closeEditModal=document.getElementById('closeEditModal');
    const editUserForm=document.getElementById('editUserForm');

    const showModal=(modal)=>{
        if(!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    const hideModal=(modal)=>{
        if(!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    // Profile Dropdown
    if(profileDropdownBtn && profileMenu){
        profileDropdownBtn.addEventListener('click',(e)=>{
            e.stopPropagation();
            profileMenu.classList.toggle('hidden');
        });
    }

    // Modal Controls
    if(openOfficialModal){
        openOfficialModal.addEventListener('click',()=>showModal(officialModal));
    }

    if(closeOfficialModal){
        closeOfficialModal.addEventListener('click',()=>hideModal(officialModal));
    }

    if(openImportModal){
        openImportModal.addEventListener('click',()=>showModal(importModal));
    }

    if(closeImportModal){
        closeImportModal.addEventListener('click',()=>hideModal(importModal));
    }

    if(openBulkModal){
        openBulkModal.addEventListener('click',()=>showModal(bulkModal));
    }

    if(closeBulkModal){
        closeBulkModal.addEventListener('click',()=>hideModal(bulkModal));
    }

    if(closeEditModal){
        closeEditModal.addEventListener('click',()=>hideModal(editUserModal));
    }

    // Edit User
    document.querySelectorAll('.edit-user-btn').forEach((btn)=>{
        btn.addEventListener('click',(e)=>{
            e.stopPropagation();

            const userData=JSON.parse(btn.getAttribute('data-user') || '{}');

            if(!userData || !userData.id) return;

            editUserForm.action=`/sk_pres/user-management/${userData.id}`;

            document.getElementById('edit_first_name').value=userData.first_name || '';
            document.getElementById('edit_last_name').value=userData.last_name || '';
            document.getElementById('edit_email').value=userData.email || '';
            document.getElementById('edit_role').value=userData.role || 'sk_chairman';
            document.getElementById('edit_barangay_id').value=userData.barangay_id || '';
            document.getElementById('edit_phone_number').value=userData.phone_number || '';

            const statusSelect=document.getElementById('edit_status');
            const pendingStatusNote=document.getElementById('pendingStatusNote');
            const activeOption=statusSelect.querySelector('option[value="active"]');

            const isPending=
                userData.role==='sk_chairman' &&
                Number(userData.is_verified)===0;

            if(activeOption){
                activeOption.disabled=isPending;
            }

            if(isPending){
                statusSelect.value='inactive';

                if(pendingStatusNote){
                    pendingStatusNote.classList.remove('hidden');
                }
            }else{
                statusSelect.value=userData.status || 'active';

                if(pendingStatusNote){
                    pendingStatusNote.classList.add('hidden');
                }
            }

            showModal(editUserModal);
        });
    });

    // Close Modals on Backdrop
    [officialModal,importModal,editUserModal,bulkModal].forEach((modal)=>{
        if(modal){
            modal.addEventListener('click',(e)=>{
                if(e.target===modal){
                    hideModal(modal);
                }
            });
        }
    });

    // Close Modals on Escape
    window.addEventListener('keydown',(e)=>{
        if(e.key==='Escape'){
            hideModal(officialModal);
            hideModal(importModal);
            hideModal(editUserModal);
            hideModal(bulkModal);
        }
    });

    // Bulk Add Rows
    let bulkRowIndex={{ $bulkNextIndex }};

    if(addBulkRow && bulkRows){
        addBulkRow.addEventListener('click',()=>{
            const firstRow=bulkRows.querySelector('.bulk-row');

            if(!firstRow) return;

            const newRow=firstRow.cloneNode(true);

            newRow.querySelectorAll('input,select').forEach((field)=>{
                const currentName=field.getAttribute('name');

                if(currentName){
                    field.setAttribute(
                        'name',
                        currentName.replace(
                            /officials\[\d+\]/,
                            `officials[${bulkRowIndex}]`
                        )
                    );
                }

                if(field.tagName==='SELECT'){
                    field.selectedIndex=0;
                }else{
                    field.value='';
                }
            });

            const removeButton=newRow.querySelector('.removeBulkRow');

            if(removeButton){
                removeButton.classList.remove('hidden');
            }

            bulkRows.appendChild(newRow);
            bulkRowIndex++;
            updateBulkTitles();
        });
    }

    document.addEventListener('click',(e)=>{
        if(e.target.classList.contains('removeBulkRow')){
            const row=e.target.closest('.bulk-row');

            if(row){
                row.remove();
                updateBulkTitles();
            }
        }
    });

    function updateBulkTitles(){
        document.querySelectorAll('#bulkRows .bulk-row').forEach((row,index)=>{
            const title=row.querySelector('.bulk-title');

            if(title){
                title.textContent=`Chairman #${index+1}`;
            }

            const removeButton=row.querySelector('.removeBulkRow');

            if(removeButton){
                removeButton.classList.toggle('hidden',index===0);
            }
        });
    }

    // Action Menu
    document.querySelectorAll('.action-menu-btn').forEach((btn)=>{
        btn.addEventListener('click',(e)=>{
            e.stopPropagation();

            const currentMenu=btn.nextElementSibling;

            document.querySelectorAll('.action-menu').forEach((menu)=>{
                if(menu!==currentMenu){
                    menu.classList.add('hidden');
                }
            });

            if(currentMenu){
                currentMenu.classList.toggle('hidden');
            }
        });
    });

    // Real-time Search
    const liveSearchInputs=document.querySelectorAll('.live-user-search');
    const userGroups=document.querySelectorAll('.user-group');
    const noSearchResults=document.getElementById('noSearchResults');

    function filterUsers(value){
        const query=value.trim().toLowerCase();
        let totalVisible=0;

        liveSearchInputs.forEach((input)=>{
            if(input.value!==value){
                input.value=value;
            }
        });

        userGroups.forEach((group)=>{
            const cards=group.querySelectorAll('.user-card');
            const countBadge=group.querySelector('.group-count');
            const emptyMessage=group.querySelector('.live-search-empty');
            const noUsersMessage=group.querySelector('.no-users-message');
            let visibleCount=0;

            cards.forEach((card)=>{
                const text=(card.dataset.search || '').toLowerCase();
                const matches=query==='' || text.includes(query);

                card.classList.toggle('hidden',!matches);

                if(matches){
                    visibleCount++;
                    totalVisible++;
                }
            });

            if(noUsersMessage){
                noUsersMessage.classList.toggle('hidden',query!=='');
            }

            if(emptyMessage){
                emptyMessage.classList.toggle(
                    'hidden',
                    query==='' || visibleCount>0
                );
            }

            if(countBadge){
                if(query===''){
                    countBadge.textContent=countBadge.dataset.originalCount;
                }else{
                    countBadge.textContent=visibleCount;
                }
            }

            if(query!==''){
                group.open=visibleCount>0;
            }else{
                group.open=group.dataset.defaultOpen==='1';
            }
        });

        if(noSearchResults){
            noSearchResults.classList.toggle(
                'hidden',
                query==='' || totalVisible>0
            );
        }
    }

    liveSearchInputs.forEach((input)=>{
        input.addEventListener('input',()=>{
            filterUsers(input.value);
        });
    });

    // Global Click
    window.addEventListener('click',(e)=>{
        if(
            profileMenu &&
            profileDropdownBtn &&
            !profileMenu.contains(e.target) &&
            !profileDropdownBtn.contains(e.target)
        ){
            profileMenu.classList.add('hidden');
        }

        document.querySelectorAll('.action-menu').forEach((menu)=>{
            if(!menu.contains(e.target)){
                menu.classList.add('hidden');
            }
        });
    });

    // Open correct modal after validation error
    @if ($errors->csvImport->any())
        showModal(importModal);
    @elseif ($errors->bulkAdd->any())
        showModal(bulkModal);
    @elseif ($errors->getBag('default')->any())
        showModal(officialModal);
    @endif
});
</script>
@endpush