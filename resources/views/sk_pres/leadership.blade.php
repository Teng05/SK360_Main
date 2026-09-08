{{-- File guide: View-only leadership directory for resources/views/sk_pres/leadership.blade.php. --}}
@extends('layouts.app')

@section('title','SK 360 Leadership')

@section('page_css')
<script src="https://cdn.tailwindcss.com"></script>
@endsection

@section('content')
<div class="flex h-screen bg-gray-100">

    <!-- Sidebar -->
    <div class="w-64 bg-red-600 text-white flex flex-col p-3 overflow-y-auto shrink-0">

        <div class="flex items-center gap-3 mb-4">

            <img src="{{ asset('images/logo.png') }}"
                class="w-8 h-8 rounded-full object-cover"
                alt="logo">

            <div class="leading-tight">
                <h2 class="text-lg font-extrabold tracking-wide">
                    SK 360°
                </h2>

                <p class="text-[10px] opacity-80">
                    Management System
                </p>
            </div>
        </div>

        <div class="bg-red-500 rounded-lg p-2 flex items-center gap-2 mb-3 shadow text-xs">

            <div class="bg-yellow-400 text-red-600 p-1 rounded-full text-sm">
                &#128100;
            </div>

            <div>
                <p class="font-semibold text-xs">
                    {{ $fullName }}
                </p>

                <p class="text-xs opacity-80">
                    SK President
                </p>
            </div>
        </div>

        <nav class="space-y-1 text-xs">

            @foreach($menuItems as $item)

                @php
                    $isActive=$item['link'] === $currentUrl;
                @endphp

                <a href="{{ $item['link'] }}"
                    class="flex items-center gap-2 p-2 rounded-lg {{ $isActive ? 'bg-red-500' : 'hover:bg-red-500 transition' }}">

                    <span class="{{ $isActive ? 'bg-yellow-400 text-red-600' : 'bg-red-400' }} p-1 rounded text-sm">
                        {!! $item['icon'] !!}
                    </span>

                    <span class="{{ $isActive ? 'text-yellow-300 font-semibold' : '' }} text-xs">
                        {{ $item['label'] }}
                    </span>
                </a>

            @endforeach
        </nav>
    </div>

    <!-- Main -->
    <div class="flex-1 flex flex-col overflow-hidden">

        <!-- Header -->
        <div class="bg-red-600 text-white px-6 py-3 flex justify-between items-center shadow relative z-20">

            <div class="w-1/4"></div>

            <div class="w-1/3">

                <input type="text"
                    id="leadershipSearch"
                    placeholder="Search name, barangay, position..."
                    class="w-full px-4 py-2 rounded-full text-black focus:outline-none text-sm"
                    autocomplete="off">
            </div>

            <div class="w-1/4 flex justify-end items-center gap-5 text-sm">

                <button type="button"
                    class="hover:opacity-80">
                    &#128276;
                </button>

                <div class="relative">

                    <button id="profileDropdownBtn"
                        type="button"
                        class="flex items-center gap-2 font-semibold focus:outline-none hover:opacity-80 transition">

                        <span>{{ $fullName }}</span>

                        <span class="text-[10px]">
                            &#9660;
                        </span>
                    </button>

                    <div id="profileMenu"
                        class="absolute right-0 mt-3 w-48 bg-white rounded-xl shadow-2xl py-2 z-[9999] hidden border border-gray-100">

                        <div class="px-4 py-3 border-b border-gray-50">

                            <p class="text-[10px] text-gray-400 uppercase font-black tracking-widest">
                                Account Settings
                            </p>
                        </div>

                        <a href="{{ route('sk_pres.profile') }}"
                            class="px-4 py-3 text-gray-700 hover:bg-gray-50 text-xs flex items-center gap-2 transition">

                            <span>&#128100;</span>

                            View Profile
                        </a>

                        <form action="{{ route('logout') }}"
                            method="POST">

                            @csrf

                            <button type="submit"
                                class="w-full text-left px-4 py-3 text-red-600 hover:bg-red-50 text-xs font-bold flex items-center gap-2 transition">

                                <span>
                                    &#128682;
                                </span>

                                Log Out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <main class="p-8 overflow-y-auto h-full bg-gray-50">

            <!-- Page Title -->
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between mb-6">

                <div>

                    <div class="flex items-center gap-2">

                        <h1 class="text-3xl font-black text-gray-800">
                            SK Barangay Leadership
                        </h1>

                        <span class="rounded-full bg-gray-200 px-3 py-1 text-[9px] font-black uppercase text-gray-600">
                            View Only
                        </span>
                    </div>

                    <p class="text-sm text-gray-500 mt-1">
                        Current leadership directory for SK councils across Lipa City.
                    </p>
                </div>

                <!-- Barangay Filter -->
                <form method="GET"
                    action="{{ route('sk_pres.leadership') }}"
                    class="w-full lg:w-auto">

                    <label for="barangay_id"
                        class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">

                        Barangay Filter
                    </label>

                    <div class="flex gap-2">

                        <select id="barangay_id"
                            name="barangay_id"
                            class="w-full lg:min-w-[280px] rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-red-400">

                            <option value="0"
                                {{ $selectedBarangayId === 0 ? 'selected' : '' }}>

                                All Barangays
                            </option>

                            @foreach($barangays as $barangay)

                                <option value="{{ $barangay->barangay_id }}"
                                    {{ $selectedBarangayId === (int)$barangay->barangay_id ? 'selected' : '' }}>

                                    Barangay {{ $barangay->barangay_name }}
                                </option>

                            @endforeach
                        </select>

                        <button type="submit"
                            class="rounded-xl bg-red-600 px-5 py-3 text-xs font-black uppercase text-white hover:bg-red-700 transition">

                            View
                        </button>
                    </div>
                </form>
            </div>

            <!-- Current Administration -->
            <div class="bg-red-600 rounded-2xl p-5 text-white mb-6 shadow-md flex flex-col gap-3 md:flex-row md:items-center md:justify-between">

                <div class="flex items-center gap-4">

                    <div class="bg-white/20 p-3 rounded-xl text-2xl">
                        &#128197;
                    </div>

                    <div>

                        <p class="text-[10px] font-black uppercase tracking-widest opacity-70">
                            Current Administration
                        </p>

                        @if($currentAdministration)

                            <h2 class="text-xl font-black">
                                {{ $currentAdministration->start_year }}
                                -
                                {{ $currentAdministration->end_year }}
                            </h2>

                        @else

                            <h2 class="text-xl font-black">
                                No Active Administration
                            </h2>

                        @endif
                    </div>
                </div>

                <div class="rounded-xl bg-white/10 border border-white/20 px-4 py-2">

                    <p class="text-[10px] font-black uppercase tracking-widest">
                        Directory Scope
                    </p>

                    <p class="text-xs mt-1">
                        {{ $selectedBarangayId > 0 ? 'Selected Barangay' : 'All Barangays' }}
                    </p>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3 mb-6">

                @foreach($stats as $stat)

                    <div class="rounded-2xl border border-gray-100 bg-white px-4 py-4 shadow-sm">

                        <div class="flex items-center justify-between gap-3">

                            <div>

                                <p class="text-[9px] font-black uppercase text-gray-400">
                                    {{ $stat['label'] }}
                                </p>

                                <p class="text-2xl font-black text-gray-800 mt-1">
                                    {{ $stat['value'] }}
                                </p>
                            </div>

                            <span class="text-xl text-gray-400">
                                {!! $stat['icon'] !!}
                            </span>
                        </div>
                    </div>

                @endforeach
            </div>

            <!-- Federation President -->
            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 mb-6">

                <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-4">

                    <div>

                        <h3 class="text-xs font-black uppercase tracking-[0.18em] text-gray-700">
                            SK Federation Leadership
                        </h3>

                        <p class="text-[10px] text-gray-400 mt-1">
                            Current President and pending successor, if any.
                        </p>
                    </div>

                    <span class="rounded-full bg-red-50 px-3 py-1 text-[9px] font-black uppercase text-red-600">
                        Federation
                    </span>
                </div>

                <div class="space-y-3">

                    @forelse($federationPresidents as $president)

                        @php
                            $pending=($president['assignment_status'] ?? '') === 'pending';

                            $searchText=strtolower(
                                ($president['name'] ?? '').' '.
                                ($president['position'] ?? '').' '.
                                ($president['email'] ?? '').' '.
                                ($president['phone'] ?? '').' '.
                                'president federation'
                            );
                        @endphp

                        <div class="federation-card flex items-center gap-4 rounded-2xl border border-red-100 bg-red-50/30 p-4"
                            data-search="{{ $searchText }}">

                            <div class="w-12 h-12 rounded-xl bg-red-600 text-white flex items-center justify-center font-black">
                                {{ strtoupper(substr($president['name'] ?: 'PR',0,2)) }}
                            </div>

                            <div class="flex-1 min-w-0">

                                <div class="flex flex-wrap items-center gap-2">

                                    <h4 class="font-black text-gray-800 uppercase">
                                        {{ $president['name'] }}
                                    </h4>

                                    <span class="rounded-full bg-red-600 px-2.5 py-1 text-[8px] font-black uppercase text-white">
                                        SK Federation President
                                    </span>

                                    @if($pending)

                                        <span class="rounded-full bg-yellow-100 px-2.5 py-1 text-[8px] font-black uppercase text-yellow-700">
                                            Pending Setup
                                        </span>

                                    @else

                                        <span class="rounded-full bg-green-100 px-2.5 py-1 text-[8px] font-black uppercase text-green-700">
                                            Current
                                        </span>

                                    @endif
                                </div>

                                <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-[10px] text-gray-500">

                                    <span>
                                        &#128231;
                                        {{ $president['email'] ?: 'No email provided' }}
                                    </span>

                                    <span>
                                        &#128222;
                                        {{ $president['phone'] ?: 'No phone provided' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                    @empty

                        <div class="rounded-2xl bg-gray-50 p-5 text-sm text-gray-400 italic">
                            No President assignment is connected to the current administration.
                        </div>

                    @endforelse
                </div>
            </div>

            <!-- Barangay Groups -->
            <div id="leadershipGroups"
                class="space-y-4">

                @forelse($leadershipGroups as $index=>$group)

                    @php
                        $membersForSearch=collect([
                            $group['chairman'],
                            $group['secretary'],
                            $group['treasurer'],
                        ])
                        ->filter()
                        ->concat($group['councilors']);

                        $groupSearch=strtolower(
                            'barangay '.
                            $group['barangay_name'].
                            ' '.
                            $membersForSearch
                                ->map(function($member){
                                    return
                                        ($member['name'] ?? '').
                                        ' '.
                                        ($member['position'] ?? '').
                                        ' '.
                                        ($member['email'] ?? '').
                                        ' '.
                                        ($member['phone'] ?? '').
                                        ' '.
                                        ($member['assignment_status'] ?? '');
                                })
                                ->implode(' ')
                        );

                        $shouldOpen=
                            $selectedBarangayId > 0 ||
                            $index === 0;
                    @endphp

                    <details
                        class="leadership-group rounded-2xl border border-gray-200 bg-white shadow-sm"
                        data-search="{{ $groupSearch }}"
                        data-default-open="{{ $shouldOpen ? '1' : '0' }}"
                        {{ $shouldOpen ? 'open' : '' }}>

                        <summary class="cursor-pointer list-none px-5 py-4 hover:bg-gray-50 transition">

                            <div class="flex items-center justify-between gap-4">

                                <div class="flex items-center gap-3">

                                    <div class="rounded-xl bg-red-50 p-2.5 text-red-500">
                                        &#128205;
                                    </div>

                                    <div>

                                        <h3 class="text-sm font-black text-gray-800">
                                            Barangay {{ $group['barangay_name'] }}
                                        </h3>

                                        <p class="text-[10px] text-gray-400 mt-1">
                                            {{ $group['member_count'] }}
                                            current/pending
                                            member{{ $group['member_count'] === 1 ? '' : 's' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">

                                    <span class="rounded-full bg-gray-100 px-3 py-1 text-[9px] font-black text-gray-500">

                                        {{ $group['councilors']->count() }}
                                        Councilors
                                    </span>

                                    <span class="text-gray-400">
                                        &#9662;
                                    </span>
                                </div>
                            </div>
                        </summary>

                        <div class="border-t border-gray-100 bg-gray-50/30 p-5">

                            <!-- Executive Officers -->
                            <div class="mb-6">

                                <div class="mb-3 flex items-center justify-between">

                                    <div>

                                        <h4 class="text-[10px] font-black uppercase tracking-[0.18em] text-gray-500">
                                            Executive Officers
                                        </h4>

                                        <p class="text-[9px] text-gray-400 mt-0.5">
                                            Chairman, Secretary and Treasurer
                                        </p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 xl:grid-cols-3 gap-3">

                                    @foreach([
                                        [
                                            'key'=>'chairman',
                                            'label'=>'SK Chairman',
                                            'avatar'=>'bg-green-600',
                                            'badge'=>'bg-green-50 text-green-700',
                                            'border'=>'border-green-100'
                                        ],
                                        [
                                            'key'=>'secretary',
                                            'label'=>'SK Secretary',
                                            'avatar'=>'bg-blue-600',
                                            'badge'=>'bg-blue-50 text-blue-700',
                                            'border'=>'border-blue-100'
                                        ],
                                        [
                                            'key'=>'treasurer',
                                            'label'=>'SK Treasurer',
                                            'avatar'=>'bg-yellow-500',
                                            'badge'=>'bg-yellow-50 text-yellow-700',
                                            'border'=>'border-yellow-100'
                                        ],
                                    ] as $positionConfig)

                                        @php
                                            $member=$group[$positionConfig['key']];
                                        @endphp

                                        @if($member)

                                            @php
                                                $pending=
                                                    ($member['assignment_status'] ?? '') === 'pending';
                                            @endphp

                                            <div class="rounded-2xl border {{ $positionConfig['border'] }} bg-white p-4">

                                                <div class="flex items-start gap-3">

                                                    <div class="w-11 h-11 rounded-xl {{ $positionConfig['avatar'] }} text-white flex items-center justify-center font-black shrink-0">

                                                        {{ strtoupper(substr($member['name'] ?: 'NA',0,2)) }}
                                                    </div>

                                                    <div class="min-w-0 flex-1">

                                                        <div class="flex flex-wrap items-center gap-2">

                                                            <h5 class="text-sm font-black text-gray-800 uppercase">
                                                                {{ $member['name'] }}
                                                            </h5>

                                                            <span class="rounded-full px-2 py-1 text-[8px] font-black uppercase {{ $positionConfig['badge'] }}">

                                                                {{ $positionConfig['label'] }}
                                                            </span>

                                                            @if($pending)

                                                                <span class="rounded-full bg-yellow-100 px-2 py-1 text-[8px] font-black uppercase text-yellow-700">
                                                                    Pending Setup
                                                                </span>

                                                            @endif
                                                        </div>

                                                        <div class="mt-2 space-y-1 text-[10px] text-gray-500">

                                                            <p class="truncate">
                                                                &#128231;
                                                                {{ $member['email'] ?: 'No email provided' }}
                                                            </p>

                                                            <p>
                                                                &#128222;
                                                                {{ $member['phone'] ?: 'No phone provided' }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        @else

                                            <div class="rounded-2xl border border-dashed border-gray-200 bg-white/60 p-4">

                                                <p class="text-[9px] font-black uppercase text-gray-400">
                                                    {{ $positionConfig['label'] }}
                                                </p>

                                                <p class="mt-2 text-xs font-bold text-gray-500">
                                                    Not yet assigned
                                                </p>

                                                <p class="mt-1 text-[9px] text-gray-400">
                                                    No current assignment found for this administration.
                                                </p>
                                            </div>

                                        @endif

                                    @endforeach
                                </div>
                            </div>

                            <!-- Councilors -->
                            <div>

                                <div class="mb-3 flex items-center justify-between">

                                    <div>

                                        <h4 class="text-[10px] font-black uppercase tracking-[0.18em] text-gray-500">
                                            SK Councilors
                                        </h4>

                                        <p class="text-[9px] text-gray-400 mt-0.5">
                                            Current council members for Barangay {{ $group['barangay_name'] }}
                                        </p>
                                    </div>

                                    <span class="rounded-full bg-purple-50 px-3 py-1 text-[9px] font-black text-purple-600">

                                        {{ $group['councilors']->count() }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">

                                    @forelse($group['councilors'] as $councilor)

                                        <div class="rounded-2xl border border-purple-100 bg-white p-4">

                                            <div class="flex items-center gap-3">

                                                <div class="w-10 h-10 rounded-xl bg-purple-600 text-white flex items-center justify-center font-black text-xs shrink-0">

                                                    {{ strtoupper(substr($councilor['name'] ?: 'NA',0,2)) }}
                                                </div>

                                                <div class="min-w-0 flex-1">

                                                    <h5 class="text-xs font-black text-gray-800 uppercase truncate">
                                                        {{ $councilor['name'] }}
                                                    </h5>

                                                    <p class="text-[9px] font-black uppercase text-purple-600 mt-1">
                                                        SK Councilor
                                                    </p>

                                                    <p class="text-[9px] text-gray-400 mt-1 truncate">
                                                        &#128231;
                                                        {{ $councilor['email'] ?: 'No email provided' }}
                                                    </p>

                                                    <p class="text-[9px] text-gray-400 mt-1">
                                                        &#128222;
                                                        {{ $councilor['phone'] ?: 'No phone provided' }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                    @empty

                                        <div class="md:col-span-2 xl:col-span-3 rounded-2xl border border-dashed border-gray-200 bg-white/60 p-5">

                                            <p class="text-xs font-bold text-gray-500">
                                                No SK Councilors assigned yet.
                                            </p>

                                            <p class="text-[9px] text-gray-400 mt-1">
                                                Councilors added by the SK Chairman will appear here automatically.
                                            </p>
                                        </div>

                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </details>

                @empty

                    <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center">

                        <p class="text-sm font-bold text-gray-600">
                            No barangays found.
                        </p>
                    </div>

                @endforelse
            </div>

            <!-- No Search Results -->
            <div id="leadershipNoResults"
                class="hidden mt-5 rounded-2xl border border-gray-200 bg-white p-8 text-center">

                <div class="text-3xl mb-2">
                    &#128269;
                </div>

                <p class="text-sm font-bold text-gray-600">
                    No leadership records matched your search.
                </p>

                <p class="text-xs text-gray-400 mt-1">
                    Try searching by official name, barangay, position, email or phone.
                </p>
            </div>
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{

    const dropdownBtn=
        document.getElementById('profileDropdownBtn');

    const profileMenu=
        document.getElementById('profileMenu');

    const searchInput=
        document.getElementById('leadershipSearch');

    const noResults=
        document.getElementById('leadershipNoResults');

    /*
    |--------------------------------------------------------------------------
    | PROFILE DROPDOWN
    |--------------------------------------------------------------------------
    */
    if(dropdownBtn && profileMenu){

        dropdownBtn.addEventListener('click',(e)=>{

            e.stopPropagation();

            profileMenu.classList.toggle('hidden');
        });

        window.addEventListener('click',(e)=>{

            if(
                !profileMenu.contains(e.target) &&
                !dropdownBtn.contains(e.target)
            ){
                profileMenu.classList.add('hidden');
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | LIVE LEADERSHIP SEARCH
    |--------------------------------------------------------------------------
    */
    if(searchInput){

        searchInput.addEventListener('input',()=>{

            const query=
                searchInput.value
                    .trim()
                    .toLowerCase();

            const groups=
                document.querySelectorAll(
                    '.leadership-group'
                );

            const federationCards=
                document.querySelectorAll(
                    '.federation-card'
                );

            let visible=0;

            groups.forEach((group)=>{

                const matches=
                    query==='' ||
                    (group.dataset.search || '')
                        .toLowerCase()
                        .includes(query);

                group.classList.toggle(
                    'hidden',
                    !matches
                );

                if(matches){

                    visible++;

                    group.open=
                        query!==''
                            ? true
                            : group.dataset.defaultOpen==='1';
                }
            });

            federationCards.forEach((card)=>{

                const matches=
                    query==='' ||
                    (card.dataset.search || '')
                        .toLowerCase()
                        .includes(query);

                card.classList.toggle(
                    'hidden',
                    !matches
                );

                if(matches){
                    visible++;
                }
            });

            if(noResults){

                noResults.classList.toggle(
                    'hidden',
                    query==='' ||
                    visible>0
                );
            }
        });
    }
});
</script>
@endpush