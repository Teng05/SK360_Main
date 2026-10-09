{{-- File guide: Blade view template for resources/views/sk_chairman/leadership.blade.php. --}}
@extends('layouts.app')

@section('title','SK 360° | Leadership')

@section('page_css')
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('content')

<!-- BULK COUNCILOR VALUES -->
@php
    $oldCouncilors=old('councilors',[
        [
            'name'=>'',
            'email'=>'',
            'phone'=>'',
        ]
    ]);

    $bulkIndexes=array_map('intval',array_keys($oldCouncilors));
    $bulkNextIndex=empty($bulkIndexes) ? 0 : max($bulkIndexes)+1;
@endphp

<!-- ADD COUNCILORS MODAL -->
<div id="bulkCouncilorModal" class="hidden fixed inset-0 bg-black/50 z-[100] flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl w-full max-w-5xl max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="bg-red-600 p-6 text-white flex justify-between items-center">
            <div>
                <h2 class="text-xl font-black uppercase tracking-tighter">Add Councilors</h2>
                <p class="text-[10px] opacity-80 uppercase font-bold">Add multiple SK Councilors at once.</p>
            </div>

            <button type="button" onclick="toggleModal('bulkCouncilorModal')" class="text-2xl">&times;</button>
        </div>

        <form method="POST" action="{{ route('sk_chairman.leadership.bulk-councilors') }}" class="p-6">
            @csrf

            @if($errors->bulkCouncilors->any())
                <div class="mb-4 rounded-xl bg-red-50 border border-red-200 p-3 text-xs text-red-600">
                    <p class="font-black mb-1">Add Councilors Failed</p>

                    @foreach($errors->bulkCouncilors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="mb-4 rounded-xl bg-green-50 border border-green-100 px-4 py-3">
                <p class="text-[10px] font-black text-green-600 uppercase">Administration Term</p>

                @if($currentAdministration)
                    <p class="mt-1 text-sm font-black text-green-700">
                        {{ $currentAdministration->start_year }} - {{ $currentAdministration->end_year }}
                    </p>
                    <p class="mt-1 text-[9px] text-green-600">
                        All Councilors below will be assigned to this administration.
                    </p>
                @else
                    <p class="mt-1 text-xs font-bold text-red-600">No active administration term</p>
                @endif
            </div>

            <div id="bulkCouncilorRows" class="space-y-4">
                @foreach($oldCouncilors as $index=>$councilor)
                    <div class="bulk-councilor-row rounded-2xl border bg-gray-50 p-4">
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="bulk-councilor-title text-xs font-black uppercase text-gray-600">
                                Councilor #{{ $loop->iteration }}
                            </h3>

                            <button type="button"
                                class="removeCouncilorRow {{ $loop->first ? 'hidden' : '' }} text-xs font-bold text-red-500">
                                Remove
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <input type="text"
                                name="councilors[{{ $index }}][name]"
                                value="{{ $councilor['name'] ?? '' }}"
                                placeholder="Full Name"
                                required
                                class="rounded-xl border px-3 py-2 text-sm">

                            <input type="email"
                                name="councilors[{{ $index }}][email]"
                                value="{{ $councilor['email'] ?? '' }}"
                                placeholder="Email"
                                class="rounded-xl border px-3 py-2 text-sm">

                            <input type="text"
                                name="councilors[{{ $index }}][phone]"
                                value="{{ $councilor['phone'] ?? '' }}"
                                placeholder="Phone"
                                class="rounded-xl border px-3 py-2 text-sm">
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-5 flex justify-between gap-3">
                <button id="addCouncilorRow" type="button"
                    class="rounded-xl border px-4 py-3 text-xs font-black text-gray-500 hover:bg-gray-50">
                    + Add Another Councilor
                </button>

                <button type="submit"
                    class="rounded-xl bg-red-600 px-6 py-3 text-xs font-black uppercase text-white">
                    Save All Councilors
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ADD SECRETARY MODAL -->
<div id="addSecretaryModal" class="hidden fixed inset-0 bg-black/50 z-[100] flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl w-full max-w-md overflow-hidden shadow-2xl">
        <div class="bg-blue-600 p-6 text-white">
            <h2 class="text-xl font-black uppercase tracking-tighter">Add SK Secretary</h2>
            <p class="text-[10px] opacity-80 uppercase font-bold">
                Create the secretary account for Barangay {{ $barangayName }}.
            </p>
        </div>

        <form method="POST" action="{{ route('sk_chairman.leadership.secretary.store') }}" class="p-6 space-y-4">
            @csrf

            @if($errors->secretaryAdd->any())
                <div class="rounded-xl bg-red-50 border border-red-200 p-3 text-xs text-red-600">
                    @foreach($errors->secretaryAdd->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase">First Name</label>
                    <input type="text"
                        name="secretary_first_name"
                        value="{{ old('secretary_first_name') }}"
                        required
                        class="w-full border-b-2 border-gray-100 focus:border-blue-500 outline-none py-2 text-sm font-bold">
                </div>

                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase">Last Name</label>
                    <input type="text"
                        name="secretary_last_name"
                        value="{{ old('secretary_last_name') }}"
                        required
                        class="w-full border-b-2 border-gray-100 focus:border-blue-500 outline-none py-2 text-sm font-bold">
                </div>
            </div>

            <div>
                <label class="text-[10px] font-black text-gray-400 uppercase">Email</label>
                <input type="email"
                    name="secretary_email"
                    value="{{ old('secretary_email') }}"
                    required
                    class="w-full border-b-2 border-gray-100 focus:border-blue-500 outline-none py-2 text-sm font-bold">
            </div>

            <div>
                <label class="text-[10px] font-black text-gray-400 uppercase">Phone</label>
                <input type="text"
                    name="secretary_phone"
                    value="{{ old('secretary_phone') }}"
                    class="w-full border-b-2 border-gray-100 focus:border-blue-500 outline-none py-2 text-sm font-bold">
            </div>

            <div class="rounded-xl bg-green-50 border border-green-100 px-4 py-3">
                <p class="text-[10px] font-black text-green-600 uppercase">Administration Term</p>

                @if($currentAdministration)
                    <p class="mt-1 text-sm font-black text-green-700">
                        {{ $currentAdministration->start_year }} - {{ $currentAdministration->end_year }}
                    </p>
                @else
                    <p class="mt-1 text-xs font-bold text-red-600">No active administration term</p>
                @endif
            </div>

            <div class="rounded-xl bg-blue-50 px-4 py-3 text-xs text-blue-700">
                The secretary will receive an email to set their password and activate the account.
            </div>

            <div class="flex gap-3 pt-3">
                <button type="button" onclick="toggleModal('addSecretaryModal')"
                    class="flex-1 py-3 text-xs font-black uppercase text-gray-400">
                    Cancel
                </button>

                <button type="submit"
                    class="flex-1 bg-blue-600 hover:bg-blue-700 py-3 rounded-xl text-xs font-black uppercase text-white">
                    Create Account
                </button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT SECRETARY MODAL -->
@if($secretary)
<div id="editSecretaryModal" class="hidden fixed inset-0 bg-black/50 z-[100] flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl w-full max-w-md overflow-hidden shadow-2xl">
        <div class="bg-blue-600 p-6 text-white">
            <h2 class="text-xl font-black uppercase tracking-tighter">Edit SK Secretary</h2>
            <p class="text-[10px] opacity-80 uppercase font-bold">Update secretary account details.</p>
        </div>

        <form method="POST"
            action="{{ route('sk_chairman.leadership.secretary.update',$secretary['user_id']) }}"
            class="p-6 space-y-4">
            @csrf
            @method('PUT')

            @if($errors->secretaryEdit->any())
                <div class="rounded-xl bg-red-50 border border-red-200 p-3 text-xs text-red-600">
                    @foreach($errors->secretaryEdit->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase">First Name</label>
                    <input type="text"
                        name="edit_secretary_first_name"
                        value="{{ old('edit_secretary_first_name',$secretary['first_name']) }}"
                        required
                        class="w-full border-b-2 border-gray-100 focus:border-blue-500 outline-none py-2 text-sm font-bold">
                </div>

                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase">Last Name</label>
                    <input type="text"
                        name="edit_secretary_last_name"
                        value="{{ old('edit_secretary_last_name',$secretary['last_name']) }}"
                        required
                        class="w-full border-b-2 border-gray-100 focus:border-blue-500 outline-none py-2 text-sm font-bold">
                </div>
            </div>

            <div>
                <label class="text-[10px] font-black text-gray-400 uppercase">Email</label>
                <input type="email"
                    name="edit_secretary_email"
                    value="{{ old('edit_secretary_email',$secretary['email']) }}"
                    required
                    class="w-full border-b-2 border-gray-100 focus:border-blue-500 outline-none py-2 text-sm font-bold">
            </div>

            <div>
                <label class="text-[10px] font-black text-gray-400 uppercase">Phone</label>
                <input type="text"
                    name="edit_secretary_phone"
                    value="{{ old('edit_secretary_phone',$secretary['phone']) }}"
                    class="w-full border-b-2 border-gray-100 focus:border-blue-500 outline-none py-2 text-sm font-bold">
            </div>

            <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3">
                <p class="text-[10px] font-black text-gray-400 uppercase">Administration Term</p>

                <p class="mt-1 text-sm font-black text-gray-700">
                    {{ $secretary['term'] ?? 'N/A' }}
                </p>

                <p class="mt-1 text-[9px] text-gray-400">
                    Administration term cannot be changed from Edit Details.
                </p>
            </div>

            <div class="flex gap-3 pt-3">
                <button type="button" onclick="toggleModal('editSecretaryModal')"
                    class="flex-1 py-3 text-xs font-black uppercase text-gray-400">
                    Cancel
                </button>

                <button type="submit"
                    class="flex-1 bg-blue-600 hover:bg-blue-700 py-3 rounded-xl text-xs font-black uppercase text-white">
                    Update
                </button>
            </div>
        </form>
    </div>
</div>
@endif

<!-- ADD TREASURER MODAL -->
<div id="addTreasurerModal" class="hidden fixed inset-0 bg-black/50 z-[100] flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl w-full max-w-md overflow-hidden shadow-2xl">
        <div class="bg-yellow-500 p-6 text-white">
            <h2 class="text-xl font-black uppercase tracking-tighter">Add SK Treasurer</h2>
            <p class="text-[10px] opacity-80 uppercase font-bold">
                Treasurer information only. No SK360 account will be created.
            </p>
        </div>

        <form method="POST" action="{{ route('sk_chairman.leadership.treasurer.store') }}" class="p-6 space-y-4">
            @csrf

            @if($errors->treasurerAdd->any())
                <div class="rounded-xl bg-red-50 border border-red-200 p-3 text-xs text-red-600">
                    @foreach($errors->treasurerAdd->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div>
                <label class="text-[10px] font-black text-gray-400 uppercase">Full Name</label>
                <input type="text"
                    name="treasurer_name"
                    value="{{ old('treasurer_name') }}"
                    required
                    class="w-full border-b-2 border-gray-100 focus:border-yellow-500 outline-none py-2 text-sm font-bold">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase">Email</label>
                    <input type="email"
                        name="treasurer_email"
                        value="{{ old('treasurer_email') }}"
                        class="w-full border-b-2 border-gray-100 focus:border-yellow-500 outline-none py-2 text-sm font-bold">
                </div>

                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase">Phone</label>
                    <input type="text"
                        name="treasurer_phone"
                        value="{{ old('treasurer_phone') }}"
                        class="w-full border-b-2 border-gray-100 focus:border-yellow-500 outline-none py-2 text-sm font-bold">
                </div>
            </div>

            <div class="rounded-xl bg-green-50 border border-green-100 px-4 py-3">
                <p class="text-[10px] font-black text-green-600 uppercase">Administration Term</p>

                @if($currentAdministration)
                    <p class="mt-1 text-sm font-black text-green-700">
                        {{ $currentAdministration->start_year }} - {{ $currentAdministration->end_year }}
                    </p>
                @else
                    <p class="mt-1 text-xs font-bold text-red-600">No active administration term</p>
                @endif
            </div>

            <div class="flex gap-3 pt-3">
                <button type="button" onclick="toggleModal('addTreasurerModal')"
                    class="flex-1 py-3 text-xs font-black uppercase text-gray-400">
                    Cancel
                </button>

                <button type="submit"
                    class="flex-1 bg-yellow-500 hover:bg-yellow-600 py-3 rounded-xl text-xs font-black uppercase text-white">
                    Save Treasurer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT TREASURER MODAL -->
@if($treasurer)
<div id="editTreasurerModal" class="hidden fixed inset-0 bg-black/50 z-[100] flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl w-full max-w-md overflow-hidden shadow-2xl">
        <div class="bg-yellow-500 p-6 text-white">
            <h2 class="text-xl font-black uppercase tracking-tighter">Edit SK Treasurer</h2>
            <p class="text-[10px] opacity-80 uppercase font-bold">Update treasurer information.</p>
        </div>

        <form method="POST"
            action="{{ route('sk_chairman.leadership.treasurer.update',$treasurer['council_id']) }}"
            class="p-6 space-y-4">
            @csrf
            @method('PUT')

            @if($errors->treasurerEdit->any())
                <div class="rounded-xl bg-red-50 border border-red-200 p-3 text-xs text-red-600">
                    @foreach($errors->treasurerEdit->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div>
                <label class="text-[10px] font-black text-gray-400 uppercase">Full Name</label>
                <input type="text"
                    name="edit_treasurer_name"
                    value="{{ old('edit_treasurer_name',$treasurer['name']) }}"
                    required
                    class="w-full border-b-2 border-gray-100 focus:border-yellow-500 outline-none py-2 text-sm font-bold">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase">Email</label>
                    <input type="email"
                        name="edit_treasurer_email"
                        value="{{ old('edit_treasurer_email',$treasurer['email']) }}"
                        class="w-full border-b-2 border-gray-100 focus:border-yellow-500 outline-none py-2 text-sm font-bold">
                </div>

                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase">Phone</label>
                    <input type="text"
                        name="edit_treasurer_phone"
                        value="{{ old('edit_treasurer_phone',$treasurer['phone']) }}"
                        class="w-full border-b-2 border-gray-100 focus:border-yellow-500 outline-none py-2 text-sm font-bold">
                </div>
            </div>

            <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3">
                <p class="text-[10px] font-black text-gray-400 uppercase">Administration Term</p>

                <p class="mt-1 text-sm font-black text-gray-700">
                    {{ $treasurer['term'] ?? 'N/A' }}
                </p>

                <p class="mt-1 text-[9px] text-gray-400">
                    Administration term cannot be changed from Edit Details.
                </p>
            </div>

            <div class="flex gap-3 pt-3">
                <button type="button" onclick="toggleModal('editTreasurerModal')"
                    class="flex-1 py-3 text-xs font-black uppercase text-gray-400">
                    Cancel
                </button>

                <button type="submit"
                    class="flex-1 bg-yellow-500 hover:bg-yellow-600 py-3 rounded-xl text-xs font-black uppercase text-white">
                    Update
                </button>
            </div>
        </form>
    </div>
</div>
@endif

<!-- EDIT COUNCILOR MODAL -->
<div id="editCouncilorModal" class="hidden fixed inset-0 bg-black/50 z-[100] flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl w-full max-w-md overflow-hidden shadow-2xl">
        <div class="bg-red-600 p-6 text-white">
            <h2 class="text-xl font-black uppercase tracking-tighter">Edit SK Councilor</h2>
            <p class="text-[10px] opacity-80 uppercase font-bold">Update councilor information.</p>
        </div>

        <form id="editCouncilorForm" method="POST" action="" class="p-6 space-y-4">
            @csrf
            @method('PUT')

            <input type="hidden"
                id="edit_councilor_id"
                name="edit_councilor_id"
                value="{{ old('edit_councilor_id') }}">

            @if($errors->councilorEdit->any())
                <div class="rounded-xl bg-red-50 border border-red-200 p-3 text-xs text-red-600">
                    @foreach($errors->councilorEdit->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div>
                <label class="text-[10px] font-black text-gray-400 uppercase">Full Name</label>
                <input type="text"
                    id="edit_councilor_name"
                    name="edit_councilor_name"
                    value="{{ old('edit_councilor_name') }}"
                    required
                    class="w-full border-b-2 border-gray-100 focus:border-red-500 outline-none py-2 text-sm font-bold">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase">Email</label>
                    <input type="email"
                        id="edit_councilor_email"
                        name="edit_councilor_email"
                        value="{{ old('edit_councilor_email') }}"
                        class="w-full border-b-2 border-gray-100 focus:border-red-500 outline-none py-2 text-sm font-bold">
                </div>

                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase">Phone</label>
                    <input type="text"
                        id="edit_councilor_phone"
                        name="edit_councilor_phone"
                        value="{{ old('edit_councilor_phone') }}"
                        class="w-full border-b-2 border-gray-100 focus:border-red-500 outline-none py-2 text-sm font-bold">
                </div>
            </div>

            <div class="flex gap-3 pt-3">
                <button type="button" onclick="toggleModal('editCouncilorModal')"
                    class="flex-1 py-3 text-xs font-black uppercase text-gray-400">
                    Cancel
                </button>

                <button type="submit"
                    class="flex-1 bg-red-600 hover:bg-red-700 py-3 rounded-xl text-xs font-black uppercase text-white">
                    Update
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MAIN PAGE -->
<div class="flex h-screen bg-gray-100 overflow-hidden">
    @include('partials.app.sidebar')

    <div class="flex-1 flex flex-col overflow-hidden min-w-0">
        @include('partials.app.topbar', ['search' => ['id' => 'leadershipSearch', 'placeholder' => 'Search officials...']])

        <main class="p-8 overflow-y-auto h-full bg-gray-50">

            @if(session('warning'))
                <div class="mb-5 rounded-xl border border-yellow-200 bg-yellow-50 p-3 text-xs text-yellow-700">
                    {{ session('warning') }}
                </div>
            @endif

            <!-- PAGE TITLE -->
            <div class="flex justify-between items-end mb-6">

                <div>
                    <span class="sk-eyebrow"><span class="sk-dot"></span>Leadership</span>
                    <h1 class="text-3xl font-black text-gray-800 uppercase tracking-tight">
                        Council Leadership
                    </h1>

                    <p class="text-gray-500 font-medium italic">
                        Official Directory for Barangay {{ $barangayName }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    @if(($activeTab ?? 'current')==='current')
                        <button type="button"
                            onclick="toggleModal('bulkCouncilorModal')"
                            class="bg-gray-800 hover:bg-gray-900 text-white px-5 py-3 rounded-2xl font-black uppercase text-[10px] tracking-widest shadow-lg transition-all">
                            <span class="inline-flex align-[-3px]">@include('partials.ui.icon', ['icon' => 'users', 'iconSize' => 16])</span> Add Councilors
                        </button>
                    @endif
                </div>
            </div>

            <div class="mb-6 flex gap-2 rounded-2xl border border-gray-100 bg-white p-2 shadow-sm">
                <a href="{{ route('sk_chairman.leadership',['tab'=>'current']) }}"
                    class="flex-1 rounded-xl px-4 py-3 text-center text-xs font-black uppercase transition {{ ($activeTab ?? 'current')==='current' ? 'bg-red-600 text-white' : 'text-gray-500 hover:bg-gray-50' }}">
                    <span class="inline-flex align-[-3px]">@include('partials.ui.icon',['icon'=>'users','iconSize'=>16])</span> Current Leadership
                </a>
                <a href="{{ route('sk_chairman.leadership',['tab'=>'history']) }}"
                    class="flex-1 rounded-xl px-4 py-3 text-center text-xs font-black uppercase transition {{ ($activeTab ?? 'current')==='history' ? 'bg-gray-800 text-white' : 'text-gray-500 hover:bg-gray-50' }}">
                    <span class="inline-flex align-[-3px]">@include('partials.ui.icon',['icon'=>'archive','iconSize'=>16])</span> Leadership History
                </a>
            </div>

            @if(($activeTab ?? 'current')==='current')

            <!-- BARANGAY CARD -->
            <div class="bg-red-600 rounded-2xl p-6 text-white mb-8 shadow-md flex justify-between items-center">

                <div class="flex items-center gap-4">

                    <div class="bg-white/20 p-3 rounded-xl text-2xl">
                        <span class="inline-flex align-[-3px]">@include('partials.ui.icon', ['icon' => 'map-pin', 'iconSize' => 18])</span>
                    </div>

                    <div>
                        <h2 class="text-xl font-black uppercase tracking-tight">
                            Barangay {{ $barangayName }}
                        </h2>

                        <p class="text-xs opacity-80 font-medium">
                            Current SK Administration
                        </p>

                        @if($currentAdministration)
                            <p class="mt-1 text-sm font-black">
                                {{ $currentAdministration->start_year }} - {{ $currentAdministration->end_year }}
                            </p>
                        @endif
                    </div>
                </div>

                <div class="text-right">

                    <span class="bg-white/10 px-4 py-2 rounded-full text-[10px] font-bold border border-white/20 uppercase tracking-widest">
                        {{ $councilMembers->count() }} Total Members
                    </span>
                </div>
            </div>

            <!-- EXECUTIVE OFFICERS -->
            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-8 mb-8">

                <div class="flex items-center justify-between gap-3 mb-8 border-b border-gray-50 pb-4">

                    <div class="flex items-center gap-2">

                        <span class="text-red-500 font-bold">
                            <span class="inline-flex align-middle">@include('partials.ui.icon', ['icon' => 'shield-check', 'iconSize' => 16])</span>
                        </span>

                        <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">
                            Executive Officers
                        </h3>
                    </div>

                    <div class="flex items-center gap-2">

                        @if($canAddSecretary)
                            <button type="button"
                                onclick="toggleModal('addSecretaryModal')"
                                class="rounded-xl bg-blue-600 hover:bg-blue-700 px-4 py-2 text-[9px] font-black uppercase text-white">

                                + Add Secretary
                            </button>
                        @endif

                        @if(!$treasurer)
                            <button type="button"
                                onclick="toggleModal('addTreasurerModal')"
                                class="rounded-xl bg-yellow-500 hover:bg-yellow-600 px-4 py-2 text-[9px] font-black uppercase text-white">

                                + Add Treasurer
                            </button>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4">

                    @foreach($executives as $member)

                        @php
                            $isSecretary=($member['position'] ?? '') === 'SK Secretary';
                            $isChairman=($member['position'] ?? '') === 'SK Chairman';
                            $isTreasurer=($member['position'] ?? '') === 'SK Treasurer';
                            $pending=$isSecretary && (int)($member['is_verified'] ?? 1) === 0;
                            $isSecretaryReappointment=$isSecretary && ($member['is_reappointment'] ?? false);

                            if($isChairman){
                                $officerBorder='border-red-100 bg-red-50/30';
                                $avatarStyle='bg-red-600 text-white';
                                $positionStyle='bg-red-600 text-white';
                                $officerIcon='shield-check';
                            }elseif($isSecretary){
                                $officerBorder='border-blue-100 bg-blue-50/30';
                                $avatarStyle='bg-blue-600 text-white';
                                $positionStyle='bg-blue-600 text-white';
                                $officerIcon='file-text';
                            }else{
                                $officerBorder='border-yellow-200 bg-yellow-50/40';
                                $avatarStyle='bg-yellow-500 text-white';
                                $positionStyle='bg-yellow-500 text-white';
                                $officerIcon='wallet';
                            }

                            $searchStatus=$pending ? 'pending' : ($member['status'] ?? '');
                        @endphp

                        <div
                            class="leadership-search-item executive-item flex items-center gap-6 p-5 rounded-2xl border {{ $officerBorder }} transition group relative"
                            data-search="{{ strtolower(
                                ($member['name'] ?? '').' '.
                                ($member['position'] ?? '').' '.
                                ($member['email'] ?? '').' '.
                                ($member['phone'] ?? '').' '.
                                ($member['term'] ?? '').' '.
                                $searchStatus
                            ) }}">

                            <div class="w-16 h-16 rounded-2xl {{ $avatarStyle }} flex items-center justify-center font-black text-xl border-4 border-white shadow-sm">
                                {{ strtoupper(substr($member['name'] ?? 'U',0,2)) }}
                            </div>

                            <div class="flex-1">

                                <div class="flex flex-wrap items-center gap-3">

                                    <h4 class="text-lg font-black text-gray-800 uppercase leading-none">
                                        {{ $member['name'] }}
                                    </h4>

                                    <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase {{ $positionStyle }}">
                                        @include('partials.ui.icon', ['icon' => $officerIcon, 'iconSize' => 16]) {{ $member['position'] }}
                                    </span>

                                    @if($isSecretary)

                                        @if($pending)

                                            <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase bg-yellow-100 text-yellow-700">
                                                Pending Setup
                                            </span>

                                            @if($isSecretaryReappointment)
                                                <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase bg-blue-100 text-blue-700">
                                                    Reappointment
                                                </span>
                                            @endif

                                        @elseif(($member['status'] ?? '') === 'active')

                                            <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase bg-green-100 text-green-600">
                                                Active
                                            </span>

                                        @else

                                            <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase bg-gray-200 text-gray-600">
                                                Inactive
                                            </span>

                                        @endif

                                    @elseif($isChairman)

                                        <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase bg-green-100 text-green-600">
                                            Active
                                        </span>

                                    @endif
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 mt-3 text-[11px] text-gray-500 font-medium gap-2">

                                    <div class="flex items-center gap-2">
                                        <span>
                                            <span class="inline-flex align-middle">@include('partials.ui.icon', ['icon' => 'mail', 'iconSize' => 16])</span>
                                        </span>

                                        {{ $member['email'] ?: 'No email provided' }}
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <span>
                                            <span class="inline-flex align-middle">@include('partials.ui.icon', ['icon' => 'phone', 'iconSize' => 16])</span>
                                        </span>

                                        {{ $member['phone'] ?: 'No phone provided' }}
                                    </div>

                                    <div class="flex items-center gap-2 uppercase tracking-tighter">
                                        <span>
                                            <span class="inline-flex align-middle">@include('partials.ui.icon', ['icon' => 'calendar-days', 'iconSize' => 16])</span>
                                        </span>

                                        {{ $member['term'] ?: 'N/A' }}
                                    </div>
                                </div>
                            </div>

                            <!-- SECRETARY ACTIONS -->
                            @if($isSecretary)

                                <div class="relative">

                                    <button type="button"
                                        class="secretary-menu-btn rounded-lg px-3 py-2 text-xl text-gray-400 hover:bg-white">
                                        &#8942;
                                    </button>

                                    <div class="secretary-menu hidden absolute right-0 top-10 z-40 w-48 rounded-xl border bg-white shadow-xl py-1">

                                        <button type="button"
                                            onclick="toggleModal('editSecretaryModal')"
                                            class="block w-full text-left px-4 py-2 text-xs hover:bg-gray-50">

                                            Edit Details
                                        </button>

                                        @if($pending)

                                            <form method="POST"
                                                action="{{ route('sk_chairman.leadership.secretary.resend',$member['user_id']) }}"
                                                onsubmit="return confirm('Send a new password setup link to this Secretary?');">

                                                @csrf

                                                <button type="submit"
                                                    class="block w-full text-left px-4 py-2 text-xs font-semibold text-blue-600 hover:bg-blue-50">

                                                    Resend Setup Link
                                                </button>
                                            </form>

                                            <div class="border-t my-1"></div>

                                            @if($isSecretaryReappointment)

                                                <form method="POST"
                                                    action="{{ route('sk_chairman.leadership.secretary.destroy',$member['user_id']) }}"
                                                    onsubmit="return confirm('Cancel this Secretary reappointment? Their historical service records will remain preserved.');">

                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="submit"
                                                        class="block w-full text-left px-4 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">

                                                        Cancel Reappointment
                                                    </button>
                                                </form>

                                            @else

                                                <form method="POST"
                                                    action="{{ route('sk_chairman.leadership.secretary.destroy',$member['user_id']) }}"
                                                    onsubmit="return confirm('Delete this pending Secretary account permanently?');">

                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="submit"
                                                        class="block w-full text-left px-4 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">

                                                        Delete Pending Account
                                                    </button>
                                                </form>

                                            @endif

                                        @else

                                            <form method="POST"
                                                action="{{ route('sk_chairman.leadership.secretary.toggle-status',$member['user_id']) }}">

                                                @csrf
                                                @method('PATCH')

                                                <button type="submit"
                                                    class="block w-full text-left px-4 py-2 text-xs hover:bg-gray-50">

                                                    {{ ($member['status'] ?? '') === 'active' ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>

                                        @endif
                                    </div>
                                </div>

                            @endif

                            <!-- TREASURER ACTIONS -->
                            @if($isTreasurer && !empty($member['council_id']))

                                <div class="relative">

                                    <button type="button"
                                        class="action-menu-btn rounded-lg px-3 py-2 text-xl text-gray-400 hover:bg-white">
                                        &#8942;
                                    </button>

                                    <div class="action-menu hidden absolute right-0 top-10 z-40 w-48 rounded-xl border bg-white shadow-xl py-1">

                                        <button type="button"
                                            onclick="toggleModal('editTreasurerModal')"
                                            class="block w-full text-left px-4 py-2 text-xs hover:bg-gray-50">

                                            Edit Details
                                        </button>

                                        <div class="border-t my-1"></div>

                                        <form method="POST"
                                            action="{{ route('sk_chairman.leadership.destroy',$member['council_id']) }}"
                                            onsubmit="return confirm('End this Treasurer\'s service for the current administration? Their historical record will be preserved.');">

                                            @csrf

                                            <button type="submit"
                                                class="block w-full text-left px-4 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">

                                                End Treasurer Service
                                            </button>
                                        </form>
                                    </div>
                                </div>

                            @endif
                        </div>

                    @endforeach
                </div>
            </div>

            <!-- COUNCILORS -->
            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-8">

                <div class="flex items-center gap-2 mb-8 border-b border-gray-50 pb-4">

                    <span class="text-yellow-500 font-bold">
                        <span class="inline-flex align-middle">@include('partials.ui.icon', ['icon' => 'award', 'iconSize' => 16])</span>
                    </span>

                    <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">
                        SK Councilors
                    </h3>
                </div>

                <div id="councilorGrid"
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                    @forelse($kagawads as $member)

                        <div
                            class="leadership-search-item councilor-item bg-gray-50/50 p-5 rounded-2xl border border-gray-100 flex items-center gap-4 hover:shadow-md hover:bg-white transition group relative"
                            data-search="{{ strtolower(
                                ($member['name'] ?? '').' '.
                                ($member['position'] ?? '').' '.
                                ($member['email'] ?? '').' '.
                                ($member['phone'] ?? '').' '.
                                ($member['term'] ?? '')
                            ) }}">

                            <div class="w-12 h-12 rounded-full bg-yellow-400 flex items-center justify-center text-white font-black shadow-sm border-2 border-white">
                                {{ strtoupper(substr($member['name'] ?? 'U',0,2)) }}
                            </div>

                            <div class="flex-1 min-w-0">

                                <h4 class="text-xs font-black text-gray-800 uppercase leading-none">
                                    {{ $member['name'] }}
                                </h4>

                                <p class="text-[9px] text-yellow-600 font-black uppercase mt-1">
                                    {{ $member['position'] }}
                                </p>

                                <p class="text-[10px] text-gray-400 mt-2 font-medium">
                                    <span class="inline-flex align-middle">@include('partials.ui.icon', ['icon' => 'phone', 'iconSize' => 16])</span> {{ $member['phone'] ?: 'No phone provided' }}
                                </p>

                                <p class="text-[10px] text-gray-400 mt-1 truncate">
                                    <span class="inline-flex align-middle">@include('partials.ui.icon', ['icon' => 'mail', 'iconSize' => 16])</span> {{ $member['email'] ?: 'No email provided' }}
                                </p>

                                <p class="text-[9px] text-gray-400 mt-1">
                                    <span class="inline-flex align-middle">@include('partials.ui.icon', ['icon' => 'calendar-days', 'iconSize' => 16])</span> {{ $member['term'] ?: 'N/A' }}
                                </p>
                            </div>

                            @if(!empty($member['council_id']))

                                <div class="relative">

                                    <button type="button"
                                        class="action-menu-btn rounded-lg px-2 py-1 text-xl text-gray-400 hover:bg-gray-100">
                                        &#8942;
                                    </button>

                                    <div class="action-menu hidden absolute right-0 top-9 z-40 w-48 rounded-xl border bg-white shadow-xl py-1">

                                        <button type="button"
                                            class="edit-councilor-btn block w-full text-left px-4 py-2 text-xs hover:bg-gray-50"
                                            data-id="{{ $member['council_id'] }}"
                                            data-name="{{ $member['name'] }}"
                                            data-email="{{ $member['email'] ?? '' }}"
                                            data-phone="{{ $member['phone'] ?? '' }}">

                                            Edit Details
                                        </button>

                                        <div class="border-t my-1"></div>

                                        <form method="POST"
                                            action="{{ route('sk_chairman.leadership.destroy',$member['council_id']) }}"
                                            onsubmit="return confirm('End this Councilor\'s service for the current administration? Their historical record will be preserved.');">

                                            @csrf

                                            <button type="submit"
                                                class="block w-full text-left px-4 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">

                                                End Councilor Service
                                            </button>
                                        </form>
                                    </div>
                                </div>

                            @endif
                        </div>

                    @empty

                        <p class="text-gray-400 italic">
                            No councilors found.
                        </p>

                    @endforelse
                </div>
            </div>

            <!-- SEARCH NO RESULTS -->
            <div id="leadershipNoResults"
                class="hidden py-10 text-center">

                <div class="text-3xl mb-2">
                    <span class="inline-flex align-middle">@include('partials.ui.icon', ['icon' => 'search', 'iconSize' => 16])</span>
                </div>

                <p class="text-sm font-bold text-gray-500">
                    No officials found.
                </p>

                <p class="text-xs text-gray-400 mt-1">
                    Try a different name, position, email, phone number, term, or status.
                </p>
            </div>

            @else

                @php
                    $historyAdministration=$historyTerms->firstWhere('term_id',$selectedHistoryTermId);
                    $historyExecutives=$historyRecords->whereIn('role',['sk_chairman','sk_secretary','sk_treasurer']);
                    $historyCouncilors=$historyRecords->where('role','sk_councilor');
                @endphp

                <!-- History Filter -->
                <div class="mb-6 rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400">Leadership History</p>
                            <h2 class="mt-1 text-xl font-black text-gray-800">Previous SK Administrations</h2>
                            <p class="mt-1 text-xs text-gray-500">View completed leadership records for Barangay {{ $barangayName }}.</p>
                        </div>
                        <form method="GET" action="{{ route('sk_chairman.leadership') }}" class="w-full lg:w-72">
                            <input type="hidden" name="tab" value="history">
                            <label for="historyTerm" class="mb-1.5 block text-[9px] font-black uppercase tracking-widest text-gray-400">Administration</label>
                            <select id="historyTerm" name="history_term" onchange="this.form.submit()"
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-xs font-bold text-gray-700 outline-none focus:ring-2 focus:ring-red-200">
                                @forelse($historyTerms as $term)
                                    <option value="{{ $term->term_id }}" @selected((int)$selectedHistoryTermId===(int)$term->term_id)>{{ $term->start_year }} - {{ $term->end_year }}</option>
                                @empty
                                    <option value="">No completed administrations</option>
                                @endforelse
                            </select>
                        </form>
                    </div>
                </div>

                @if($historyTerms->isEmpty())
                    <div class="rounded-3xl border border-dashed border-gray-200 bg-white p-10 text-center">
                        <div class="mb-3 text-4xl"><span class="inline-flex align-[-3px]">@include('partials.ui.icon',['icon'=>'archive','iconSize'=>16])</span></div>
                        <h3 class="text-lg font-black text-gray-700">No Leadership History Yet</h3>
                        <p class="mt-2 text-xs text-gray-400">Completed administration records for Barangay {{ $barangayName }} will appear here.</p>
                    </div>
                @elseif(!$historyAdministration)
                    <div class="rounded-3xl border border-yellow-200 bg-yellow-50 p-8 text-center">
                        <h3 class="text-sm font-black text-yellow-700">Administration Not Available</h3>
                        <p class="mt-2 text-xs text-yellow-600">The selected administration could not be found.</p>
                    </div>
                @else
                    <div class="mb-6 flex flex-col gap-4 rounded-2xl bg-gray-800 p-6 text-white shadow-md md:flex-row md:items-center md:justify-between">
                        <div class="flex items-center gap-4">
                            <div class="rounded-xl bg-white/10 p-3 text-2xl">@include('partials.ui.icon',['icon'=>'archive','iconSize'=>18])</div>
                            <div>
                                <p class="text-[9px] font-black uppercase tracking-[0.18em] text-gray-300">Completed Administration</p>
                                <h2 class="mt-1 text-xl font-black uppercase tracking-tight">{{ $historyAdministration->start_year }} - {{ $historyAdministration->end_year }}</h2>
                                <p class="mt-1 text-xs text-gray-300">Barangay {{ $barangayName }}</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span class="rounded-full border border-white/10 bg-white/10 px-4 py-2 text-[10px] font-bold uppercase tracking-widest">{{ $historyRecords->count() }} Total Members</span>
                            <span class="rounded-full border border-white/10 bg-white/10 px-4 py-2 text-[10px] font-bold uppercase tracking-widest">{{ $historyCouncilors->count() }} Councilors</span>
                        </div>
                    </div>

                    <div class="mb-6 rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
                        <div class="mb-5 flex items-center justify-between border-b border-gray-100 pb-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-gray-500">@include('partials.ui.icon',['icon'=>'shield-check','iconSize'=>16])</span>
                                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Executive Officers</h3>
                                </div>
                                <p class="mt-1 text-[9px] text-gray-400">Chairman, Secretary and Treasurer from this administration</p>
                            </div>
                            <span class="rounded-full bg-gray-100 px-3 py-1 text-[9px] font-black text-gray-500">{{ $historyExecutives->count() }}</span>
                        </div>
                        <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                            @forelse($historyExecutives as $record)
                                @php
                                    $style=match($record['position']){
                                        'SK Chairman'=>['avatar'=>'bg-green-600','badge'=>'bg-green-50 text-green-700','border'=>'border-green-100'],
                                        'SK Secretary'=>['avatar'=>'bg-blue-600','badge'=>'bg-blue-50 text-blue-700','border'=>'border-blue-100'],
                                        'SK Treasurer'=>['avatar'=>'bg-yellow-500','badge'=>'bg-yellow-50 text-yellow-700','border'=>'border-yellow-100'],
                                        default=>['avatar'=>'bg-gray-600','badge'=>'bg-gray-100 text-gray-600','border'=>'border-gray-200'],
                                    };
                                    $completedLabel=$record['completed_at'] ? \Carbon\Carbon::parse($record['completed_at'])->format('M j, Y') : 'Administration completed';
                                    $actionKey=$record['source'].':'.$record['record_id'];
                                    $reappointType=$reappointmentActions[$actionKey] ?? null;
                                @endphp
                                <article class="leadership-search-item rounded-2xl border {{ $style['border'] }} bg-white p-5 transition hover:shadow-sm"
                                    data-search="{{ strtolower($record['name'].' '.$record['position'].' '.$record['email'].' '.$record['phone'].' '.$record['start_year'].'-'.$record['end_year']) }}">
                                    <div class="flex items-start gap-4">
                                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $style['avatar'] }} font-black text-white">{{ strtoupper(substr($record['name'] ?: 'NA',0,2)) }}</div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h4 class="text-sm font-black uppercase text-gray-800">{{ $record['name'] }}</h4>
                                                <span class="rounded-full px-2.5 py-1 text-[8px] font-black uppercase {{ $style['badge'] }}">{{ $record['position'] }}</span>
                                            </div>
                                            <div class="mt-2"><span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-[8px] font-black uppercase text-gray-500">Completed</span></div>
                                            <div class="mt-3 space-y-1.5 text-[10px] text-gray-500">
                                                <p class="truncate">@include('partials.ui.icon',['icon'=>'mail','iconSize'=>15]) {{ $record['email'] ?: 'No email provided' }}</p>
                                                <p>@include('partials.ui.icon',['icon'=>'phone','iconSize'=>15]) {{ $record['phone'] ?: 'No phone provided' }}</p>
                                                <p>@include('partials.ui.icon',['icon'=>'calendar-days','iconSize'=>15]) Term: {{ $record['start_year'] }} - {{ $record['end_year'] }}</p>
                                                <p>✓ Service ended: {{ $completedLabel }}</p>
                                            </div>
                                            @if($reappointType==='secretary')
                                                <form method="POST" action="{{ route('sk_chairman.leadership.secretary.reappoint',$record['record_id']) }}" class="mt-4"
                                                    onsubmit="return confirm(@js('Reappoint '.$record['name'].' as SK Secretary for the current administration? Their existing account will be reused and a new password setup link will be sent.'))">
                                                    @csrf
                                                    <button type="submit" class="rounded-xl bg-blue-600 px-4 py-2.5 text-[9px] font-black uppercase text-white transition hover:bg-blue-700">Reappoint Secretary</button>
                                                </form>
                                            @elseif($reappointType==='treasurer')
                                                <form method="POST" action="{{ route('sk_chairman.leadership.council.reappoint',$record['record_id']) }}" class="mt-4"
                                                    onsubmit="return confirm(@js('Reappoint '.$record['name'].' as SK Treasurer for the current administration?'))">
                                                    @csrf
                                                    <button type="submit" class="rounded-xl bg-yellow-500 px-4 py-2.5 text-[9px] font-black uppercase text-white transition hover:bg-yellow-600">Reappoint Treasurer</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            @empty
                                <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-8 text-center xl:col-span-3">
                                    <p class="text-sm font-bold text-gray-500">No executive officers found.</p>
                                    <p class="mt-1 text-xs text-gray-400">No historical Chairman, Secretary, or Treasurer records were found for this administration.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
                        <div class="mb-5 flex items-center justify-between border-b border-gray-100 pb-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-purple-500">@include('partials.ui.icon',['icon'=>'award','iconSize'=>16])</span>
                                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">SK Councilors</h3>
                                </div>
                                <p class="mt-1 text-[9px] text-gray-400">Council members recorded for this completed administration</p>
                            </div>
                            <span class="rounded-full bg-purple-50 px-3 py-1 text-[9px] font-black text-purple-600">{{ $historyCouncilors->count() }}</span>
                        </div>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                            @forelse($historyCouncilors as $record)
                                @php
                                    $completedLabel=$record['completed_at'] ? \Carbon\Carbon::parse($record['completed_at'])->format('M j, Y') : 'Administration completed';
                                    $actionKey=$record['source'].':'.$record['record_id'];
                                    $reappointType=$reappointmentActions[$actionKey] ?? null;
                                @endphp
                                <article class="leadership-search-item rounded-2xl border border-purple-100 bg-white p-4 transition hover:shadow-sm"
                                    data-search="{{ strtolower($record['name'].' '.$record['position'].' '.$record['email'].' '.$record['phone'].' '.$record['start_year'].'-'.$record['end_year']) }}">
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-purple-600 text-xs font-black text-white">{{ strtoupper(substr($record['name'] ?: 'NA',0,2)) }}</div>
                                        <div class="min-w-0 flex-1">
                                            <h4 class="truncate text-xs font-black uppercase text-gray-800">{{ $record['name'] }}</h4>
                                            <div class="mt-1"><span class="rounded-full bg-purple-50 px-2 py-1 text-[8px] font-black uppercase text-purple-700">SK Councilor</span></div>
                                            <div class="mt-3 space-y-1 text-[9px] text-gray-400">
                                                <p class="truncate">@include('partials.ui.icon',['icon'=>'mail','iconSize'=>14]) {{ $record['email'] ?: 'No email provided' }}</p>
                                                <p>@include('partials.ui.icon',['icon'=>'phone','iconSize'=>14]) {{ $record['phone'] ?: 'No phone provided' }}</p>
                                                <p>@include('partials.ui.icon',['icon'=>'calendar-days','iconSize'=>14]) {{ $record['start_year'] }} - {{ $record['end_year'] }}</p>
                                                <p>✓ Service ended: {{ $completedLabel }}</p>
                                            </div>
                                            @if($reappointType==='councilor')
                                                <form method="POST" action="{{ route('sk_chairman.leadership.council.reappoint',$record['record_id']) }}" class="mt-4"
                                                    onsubmit="return confirm(@js('Reappoint '.$record['name'].' as SK Councilor for the current administration?'))">
                                                    @csrf
                                                    <button type="submit" class="rounded-xl bg-red-600 px-4 py-2.5 text-[9px] font-black uppercase text-white transition hover:bg-red-700">Reappoint Councilor</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            @empty
                                <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-8 text-center md:col-span-2 xl:col-span-3">
                                    <p class="text-sm font-bold text-gray-500">No SK Councilors found.</p>
                                    <p class="mt-1 text-xs text-gray-400">No historical Councilor records were found for this administration.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div id="leadershipNoResults" class="mt-6 hidden rounded-2xl border border-gray-200 bg-white p-8 text-center">
                        <div class="text-3xl">@include('partials.ui.icon',['icon'=>'search','iconSize'=>18])</div>
                        <p class="mt-2 text-sm font-bold text-gray-600">No leadership record matched your search.</p>
                        <p class="mt-1 text-xs text-gray-400">Search by official name, position, email, phone or administration term.</p>
                    </div>
                @endif

            @endif
        </main>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{

    const notifBtn=document.getElementById('notifBtn');
    const notifDropdown=document.getElementById('notifDropdown');
    const userMenuBtn=document.getElementById('userMenuBtn');
    const userDropdown=document.getElementById('userDropdown');

    /*
    |--------------------------------------------------------------------------
    | MODAL HELPER
    |--------------------------------------------------------------------------
    */
    window.toggleModal=(id)=>{
        const modal=document.getElementById(id);

        if(modal){
            modal.classList.toggle('hidden');
        }
    };

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATION DROPDOWN
    |--------------------------------------------------------------------------
    */
    if(notifBtn && notifDropdown){
        notifBtn.addEventListener('click',(e)=>{
            e.stopPropagation();

            notifDropdown.classList.toggle('hidden');

            if(userDropdown){
                userDropdown.classList.add('hidden');
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | USER DROPDOWN
    |--------------------------------------------------------------------------
    */
    if(userMenuBtn && userDropdown){
        userMenuBtn.addEventListener('click',(e)=>{
            e.stopPropagation();

            userDropdown.classList.toggle('hidden');

            if(notifDropdown){
                notifDropdown.classList.add('hidden');
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | BULK COUNCILOR ROWS
    |--------------------------------------------------------------------------
    */
    const bulkRows=document.getElementById('bulkCouncilorRows');
    const addCouncilorRow=document.getElementById('addCouncilorRow');

    let bulkIndex={{ $bulkNextIndex }};

    if(bulkRows && addCouncilorRow){
        addCouncilorRow.addEventListener('click',()=>{
            const firstRow=bulkRows.querySelector('.bulk-councilor-row');

            if(!firstRow){
                return;
            }

            const newRow=firstRow.cloneNode(true);

            newRow.querySelectorAll('input').forEach((field)=>{
                const name=field.getAttribute('name');

                if(name){
                    field.setAttribute(
                        'name',
                        name.replace(
                            /councilors\[\d+\]/,
                            `councilors[${bulkIndex}]`
                        )
                    );
                }

                field.value='';
            });

            const removeBtn=newRow.querySelector('.removeCouncilorRow');

            if(removeBtn){
                removeBtn.classList.remove('hidden');
            }

            bulkRows.appendChild(newRow);
            bulkIndex++;
            updateCouncilorTitles();
        });
    }

    document.addEventListener('click',(e)=>{
        if(e.target.classList.contains('removeCouncilorRow')){
            const row=e.target.closest('.bulk-councilor-row');

            if(row){
                row.remove();
                updateCouncilorTitles();
            }
        }
    });

    function updateCouncilorTitles(){
        document.querySelectorAll('.bulk-councilor-row').forEach((row,index)=>{
            const title=row.querySelector('.bulk-councilor-title');
            const removeBtn=row.querySelector('.removeCouncilorRow');

            if(title){
                title.textContent=`Councilor #${index+1}`;
            }

            if(removeBtn){
                removeBtn.classList.toggle('hidden',index===0);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | ACTION MENUS
    |--------------------------------------------------------------------------
    */
    document.querySelectorAll('.action-menu-btn,.secretary-menu-btn').forEach((btn)=>{
        btn.addEventListener('click',(e)=>{
            e.stopPropagation();

            const menu=btn.nextElementSibling;

            document.querySelectorAll('.action-menu,.secretary-menu').forEach((other)=>{
                if(other!==menu){
                    other.classList.add('hidden');
                }
            });

            menu?.classList.toggle('hidden');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | EDIT COUNCILOR
    |--------------------------------------------------------------------------
    */
    const editCouncilorModal=document.getElementById('editCouncilorModal');
    const editCouncilorForm=document.getElementById('editCouncilorForm');

    const councilorUpdateUrlTemplate=@json(
        route('sk_chairman.leadership.councilor.update',[
            'councilId'=>'__ID__'
        ])
    );

    document.querySelectorAll('.edit-councilor-btn').forEach((btn)=>{
        btn.addEventListener('click',()=>{
            const id=btn.dataset.id;

            if(!id || !editCouncilorForm){
                return;
            }

            editCouncilorForm.action=councilorUpdateUrlTemplate.replace('__ID__',id);

            document.getElementById('edit_councilor_id').value=id;
            document.getElementById('edit_councilor_name').value=btn.dataset.name || '';
            document.getElementById('edit_councilor_email').value=btn.dataset.email || '';
            document.getElementById('edit_councilor_phone').value=btn.dataset.phone || '';

            editCouncilorModal?.classList.remove('hidden');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | REAL-TIME SEARCH
    |--------------------------------------------------------------------------
    */
    const leadershipSearch=document.getElementById('leadershipSearch');
    const leadershipNoResults=document.getElementById('leadershipNoResults');

    if(leadershipSearch){
        leadershipSearch.addEventListener('input',()=>{
            const search=leadershipSearch.value.trim().toLowerCase();
            const items=document.querySelectorAll('.leadership-search-item');

            let visibleCount=0;

            items.forEach((item)=>{
                const content=(item.dataset.search || '').toLowerCase();
                const matched=search==='' || content.includes(search);

                item.classList.toggle('hidden',!matched);

                if(matched){
                    visibleCount++;
                }
            });

            if(leadershipNoResults){
                leadershipNoResults.classList.toggle(
                    'hidden',
                    search==='' || visibleCount>0
                );
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | CLOSE DROPDOWNS
    |--------------------------------------------------------------------------
    */
    document.addEventListener('click',(e)=>{
        if(
            notifBtn &&
            notifDropdown &&
            !notifBtn.contains(e.target) &&
            !notifDropdown.contains(e.target)
        ){
            notifDropdown.classList.add('hidden');
        }

        if(
            userMenuBtn &&
            userDropdown &&
            !userMenuBtn.contains(e.target) &&
            !userDropdown.contains(e.target)
        ){
            userDropdown.classList.add('hidden');
        }

        document.querySelectorAll('.action-menu,.secretary-menu').forEach((menu)=>{
            if(!menu.contains(e.target)){
                menu.classList.add('hidden');
            }
        });
    });

    /*
    |--------------------------------------------------------------------------
    | CLOSE MODALS BY CLICKING BACKDROP
    |--------------------------------------------------------------------------
    */
    [
        'bulkCouncilorModal',
        'addSecretaryModal',
        'editSecretaryModal',
        'addTreasurerModal',
        'editTreasurerModal',
        'editCouncilorModal'
    ].forEach((id)=>{
        const modal=document.getElementById(id);

        if(modal){
            modal.addEventListener('click',(e)=>{
                if(e.target===modal){
                    modal.classList.add('hidden');
                }
            });
        }
    });

    /*
    |--------------------------------------------------------------------------
    | OPEN CORRECT MODAL AFTER VALIDATION ERROR
    |--------------------------------------------------------------------------
    */
    @if($errors->bulkCouncilors->any())

        document.getElementById('bulkCouncilorModal')
            ?.classList.remove('hidden');

    @elseif($errors->secretaryAdd->any())

        document.getElementById('addSecretaryModal')
            ?.classList.remove('hidden');

    @elseif($errors->secretaryEdit->any())

        document.getElementById('editSecretaryModal')
            ?.classList.remove('hidden');

    @elseif($errors->treasurerAdd->any())

        document.getElementById('addTreasurerModal')
            ?.classList.remove('hidden');

    @elseif($errors->treasurerEdit->any())

        document.getElementById('editTreasurerModal')
            ?.classList.remove('hidden');

    @elseif($errors->councilorEdit->any())

        @php
            $failedCouncilorId=old('edit_councilor_id');
        @endphp

        @if($failedCouncilorId)

            if(editCouncilorForm){
                editCouncilorForm.action=
                    councilorUpdateUrlTemplate.replace(
                        '__ID__',
                        @json((string)$failedCouncilorId)
                    );
            }

            document.getElementById('editCouncilorModal')
                ?.classList.remove('hidden');

        @endif

    @endif

    /*
    |--------------------------------------------------------------------------
    | SUCCESS MESSAGE
    |--------------------------------------------------------------------------
    */
    @if(session('success'))

        Swal.fire({
            icon:'success',
            title:'Success!',
            text:@json(session('success')),
            confirmButtonColor:'#DC2626'
        });

    @endif
});
</script>
@endpush
