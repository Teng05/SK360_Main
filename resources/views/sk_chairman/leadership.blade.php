{{-- File guide: Blade view template for resources/views/sk_chairman/leadership.blade.php. --}}
@extends('layouts.app')

@section('title','Leadership | SK 360')

@section('page_css')
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('content')

<!-- ADD COUNCILOR MODAL -->
<div id="addCouncilorModal" class="hidden fixed inset-0 bg-black/50 z-[100] flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl w-full max-w-md overflow-hidden shadow-2xl">
        <div class="bg-red-600 p-6 text-white">
            <h2 class="text-xl font-black uppercase tracking-tighter">Add Councilor</h2>
            <p class="text-[10px] opacity-80 uppercase font-bold">Add an SK Councilor to your barangay.</p>
        </div>

        <form method="POST" action="{{ route('sk_chairman.leadership.store') }}" class="p-6 space-y-4">
            @csrf

            @if($errors->councilorAdd->any())
                <div class="rounded-xl bg-red-50 border border-red-200 p-3 text-xs text-red-600">
                    @foreach($errors->councilorAdd->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="space-y-1">
                <label class="text-[10px] font-black text-gray-400 uppercase">Full Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    class="w-full border-b-2 border-gray-100 focus:border-red-500 outline-none py-1 text-sm font-bold">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-[10px] font-black text-gray-400 uppercase">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                        class="w-full border-b-2 border-gray-100 focus:border-red-500 outline-none py-1 text-sm font-bold">
                </div>

                <div class="space-y-1">
                    <label class="text-[10px] font-black text-gray-400 uppercase">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                        class="w-full border-b-2 border-gray-100 focus:border-red-500 outline-none py-1 text-sm font-bold">
                </div>
            </div>

            <div class="rounded-2xl bg-red-50 px-4 py-3 text-xs font-bold text-red-700">
                Position: SK Councilor
            </div>

            <div class="space-y-1">
                <label class="text-[10px] font-black text-gray-400 uppercase">Term Period</label>
                <input type="text" name="term" value="{{ old('term','2023-2026') }}"
                    class="w-full border-b-2 border-gray-100 focus:border-red-500 outline-none py-1 text-sm font-bold">
            </div>

            <div class="flex gap-3 pt-4">
                <button type="button" onclick="toggleModal('addCouncilorModal')"
                    class="flex-1 py-3 text-xs font-black uppercase text-gray-400">
                    Cancel
                </button>

                <button type="submit"
                    class="flex-1 bg-red-600 py-3 rounded-xl text-xs font-black uppercase text-white shadow-lg">
                    Save Councilor
                </button>
            </div>
        </form>
    </div>
</div>

<!-- BULK COUNCILOR VALUES -->
@php
    $oldCouncilors=old('councilors',[
        [
            'name'=>'',
            'email'=>'',
            'phone'=>'',
            'term'=>'2023-2026',
        ]
    ]);

    $bulkIndexes=array_map('intval',array_keys($oldCouncilors));
    $bulkNextIndex=empty($bulkIndexes) ? 0 : max($bulkIndexes)+1;
@endphp

<!-- BULK ADD COUNCILORS MODAL -->
<div id="bulkCouncilorModal" class="hidden fixed inset-0 bg-black/50 z-[100] flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl w-full max-w-5xl max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="bg-red-600 p-6 text-white flex justify-between items-center">
            <div>
                <h2 class="text-xl font-black uppercase tracking-tighter">Bulk Add Councilors</h2>
                <p class="text-[10px] opacity-80 uppercase font-bold">Add multiple SK Councilors at once.</p>
            </div>

            <button type="button" onclick="toggleModal('bulkCouncilorModal')" class="text-2xl">&times;</button>
        </div>

        <form method="POST" action="{{ route('sk_chairman.leadership.bulk-councilors') }}" class="p-6">
            @csrf

            @if($errors->bulkCouncilors->any())
                <div class="mb-4 rounded-xl bg-red-50 border border-red-200 p-3 text-xs text-red-600">
                    <p class="font-black mb-1">Bulk Add Failed</p>

                    @foreach($errors->bulkCouncilors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

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

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
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

                            <input type="text"
                                name="councilors[{{ $index }}][term]"
                                value="{{ $councilor['term'] ?? '2023-2026' }}"
                                placeholder="Term"
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

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase">Term Start</label>
                    <input type="date"
                        name="secretary_term_start"
                        value="{{ old('secretary_term_start') }}"
                        required
                        class="w-full border-b-2 border-gray-100 focus:border-blue-500 outline-none py-2 text-sm font-bold">
                </div>

                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase">Term End</label>
                    <input type="date"
                        name="secretary_term_end"
                        value="{{ old('secretary_term_end') }}"
                        required
                        class="w-full border-b-2 border-gray-100 focus:border-blue-500 outline-none py-2 text-sm font-bold">
                </div>
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

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase">Term Start</label>
                    <input type="date"
                        name="edit_secretary_term_start"
                        value="{{ old('edit_secretary_term_start',$secretary['term_start']) }}"
                        required
                        class="w-full border-b-2 border-gray-100 focus:border-blue-500 outline-none py-2 text-sm font-bold">
                </div>

                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase">Term End</label>
                    <input type="date"
                        name="edit_secretary_term_end"
                        value="{{ old('edit_secretary_term_end',$secretary['term_end']) }}"
                        required
                        class="w-full border-b-2 border-gray-100 focus:border-blue-500 outline-none py-2 text-sm font-bold">
                </div>
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

            <div>
                <label class="text-[10px] font-black text-gray-400 uppercase">Term</label>
                <input type="text"
                    name="treasurer_term"
                    value="{{ old('treasurer_term','2023-2026') }}"
                    class="w-full border-b-2 border-gray-100 focus:border-yellow-500 outline-none py-2 text-sm font-bold">
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

            <div>
                <label class="text-[10px] font-black text-gray-400 uppercase">Term</label>
                <input type="text"
                    name="edit_treasurer_term"
                    value="{{ old('edit_treasurer_term',$treasurer['term']) }}"
                    class="w-full border-b-2 border-gray-100 focus:border-yellow-500 outline-none py-2 text-sm font-bold">
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

            <div>
                <label class="text-[10px] font-black text-gray-400 uppercase">Term</label>
                <input type="text"
                    id="edit_councilor_term"
                    name="edit_councilor_term"
                    value="{{ old('edit_councilor_term') }}"
                    class="w-full border-b-2 border-gray-100 focus:border-red-500 outline-none py-2 text-sm font-bold">
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

    <!-- SIDEBAR -->
    <div class="w-64 bg-red-600 text-white flex flex-col p-3 overflow-y-auto">
        <div class="flex items-center gap-3 mb-4">
            <img src="{{ asset('images/logo.png') }}" class="w-8 h-8 rounded-full object-cover" alt="logo">

            <div class="leading-tight">
                <h2 class="text-lg font-extrabold tracking-wide">SK 360°</h2>
                <p class="text-[10px] opacity-80">Management System</p>
            </div>
        </div>

        <div class="bg-red-500 rounded-lg p-2 flex items-center gap-2 mb-3 shadow text-xs">
            <div class="bg-yellow-400 text-red-600 p-1 rounded-full text-sm">👤</div>

            <div>
                <p class="font-semibold text-xs">SK Chairman</p>
                <p class="text-xs opacity-80">Active Role</p>
            </div>
        </div>

        <nav class="space-y-1 text-xs">
            @foreach($menuItems as $item)
                @php $isActive=$item['link'] === $currentUrl; @endphp

                <a href="{{ $item['link'] }}"
                    class="flex items-center gap-2 p-2 rounded-lg {{ $isActive ? 'bg-red-500' : 'hover:bg-red-500 transition' }}">
                    <span class="{{ $isActive ? 'bg-yellow-400 text-red-600' : 'bg-red-400' }} p-1 rounded text-sm">
                        {!! $item['icon'] !!}
                    </span>

                    <span class="{{ $isActive ? 'text-yellow-300 font-semibold' : '' }}">
                        {{ $item['label'] }}
                    </span>
                </a>
            @endforeach
        </nav>
    </div>

    <!-- MAIN SECTION -->
    <div class="flex-1 flex flex-col overflow-hidden">

        <!-- HEADER -->
        <div class="bg-red-600 text-white px-6 py-3 flex justify-between items-center shadow">
            <input type="text"
                id="leadershipSearch"
                placeholder="Search officials..."
                autocomplete="off"
                class="px-4 py-2 rounded-full text-black w-1/3 focus:outline-none">

            <div class="flex items-center gap-3 relative">

                <!-- NOTIFICATION -->
                <div class="relative">
                    <button id="notifBtn" type="button"
                        class="text-xl hover:bg-red-500 p-2 rounded-lg transition">
                        🔔
                    </button>

                    <div id="notifDropdown"
                        class="hidden absolute right-0 mt-3 w-72 bg-white rounded-2xl shadow-xl border z-50 overflow-hidden">
                        <div class="px-4 py-3 font-semibold border-b text-gray-800">
                            Notifications
                        </div>

                        <div class="max-h-64 overflow-y-auto">
                            <div class="px-4 py-3 hover:bg-gray-100 text-sm text-gray-700">
                                No notifications yet
                            </div>
                        </div>
                    </div>
                </div>

                <!-- USER MENU -->
                <div class="relative">
                    <button id="userMenuBtn" type="button"
                        class="flex items-center gap-2 hover:bg-red-500 px-3 py-2 rounded-lg transition">
                        <span class="font-semibold">{{ $fullName }}</span>
                    </button>

                    <div id="userDropdown"
                        class="hidden absolute right-0 mt-3 w-64 bg-white rounded-2xl shadow-xl border overflow-hidden z-50">

                        <div class="px-5 py-4 font-semibold text-gray-800 border-b">
                            My Account
                        </div>

                        <a href="{{ route('sk_chairman.profile') }}"
                            class="flex items-center gap-3 px-5 py-3 hover:bg-gray-100 transition">
                            <span>👤</span>
                            <span class="text-gray-700">Profile Settings</span>
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <button type="submit"
                                class="w-full text-left flex items-center gap-3 px-5 py-3 text-red-500 hover:bg-gray-100 transition">
                                <span>↩️</span>
                                <span>Log Out</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <main class="p-8 overflow-y-auto h-full bg-gray-50">

            @if(session('warning'))
                <div class="mb-5 rounded-xl border border-yellow-200 bg-yellow-50 p-3 text-xs text-yellow-700">
                    {{ session('warning') }}
                </div>
            @endif

            <!-- PAGE TITLE -->
            <div class="flex justify-between items-end mb-8">
                <div>
                    <h1 class="text-3xl font-black text-gray-800 uppercase tracking-tight">
                        Council Leadership
                    </h1>

                    <p class="text-gray-500 font-medium italic">
                        Official Directory for Barangay {{ $barangayName }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button"
                        onclick="toggleModal('bulkCouncilorModal')"
                        class="bg-gray-800 hover:bg-gray-900 text-white px-5 py-3 rounded-2xl font-black uppercase text-[10px] tracking-widest shadow-lg transition-all">
                        &#128101; Bulk Add
                    </button>

                    <button type="button"
                        onclick="toggleModal('addCouncilorModal')"
                        class="bg-red-600 hover:bg-red-700 text-white px-5 py-3 rounded-2xl font-black uppercase text-[10px] tracking-widest shadow-lg transition-all">
                        <span class="text-base">+</span> Single Add
                    </button>
                </div>
            </div>

            <!-- BARANGAY CARD -->
            <div class="bg-red-600 rounded-2xl p-6 text-white mb-8 shadow-md flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <div class="bg-white/20 p-3 rounded-xl text-2xl">
                        &#128205;
                    </div>

                    <div>
                        <h2 class="text-xl font-black uppercase tracking-tight">
                            Barangay {{ $barangayName }}
                        </h2>

                        <p class="text-xs opacity-80 font-medium">
                            Current SK Administration
                        </p>
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
                        <span class="text-red-500 font-bold">&#128737;</span>

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

                            if($isChairman){
                                $officerBorder='border-red-100 bg-red-50/30';
                                $avatarStyle='bg-red-600 text-white';
                                $positionStyle='bg-red-600 text-white';
                                $officerIcon='&#128737;';
                            }elseif($isSecretary){
                                $officerBorder='border-blue-100 bg-blue-50/30';
                                $avatarStyle='bg-blue-600 text-white';
                                $positionStyle='bg-blue-600 text-white';
                                $officerIcon='&#128196;';
                            }else{
                                $officerBorder='border-yellow-200 bg-yellow-50/40';
                                $avatarStyle='bg-yellow-500 text-white';
                                $positionStyle='bg-yellow-500 text-white';
                                $officerIcon='&#128176;';
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
                            ) }}"
                        >

                            <div class="w-16 h-16 rounded-2xl {{ $avatarStyle }} flex items-center justify-center font-black text-xl border-4 border-white shadow-sm">
                                {{ strtoupper(substr($member['name'] ?? 'U',0,2)) }}
                            </div>

                            <div class="flex-1">
                                <div class="flex flex-wrap items-center gap-3">

                                    <h4 class="text-lg font-black text-gray-800 uppercase leading-none">
                                        {{ $member['name'] }}
                                    </h4>

                                    <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase {{ $positionStyle }}">
                                        {!! $officerIcon !!} {{ $member['position'] }}
                                    </span>

                                    @if($isSecretary)

                                        @if($pending)
                                            <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase bg-yellow-100 text-yellow-700">
                                                Pending
                                            </span>

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
                                        <span>&#128231;</span>
                                        {{ $member['email'] ?: 'No email provided' }}
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <span>&#128222;</span>
                                        {{ $member['phone'] ?: 'No phone provided' }}
                                    </div>

                                    <div class="flex items-center gap-2 uppercase tracking-tighter">
                                        <span>&#128197;</span>
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

                                    <div class="secretary-menu hidden absolute right-0 top-10 z-40 w-44 rounded-xl border bg-white shadow-xl py-1">

                                        <button type="button"
                                            onclick="toggleModal('editSecretaryModal')"
                                            class="block w-full text-left px-4 py-2 text-xs hover:bg-gray-50">
                                            Edit Details
                                        </button>

                                        @if($pending)

                                            <form method="POST"
                                                action="{{ route('sk_chairman.leadership.secretary.resend',$member['user_id']) }}"
                                                onsubmit="return confirm('Send a new setup link to {{ $member['email'] }}?');">
                                                @csrf

                                                <button type="submit"
                                                    class="block w-full text-left px-4 py-2 text-xs font-semibold text-blue-600 hover:bg-blue-50">
                                                    Resend Setup Link
                                                </button>
                                            </form>

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

                                        <div class="border-t my-1"></div>

                                        <form method="POST"
                                            action="{{ route('sk_chairman.leadership.secretary.destroy',$member['user_id']) }}"
                                            onsubmit="return confirm('Delete this secretary account?');">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                class="block w-full text-left px-4 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">
                                                Delete Account
                                            </button>
                                        </form>
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

                                    <div class="action-menu hidden absolute right-0 top-10 z-40 w-44 rounded-xl border bg-white shadow-xl py-1">

                                        <button type="button"
                                            onclick="toggleModal('editTreasurerModal')"
                                            class="block w-full text-left px-4 py-2 text-xs hover:bg-gray-50">
                                            Edit Details
                                        </button>

                                        <div class="border-t my-1"></div>

                                        <form method="POST"
                                            action="{{ route('sk_chairman.leadership.destroy',$member['council_id']) }}"
                                            onsubmit="return confirm('Remove this treasurer?');">
                                            @csrf

                                            <button type="submit"
                                                class="block w-full text-left px-4 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">
                                                Remove Treasurer
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
                    <span class="text-yellow-500 font-bold">&#127775;</span>

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
                            ) }}"
                        >

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
                                    &#128222; {{ $member['phone'] ?: 'No phone provided' }}
                                </p>

                                <p class="text-[10px] text-gray-400 mt-1 truncate">
                                    &#128231; {{ $member['email'] ?: 'No email provided' }}
                                </p>

                                <p class="text-[9px] text-gray-400 mt-1">
                                    &#128197; {{ $member['term'] ?: 'N/A' }}
                                </p>

                            </div>

                            @if(!empty($member['council_id']))
                                <div class="relative">

                                    <button type="button"
                                        class="action-menu-btn rounded-lg px-2 py-1 text-xl text-gray-400 hover:bg-gray-100">
                                        &#8942;
                                    </button>

                                    <div class="action-menu hidden absolute right-0 top-9 z-40 w-40 rounded-xl border bg-white shadow-xl py-1">

                                        <button type="button"
                                            class="edit-councilor-btn block w-full text-left px-4 py-2 text-xs hover:bg-gray-50"
                                            data-id="{{ $member['council_id'] }}"
                                            data-name="{{ $member['name'] }}"
                                            data-email="{{ $member['email'] ?? '' }}"
                                            data-phone="{{ $member['phone'] ?? '' }}"
                                            data-term="{{ $member['term'] ?? '' }}">
                                            Edit Details
                                        </button>

                                        <div class="border-t my-1"></div>

                                        <form method="POST"
                                            action="{{ route('sk_chairman.leadership.destroy',$member['council_id']) }}"
                                            onsubmit="return confirm('Remove this councilor?');">
                                            @csrf

                                            <button type="submit"
                                                class="block w-full text-left px-4 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">
                                                Remove Councilor
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
            <div id="leadershipNoResults" class="hidden py-10 text-center">
                <div class="text-3xl mb-2">&#128269;</div>

                <p class="text-sm font-bold text-gray-500">
                    No officials found.
                </p>

                <p class="text-xs text-gray-400 mt-1">
                    Try a different name, position, email, phone number, term, or status.
                </p>
            </div>

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

                if(field.getAttribute('name')?.includes('[term]')){
                    field.value='2023-2026';
                }else{
                    field.value='';
                }
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
            document.getElementById('edit_councilor_term').value=btn.dataset.term || '';

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
        'addCouncilorModal',
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

    @if($errors->councilorAdd->any())

        document.getElementById('addCouncilorModal')
            ?.classList.remove('hidden');

    @elseif($errors->bulkCouncilors->any())

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