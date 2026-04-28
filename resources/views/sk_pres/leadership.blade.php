@extends('layouts.app')

@section('title', 'SK 360 Leadership')

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
                <input type="text" placeholder="Search..." class="w-full px-4 py-2 rounded-full text-black focus:outline-none text-sm">
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
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-800">SK Barangay Leadership Profiles</h1>
                <p class="text-sm text-gray-500">Leadership directory for all SK Councils across Lipa City</p>
            </div>

            <div class="mb-8">
                <div class="mx-auto flex w-full max-w-lg rounded-full bg-gray-100 p-1">
                    <a
                        href="{{ route('sk_pres.leadership', ['barangay_id' => $selectedBarangayId]) }}"
                        class="flex-1 rounded-full px-4 py-2 text-center text-[11px] font-black transition {{ $activeTab === 'directory' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
                    >
                        Leadership Directory
                    </a>
                    <a
                        href="{{ route('sk_pres.leadership', ['tab' => 'transition', 'barangay_id' => $selectedBarangayId]) }}"
                        class="flex-1 rounded-full px-4 py-2 text-center text-[11px] font-black transition {{ $activeTab === 'transition' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
                    >
                        Leadership Transition Guide
                    </a>
                </div>
            </div>

            @if ($activeTab === 'directory')
                <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between mb-8">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-800 uppercase">SK Council Leadership Profile</h2>
                        <p class="text-gray-500">View executive officers and councilors for every barangay.</p>
                    </div>

                    <form method="GET" action="{{ route('sk_pres.leadership') }}" class="w-full md:w-auto">
                        <input type="hidden" name="tab" value="directory">
                        <label for="barangay_id" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Select Barangay</label>
                        <div class="flex gap-2">
                            <select id="barangay_id" name="barangay_id" class="min-w-[260px] rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-red-400">
                                @foreach ($barangays as $barangay)
                                    <option value="{{ $barangay->barangay_id }}" {{ $selectedBarangayId === (int) $barangay->barangay_id ? 'selected' : '' }}>
                                        Barangay {{ $barangay->barangay_name }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="rounded-xl bg-red-600 px-5 py-3 text-sm font-bold text-white hover:bg-red-700 transition">
                                View
                            </button>
                        </div>
                    </form>
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
                                    <div class="flex flex-col gap-2 md:flex-row md:items-center md:gap-3">
                                        <h4 class="text-lg font-black text-gray-800 uppercase leading-none">{{ $member['name'] }}</h4>
                                        <span class="w-fit px-3 py-1 rounded-full text-[9px] font-black uppercase bg-red-600 text-white">
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
                            <p class="text-gray-400 italic">No executive officers found for this barangay.</p>
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
                            <p class="text-gray-400 italic">No councilors found for this barangay.</p>
                        @endforelse
                    </div>
                </div>
            @else
                <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-8">
                    <div class="mb-8">
                        <h2 class="text-xl font-black text-gray-800">Leadership Transition &amp; Account Handover Process</h2>
                        <p class="text-sm text-gray-500 mt-1">Official guidelines for transferring leadership and platform access to new SK council members</p>
                    </div>

                    <div class="mb-8 rounded-2xl border border-red-100 bg-red-50 p-5">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 text-red-500">&#9711;</span>
                            <div>
                                <h3 class="text-sm font-black text-gray-800 mb-2">Overview</h3>
                                <p class="text-sm text-gray-600 leading-6">
                                    When new SK council members are elected, a structured transition process ensures continuity of governance and secure transfer of platform access.
                                    This process is managed by the SK Federation President and follows official protocols established by the Commission on Elections (COMELEC)
                                    and the Department of the Interior and Local Government (DILG).
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mb-8">
                        <h3 class="mb-6 text-sm font-black text-gray-800">Step-by-Step Transition Process</h3>

                        <div class="space-y-6">
                            <div class="flex gap-4">
                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-red-500 text-xs font-black text-white">1</div>
                                <div class="flex-1">
                                    <h4 class="text-sm font-black text-gray-800">Election Results Verification</h4>
                                    <p class="mt-1 text-sm text-gray-600">After the SK election, the COMELEC certifies the winning candidates. The SK Federation President receives official documentation of all newly elected SK Chairmen and Secretaries across Lipa City.</p>
                                    <ul class="mt-3 space-y-1 text-sm text-gray-600">
                                        <li>&#9679; COMELEC certification required</li>
                                        <li>&#9679; Oath-taking ceremony completion</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-500 text-xs font-black text-white">2</div>
                                <div class="flex-1">
                                    <h4 class="text-sm font-black text-gray-800">Deactivation of Old Council Accounts</h4>
                                    <p class="mt-1 text-sm text-gray-600">The SK Federation President logs into the User Management module and systematically deactivates all accounts belonging to outgoing SK council members. This ensures former officers can no longer submit reports or access administrative functions.</p>
                                    <ul class="mt-3 space-y-1 text-sm text-gray-600">
                                        <li>&#9679; Historical data preserved for archival purposes</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-green-500 text-xs font-black text-white">3</div>
                                <div class="flex-1">
                                    <h4 class="text-sm font-black text-gray-800">Creation of New Council Accounts</h4>
                                    <p class="mt-1 text-sm text-gray-600">Using the User Management "Add SK Official" feature, the Federation President creates new accounts for incoming SK Chairmen and Secretaries. Each account is assigned to the correct barangay and role.</p>
                                    <div class="mt-3 rounded-2xl bg-gray-50 p-4">
                                        <ul class="space-y-1 text-sm text-gray-600">
                                            <li>&#9679; Full name of new officer</li>
                                            <li>&#9679; Official SK email address</li>
                                            <li>&#9679; Role assignment (SK Chairman or SK Secretary)</li>
                                            <li>&#9679; Barangay assignment</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-yellow-500 text-xs font-black text-white">4</div>
                                <div class="flex-1">
                                    <h4 class="text-sm font-black text-gray-800">Account Credentials Distribution</h4>
                                    <p class="mt-1 text-sm text-gray-600">The SK Federation President provides login credentials to new officers through official channels. Initial passwords are temporary and must be changed upon first login for security.</p>
                                    <ul class="mt-3 space-y-1 text-sm text-gray-600">
                                        <li>&#9679; Credentials sent via official email</li>
                                        <li>&#9679; Mandatory password change on first login</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-purple-500 text-xs font-black text-white">5</div>
                                <div class="flex-1">
                                    <h4 class="text-sm font-black text-gray-800">Orientation &amp; Training</h4>
                                    <p class="mt-1 text-sm text-gray-600">New SK officers attend an orientation session where they learn to use the SK 360&deg; platform, understand their responsibilities, and receive training on report submission, budget management, and communication protocols.</p>
                                    <ul class="mt-3 space-y-1 text-sm text-gray-600">
                                        <li>&#9679; Platform navigation tutorial</li>
                                        <li>&#9679; Role-specific feature training</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-8 rounded-2xl border border-blue-100 bg-blue-50 p-5">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 text-blue-500">&#128100;</span>
                            <div>
                                <h3 class="text-sm font-black text-gray-800 mb-2">Who is Responsible for Account Turnover?</h3>
                                <p class="text-sm text-gray-600 leading-6">
                                    The SK Federation President is the sole authority responsible for managing account transitions. They have exclusive access to the User Management module and are accountable for:
                                </p>
                                <ul class="mt-3 space-y-1 text-sm text-gray-600">
                                    <li>&#10003; Verifying the legitimacy of newly elected officers</li>
                                    <li>&#10003; Deactivating outgoing council member accounts</li>
                                    <li>&#10003; Creating and activating new officer accounts</li>
                                    <li>&#10003; Ensuring secure distribution of login credentials</li>
                                    <li>&#10003; Maintaining audit trails of all user management activities</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-amber-100 bg-amber-50 p-5">
                        <h3 class="text-sm font-black text-gray-800 mb-3">Important Notes</h3>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li>&#8226; Old council members can not transfer their own accounts. Only the Federation President has this authority.</li>
                            <li>&#8226; Deactivated accounts retain historical data for government auditing and record-keeping purposes.</li>
                            <li>&#8226; This transition process should be completed within 30 days of the official oath-taking ceremony.</li>
                            <li>&#8226; All account changes are logged in the system audit trail for transparency and accountability.</li>
                        </ul>
                    </div>
                </div>
            @endif
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const dropdownBtn = document.getElementById('profileDropdownBtn');
    const profileMenu = document.getElementById('profileMenu');

    if (dropdownBtn && profileMenu) {
        dropdownBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            profileMenu.classList.toggle('hidden');
        });

        window.addEventListener('click', (e) => {
            if (!profileMenu.contains(e.target) && !dropdownBtn.contains(e.target)) {
                profileMenu.classList.add('hidden');
            }
        });
    }
</script>
@endpush
