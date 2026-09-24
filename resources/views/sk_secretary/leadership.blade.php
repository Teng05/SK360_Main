@extends('layouts.app')

@section('title','SK Secretary Leadership')

@section('page_css')
<script src="https://cdn.tailwindcss.com"></script>
@endsection

@section('content')
<div class="flex h-screen bg-gray-100 overflow-hidden">

    @include('sk_secretary.partials.sidebar')

    <div class="flex-1 flex flex-col overflow-hidden">

        @include('sk_secretary.partials.topbar')

        <main class="p-8 overflow-y-auto h-full bg-gray-50">

            <!-- Page Title -->
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between mb-6">

                <div>
                    <div class="flex items-center gap-2">

                        <h1 class="text-3xl font-black text-gray-800 uppercase tracking-tight">
                            Council Leadership
                        </h1>

                        <span class="rounded-full bg-gray-200 px-3 py-1 text-[9px] font-black uppercase text-gray-600">
                            View Only
                        </span>
                    </div>

                    <p class="text-gray-500 font-medium mt-1">
                        {{ ($activeTab ?? 'current') === 'history'
                            ? 'View previous SK officials of Barangay '.$barangayName
                            : 'Current leadership directory for Barangay '.$barangayName }}
                    </p>
                </div>

                <!-- Search -->
                <div class="relative w-full md:w-80">

                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-300">
                        &#128269;
                    </span>

                    <input type="text"
                        id="leadershipSearch"
                        placeholder="{{ ($activeTab ?? 'current') === 'history' ? 'Search historical officials...' : 'Search officials...' }}"
                        autocomplete="off"
                        class="w-full rounded-xl border border-gray-200 bg-white py-3 pl-10 pr-4 text-sm text-gray-700 outline-none focus:ring-2 focus:ring-red-200">
                </div>
            </div>

            <!-- Tabs -->
            <div class="mb-6 rounded-2xl border border-gray-100 bg-white p-2 shadow-sm flex gap-2">

                <a href="{{ route('sk_secretary.leadership',['tab'=>'current']) }}"
                    class="flex-1 text-center rounded-xl px-4 py-3 text-xs font-black uppercase transition {{ ($activeTab ?? 'current') === 'current' ? 'bg-red-600 text-white' : 'text-gray-500 hover:bg-gray-50' }}">

                    &#128101; Current Leadership
                </a>

                <a href="{{ route('sk_secretary.leadership',['tab'=>'history']) }}"
                    class="flex-1 text-center rounded-xl px-4 py-3 text-xs font-black uppercase transition {{ ($activeTab ?? 'current') === 'history' ? 'bg-gray-800 text-white' : 'text-gray-500 hover:bg-gray-50' }}">

                    &#128220; Leadership History
                </a>
            </div>

            @if(($activeTab ?? 'current') === 'current')

                <!-- Current Administration -->
                <div class="bg-red-600 rounded-2xl p-6 text-white mb-6 shadow-md flex flex-col gap-4 md:flex-row md:justify-between md:items-center">

                    <div class="flex items-center gap-4">

                        <div class="bg-white/20 p-3 rounded-xl text-2xl">
                            &#128205;
                        </div>

                        <div>

                            <h2 class="text-xl font-black uppercase tracking-tight">
                                Barangay {{ $barangayName }}
                            </h2>

                            @if($currentAdministration)

                                <p class="text-xs opacity-80 font-medium">
                                    Current Administration:
                                    {{ $currentAdministration->start_year }}
                                    -
                                    {{ $currentAdministration->end_year }}
                                </p>

                            @else

                                <p class="text-xs opacity-80 font-medium">
                                    No active administration term
                                </p>

                            @endif
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">

                        <span class="bg-white/10 px-4 py-2 rounded-full text-[10px] font-bold border border-white/20 uppercase tracking-widest">
                            {{ $councilMembers->count() }}
                            Total Members
                        </span>

                        <span class="bg-white/10 px-4 py-2 rounded-full text-[10px] font-bold border border-white/20 uppercase tracking-widest">
                            {{ $councilors->count() }}
                            Councilors
                        </span>
                    </div>
                </div>

                @if(!$currentAdministration)

                    <div class="rounded-2xl border border-yellow-200 bg-yellow-50 p-5 text-sm text-yellow-700 mb-6">
                        No active administration term was found. Current leadership records cannot be displayed yet.
                    </div>

                @endif

                <!-- Executive Officers -->
                <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 mb-6">

                    <div class="flex items-center justify-between mb-5 border-b border-gray-100 pb-4">

                        <div>

                            <div class="flex items-center gap-2">

                                <span class="text-red-500 font-bold">
                                    &#128737;
                                </span>

                                <h3 class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em]">
                                    Executive Officers
                                </h3>
                            </div>

                            <p class="text-[9px] text-gray-400 mt-1">
                                Chairman, Secretary and Treasurer
                            </p>
                        </div>

                        <span class="rounded-full bg-gray-100 px-3 py-1 text-[9px] font-black text-gray-500">
                            {{ $executives->count() }}/3 Assigned
                        </span>
                    </div>

                    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">

                        @foreach([
                            [
                                'key'=>'chairman',
                                'member'=>$chairman,
                                'label'=>'SK Chairman',
                                'avatar'=>'bg-green-600',
                                'badge'=>'bg-green-50 text-green-700',
                                'border'=>'border-green-100'
                            ],
                            [
                                'key'=>'secretary',
                                'member'=>$secretary,
                                'label'=>'SK Secretary',
                                'avatar'=>'bg-blue-600',
                                'badge'=>'bg-blue-50 text-blue-700',
                                'border'=>'border-blue-100'
                            ],
                            [
                                'key'=>'treasurer',
                                'member'=>$treasurer,
                                'label'=>'SK Treasurer',
                                'avatar'=>'bg-yellow-500',
                                'badge'=>'bg-yellow-50 text-yellow-700',
                                'border'=>'border-yellow-100'
                            ]
                        ] as $position)

                            @php
                                $member=$position['member'];

                                $searchText=$member
                                    ? strtolower(
                                        ($member['name'] ?? '').' '.
                                        ($member['position'] ?? '').' '.
                                        ($member['email'] ?? '').' '.
                                        ($member['phone'] ?? '').' '.
                                        $barangayName
                                    )
                                    : strtolower(
                                        $position['label'].' not assigned '.$barangayName
                                    );

                                $pending=$member &&
                                    ($member['assignment_status'] ?? '') === 'pending';
                            @endphp

                            @if($member)

                                <div class="leadership-card rounded-2xl border {{ $position['border'] }} bg-white p-5 hover:shadow-sm transition"
                                    data-search="{{ $searchText }}">

                                    <div class="flex items-start gap-4">

                                        <div class="w-12 h-12 rounded-xl {{ $position['avatar'] }} text-white flex items-center justify-center font-black shrink-0">
                                            {{ strtoupper(substr($member['name'] ?: 'NA',0,2)) }}
                                        </div>

                                        <div class="min-w-0 flex-1">

                                            <div class="flex flex-wrap items-center gap-2">

                                                <h4 class="text-sm font-black text-gray-800 uppercase">
                                                    {{ $member['name'] }}
                                                </h4>

                                                <span class="rounded-full px-2.5 py-1 text-[8px] font-black uppercase {{ $position['badge'] }}">
                                                    {{ $position['label'] }}
                                                </span>
                                            </div>

                                            <div class="mt-2">

                                                @if($pending)

                                                    <span class="inline-flex rounded-full bg-yellow-100 px-2.5 py-1 text-[8px] font-black uppercase text-yellow-700">
                                                        Pending Setup
                                                    </span>

                                                @elseif(!empty($member['account_status']) && $member['account_status'] !== 'active')

                                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-[8px] font-black uppercase text-gray-600">
                                                        {{ $member['account_status'] }}
                                                    </span>

                                                @else

                                                    <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-[8px] font-black uppercase text-green-700">
                                                        Current
                                                    </span>

                                                @endif
                                            </div>

                                            <div class="mt-3 space-y-1.5 text-[10px] text-gray-500">

                                                <p class="truncate">
                                                    &#128231;
                                                    {{ $member['email'] ?: 'No email provided' }}
                                                </p>

                                                <p>
                                                    &#128222;
                                                    {{ $member['phone'] ?: 'No phone provided' }}
                                                </p>

                                                @if($currentAdministration)

                                                    <p>
                                                        &#128197;
                                                        Term:
                                                        {{ $currentAdministration->start_year }}
                                                        -
                                                        {{ $currentAdministration->end_year }}
                                                    </p>

                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            @else

                                <div class="leadership-card rounded-2xl border border-dashed border-gray-200 bg-gray-50/50 p-5"
                                    data-search="{{ $searchText }}">

                                    <div class="flex items-start gap-4">

                                        <div class="w-12 h-12 rounded-xl bg-gray-200 text-gray-400 flex items-center justify-center font-black shrink-0">
                                            --
                                        </div>

                                        <div>

                                            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[8px] font-black uppercase text-gray-500">
                                                {{ $position['label'] }}
                                            </span>

                                            <h4 class="text-sm font-black text-gray-500 mt-3">
                                                Not yet assigned
                                            </h4>

                                            <p class="text-[10px] text-gray-400 mt-1">
                                                No current {{ $position['label'] }} assignment was found for this administration.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                            @endif

                        @endforeach
                    </div>
                </div>

                <!-- SK Councilors -->
                <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6">

                    <div class="flex items-center justify-between mb-5 border-b border-gray-100 pb-4">

                        <div>

                            <div class="flex items-center gap-2">

                                <span class="text-purple-500 font-bold">
                                    &#127775;
                                </span>

                                <h3 class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em]">
                                    SK Councilors
                                </h3>
                            </div>

                            <p class="text-[9px] text-gray-400 mt-1">
                                Current council members for Barangay {{ $barangayName }}
                            </p>
                        </div>

                        <span class="rounded-full bg-purple-50 px-3 py-1 text-[9px] font-black text-purple-600">
                            {{ $councilors->count() }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">

                        @forelse($councilors as $member)

                            @php
                                $searchText=strtolower(
                                    ($member['name'] ?? '').' '.
                                    ($member['position'] ?? '').' '.
                                    ($member['email'] ?? '').' '.
                                    ($member['phone'] ?? '').' '.
                                    $barangayName
                                );
                            @endphp

                            <div class="leadership-card rounded-2xl border border-purple-100 bg-white p-4 hover:shadow-sm transition"
                                data-search="{{ $searchText }}">

                                <div class="flex items-start gap-3">

                                    <div class="w-11 h-11 rounded-xl bg-purple-600 flex items-center justify-center text-white font-black text-xs shrink-0">
                                        {{ strtoupper(substr($member['name'] ?: 'NA',0,2)) }}
                                    </div>

                                    <div class="min-w-0 flex-1">

                                        <h4 class="text-xs font-black text-gray-800 uppercase truncate">
                                            {{ $member['name'] }}
                                        </h4>

                                        <div class="mt-1">

                                            <span class="rounded-full bg-purple-50 px-2 py-1 text-[8px] font-black uppercase text-purple-700">
                                                SK Councilor
                                            </span>
                                        </div>

                                        <div class="mt-3 space-y-1 text-[9px] text-gray-400">

                                            <p class="truncate">
                                                &#128231;
                                                {{ $member['email'] ?: 'No email provided' }}
                                            </p>

                                            <p>
                                                &#128222;
                                                {{ $member['phone'] ?: 'No phone provided' }}
                                            </p>

                                            @if($currentAdministration)

                                                <p>
                                                    &#128197;
                                                    {{ $currentAdministration->start_year }}
                                                    -
                                                    {{ $currentAdministration->end_year }}
                                                </p>

                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                        @empty

                            <div class="leadership-card md:col-span-2 xl:col-span-3 rounded-2xl border border-dashed border-gray-200 bg-gray-50/50 p-6"
                                data-search="councilors not assigned {{ strtolower($barangayName) }}">

                                <p class="text-sm font-bold text-gray-500">
                                    No SK Councilors assigned yet.
                                </p>

                                <p class="text-[10px] text-gray-400 mt-1">
                                    Councilors added by the SK Chairman will automatically appear here.
                                </p>
                            </div>

                        @endforelse
                    </div>
                </div>

            @else

                <!-- History Filter -->
                <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 mb-6">

                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400">
                                Leadership History
                            </p>

                            <h2 class="text-xl font-black text-gray-800 mt-1">
                                Previous SK Administrations
                            </h2>

                            <p class="text-xs text-gray-500 mt-1">
                                View completed leadership records for Barangay {{ $barangayName }}.
                            </p>
                        </div>

                        <form id="historyTermForm"
                            method="GET"
                            action="{{ route('sk_secretary.leadership') }}"
                            class="w-full lg:w-72">

                            <input type="hidden" name="tab" value="history">

                            <label class="block mb-1.5 text-[9px] font-black uppercase tracking-widest text-gray-400">
                                Administration
                            </label>

                            <select id="historyTermSelect"
                                name="history_term"
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-xs font-bold text-gray-700 outline-none focus:ring-2 focus:ring-red-200">

                                @forelse($historyTerms as $term)

                                    <option value="{{ $term->term_id }}"
                                        {{ (int)$selectedHistoryTermId === (int)$term->term_id ? 'selected' : '' }}>

                                        {{ $term->start_year }} - {{ $term->end_year }}
                                    </option>

                                @empty

                                    <option value="">
                                        No completed administrations
                                    </option>

                                @endforelse
                            </select>
                        </form>
                    </div>
                </div>

                @if($historyTerms->isEmpty())

                    <div class="rounded-3xl border border-dashed border-gray-200 bg-white p-10 text-center">

                        <div class="text-4xl mb-3">
                            &#128220;
                        </div>

                        <h3 class="text-lg font-black text-gray-700">
                            No Leadership History Yet
                        </h3>

                        <p class="text-xs text-gray-400 mt-2">
                            Completed administration records for Barangay {{ $barangayName }} will appear here.
                        </p>
                    </div>

                @elseif(!$historyAdministration)

                    <div class="rounded-3xl border border-yellow-200 bg-yellow-50 p-8 text-center">

                        <h3 class="text-sm font-black text-yellow-700">
                            Administration Not Available
                        </h3>

                        <p class="text-xs text-yellow-600 mt-2">
                            The selected administration could not be found.
                        </p>
                    </div>

                @else

                    <!-- Selected Historical Administration -->
                    <div class="bg-gray-800 rounded-2xl p-6 text-white mb-6 shadow-md flex flex-col gap-4 md:flex-row md:justify-between md:items-center">

                        <div class="flex items-center gap-4">

                            <div class="bg-white/10 p-3 rounded-xl text-2xl">
                                &#128220;
                            </div>

                            <div>

                                <p class="text-[9px] font-black uppercase tracking-[0.18em] text-gray-300">
                                    Completed Administration
                                </p>

                                <h2 class="text-xl font-black uppercase tracking-tight mt-1">
                                    {{ $historyAdministration->start_year }}
                                    -
                                    {{ $historyAdministration->end_year }}
                                </h2>

                                <p class="text-xs text-gray-300 mt-1">
                                    Barangay {{ $barangayName }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">

                            <span class="bg-white/10 px-4 py-2 rounded-full text-[10px] font-bold border border-white/10 uppercase tracking-widest">
                                {{ $historyMembers->count() }}
                                Total Members
                            </span>

                            <span class="bg-white/10 px-4 py-2 rounded-full text-[10px] font-bold border border-white/10 uppercase tracking-widest">
                                {{ $historyCouncilors->count() }}
                                Councilors
                            </span>
                        </div>
                    </div>

                    <!-- Historical Executives -->
                    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 mb-6">

                        <div class="flex items-center justify-between mb-5 border-b border-gray-100 pb-4">

                            <div>

                                <div class="flex items-center gap-2">

                                    <span class="text-gray-500 font-bold">
                                        &#128737;
                                    </span>

                                    <h3 class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em]">
                                        Executive Officers
                                    </h3>
                                </div>

                                <p class="text-[9px] text-gray-400 mt-1">
                                    Chairman, Secretary and Treasurer from this administration
                                </p>
                            </div>

                            <span class="rounded-full bg-gray-100 px-3 py-1 text-[9px] font-black text-gray-500">
                                {{ $historyExecutives->count() }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">

                            @forelse($historyExecutives as $member)

                                @php
                                    $style=match($member['position']){
                                        'SK Chairman'=>[
                                            'avatar'=>'bg-green-600',
                                            'badge'=>'bg-green-50 text-green-700',
                                            'border'=>'border-green-100',
                                        ],
                                        'SK Secretary'=>[
                                            'avatar'=>'bg-blue-600',
                                            'badge'=>'bg-blue-50 text-blue-700',
                                            'border'=>'border-blue-100',
                                        ],
                                        'SK Treasurer'=>[
                                            'avatar'=>'bg-yellow-500',
                                            'badge'=>'bg-yellow-50 text-yellow-700',
                                            'border'=>'border-yellow-100',
                                        ],
                                        default=>[
                                            'avatar'=>'bg-gray-600',
                                            'badge'=>'bg-gray-100 text-gray-600',
                                            'border'=>'border-gray-200',
                                        ],
                                    };

                                    $searchText=strtolower(
                                        ($member['name'] ?? '').' '.
                                        ($member['position'] ?? '').' '.
                                        ($member['email'] ?? '').' '.
                                        ($member['phone'] ?? '').' '.
                                        ($member['term'] ?? '').' '.
                                        $barangayName
                                    );

                                    $completedLabel=!empty($member['completed_at'])
                                        ? \Carbon\Carbon::parse($member['completed_at'])->format('M j, Y')
                                        : 'Administration completed';
                                @endphp

                                <div class="leadership-card rounded-2xl border {{ $style['border'] }} bg-white p-5 hover:shadow-sm transition"
                                    data-search="{{ $searchText }}">

                                    <div class="flex items-start gap-4">

                                        <div class="w-12 h-12 rounded-xl {{ $style['avatar'] }} text-white flex items-center justify-center font-black shrink-0">
                                            {{ strtoupper(substr($member['name'] ?: 'NA',0,2)) }}
                                        </div>

                                        <div class="min-w-0 flex-1">

                                            <div class="flex flex-wrap items-center gap-2">

                                                <h4 class="text-sm font-black text-gray-800 uppercase">
                                                    {{ $member['name'] }}
                                                </h4>

                                                <span class="rounded-full px-2.5 py-1 text-[8px] font-black uppercase {{ $style['badge'] }}">
                                                    {{ $member['position'] }}
                                                </span>
                                            </div>

                                            <div class="mt-2">

                                                <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-[8px] font-black uppercase text-gray-500">
                                                    Completed
                                                </span>
                                            </div>

                                            <div class="mt-3 space-y-1.5 text-[10px] text-gray-500">

                                                <p class="truncate">
                                                    &#128231;
                                                    {{ $member['email'] ?: 'No email provided' }}
                                                </p>

                                                <p>
                                                    &#128222;
                                                    {{ $member['phone'] ?: 'No phone provided' }}
                                                </p>

                                                <p>
                                                    &#128197;
                                                    Term: {{ $member['term'] }}
                                                </p>

                                                <p>
                                                    &#10003;
                                                    Service ended: {{ $completedLabel }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            @empty

                                <div class="xl:col-span-3 rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-8 text-center">

                                    <p class="text-sm font-bold text-gray-500">
                                        No executive officers found.
                                    </p>

                                    <p class="text-xs text-gray-400 mt-1">
                                        No historical Chairman, Secretary, or Treasurer records were found for this administration.
                                    </p>
                                </div>

                            @endforelse
                        </div>
                    </div>

                    <!-- Historical Councilors -->
                    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6">

                        <div class="flex items-center justify-between mb-5 border-b border-gray-100 pb-4">

                            <div>

                                <div class="flex items-center gap-2">

                                    <span class="text-purple-500 font-bold">
                                        &#127775;
                                    </span>

                                    <h3 class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em]">
                                        SK Councilors
                                    </h3>
                                </div>

                                <p class="text-[9px] text-gray-400 mt-1">
                                    Council members recorded for this completed administration
                                </p>
                            </div>

                            <span class="rounded-full bg-purple-50 px-3 py-1 text-[9px] font-black text-purple-600">
                                {{ $historyCouncilors->count() }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">

                            @forelse($historyCouncilors as $member)

                                @php
                                    $searchText=strtolower(
                                        ($member['name'] ?? '').' '.
                                        ($member['position'] ?? '').' '.
                                        ($member['email'] ?? '').' '.
                                        ($member['phone'] ?? '').' '.
                                        ($member['term'] ?? '').' '.
                                        $barangayName
                                    );

                                    $completedLabel=!empty($member['completed_at'])
                                        ? \Carbon\Carbon::parse($member['completed_at'])->format('M j, Y')
                                        : 'Administration completed';
                                @endphp

                                <div class="leadership-card rounded-2xl border border-purple-100 bg-white p-4 hover:shadow-sm transition"
                                    data-search="{{ $searchText }}">

                                    <div class="flex items-start gap-3">

                                        <div class="w-11 h-11 rounded-xl bg-purple-600 flex items-center justify-center text-white font-black text-xs shrink-0">
                                            {{ strtoupper(substr($member['name'] ?: 'NA',0,2)) }}
                                        </div>

                                        <div class="min-w-0 flex-1">

                                            <h4 class="text-xs font-black text-gray-800 uppercase truncate">
                                                {{ $member['name'] }}
                                            </h4>

                                            <div class="mt-1">

                                                <span class="rounded-full bg-purple-50 px-2 py-1 text-[8px] font-black uppercase text-purple-700">
                                                    SK Councilor
                                                </span>
                                            </div>

                                            <div class="mt-3 space-y-1 text-[9px] text-gray-400">

                                                <p class="truncate">
                                                    &#128231;
                                                    {{ $member['email'] ?: 'No email provided' }}
                                                </p>

                                                <p>
                                                    &#128222;
                                                    {{ $member['phone'] ?: 'No phone provided' }}
                                                </p>

                                                <p>
                                                    &#128197;
                                                    {{ $member['term'] }}
                                                </p>

                                                <p>
                                                    &#10003;
                                                    Service ended: {{ $completedLabel }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            @empty

                                <div class="md:col-span-2 xl:col-span-3 rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-8 text-center">

                                    <p class="text-sm font-bold text-gray-500">
                                        No SK Councilors found.
                                    </p>

                                    <p class="text-xs text-gray-400 mt-1">
                                        No historical Councilor records were found for this administration.
                                    </p>
                                </div>

                            @endforelse
                        </div>
                    </div>

                @endif

            @endif

            <!-- No Search Result -->
            <div id="leadershipNoResults"
                class="hidden mt-6 rounded-2xl border border-gray-200 bg-white p-8 text-center">

                <div class="text-3xl">
                    &#128269;
                </div>

                <p class="mt-2 text-sm font-bold text-gray-600">
                    No leadership record matched your search.
                </p>

                <p class="mt-1 text-xs text-gray-400">
                    Search by official name, position, email, phone or administration term.
                </p>
            </div>
        </main>
    </div>
</div>
@endsection

@push('scripts')

@include('sk_secretary.partials.dropdown-scripts')

<script>
document.addEventListener('DOMContentLoaded',()=>{

    const searchInput=document.getElementById('leadershipSearch');
    const noResults=document.getElementById('leadershipNoResults');
    const historyTermSelect=document.getElementById('historyTermSelect');
    const historyTermForm=document.getElementById('historyTermForm');

    /*
    |--------------------------------------------------------------------------
    | HISTORY TERM
    |--------------------------------------------------------------------------
    */
    if(historyTermSelect && historyTermForm){
        historyTermSelect.addEventListener('change',()=>{
            historyTermForm.submit();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | SEARCH
    |--------------------------------------------------------------------------
    */
    if(searchInput){

        searchInput.addEventListener('input',()=>{

            const query=searchInput.value
                .trim()
                .toLowerCase();

            const cards=document.querySelectorAll('.leadership-card');

            let visible=0;

            cards.forEach((card)=>{

                const searchText=(card.dataset.search || '')
                    .toLowerCase();

                const matches=
                    query==='' ||
                    searchText.includes(query);

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
                    query==='' || visible>0
                );
            }
        });
    }
});
</script>
@endpush