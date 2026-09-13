{{-- File guide: Blade view template for resources/views/sk_pres/module.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK 360 Dashboard')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection

@section('content')
<div class="flex h-screen bg-[#f1f5f9] overflow-hidden">
    <div class="w-64 bg-red-600 text-white flex flex-col p-3 overflow-y-auto">
        <div class="flex items-center gap-3 mb-4">
    <img src="{{ asset('images/sk logo.png') }}" class="w-8 h-8 rounded-full object-cover"  alt="logo">
    <div class="leading-tight">
        <h2 class="text-lg font-extrabold tracking-wide">SK 360°</h2>
        <p class="text-[10px] opacity-80">Management System</p>
    </div>
</div>

        <div class="bg-red-500 rounded-lg p-2 flex items-center gap-2 mb-3 shadow text-xs">
            <div class="bg-yellow-400 text-red-600 p-1 rounded-full text-sm">👤</div>
            <div>
                <p class="font-semibold text-xs">SK President</p>
                <p class="text-xs opacity-80">Active Role</p>
            </div>
        </div>

        <nav class="space-y-1 text-xs">
            @foreach ($menuItems as $item)
                <a href="{{ $item['link'] }}" class="flex items-center gap-2 p-2 rounded-lg {{ $item['link'] === $currentUrl ? 'bg-red-500' : 'hover:bg-red-500 transition' }}">
                    <span class="{{ $item['link'] === $currentUrl ? 'bg-yellow-400 text-red-600' : 'bg-red-400' }} p-1 rounded text-sm">{!! $item['icon'] !!}</span>
                    <span class="{{ $item['link'] === $currentUrl ? 'text-yellow-300 font-semibold' : '' }} text-xs">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <div class="flex-1 flex flex-col">
        <div class="bg-red-600 text-white px-6 py-3 flex justify-between items-center shadow">
            <input
                type="text"
                placeholder="Search..."
                class="px-4 py-2 rounded-full text-black w-1/3 focus:outline-none"
            >

            <div class="flex items-center gap-3 relative">
                <div class="relative">
                    <button id="notifBtn" type="button" class="text-xl hover:bg-red-500 p-2 rounded-lg transition">
                        🔔
                    </button>

                    <div id="notifDropdown" class="hidden absolute right-0 mt-3 w-72 bg-white rounded-2xl shadow-xl border z-50 overflow-hidden">
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

                <div class="relative">
                    <button id="userMenuBtn" type="button" class="flex items-center gap-2 hover:bg-red-500 px-3 py-2 rounded-lg transition">
                        <span class="font-semibold">{{ $fullName }}</span>
                    </button>

                    <div id="userDropdown" class="hidden absolute right-0 mt-3 w-64 bg-white rounded-2xl shadow-xl border overflow-hidden z-50">
                        <div class="px-5 py-4 font-semibold text-gray-800 border-b">
                            My Account
                        </div>

                        <a href="{{ route('sk_pres.profile') }}" class="flex items-center gap-3 px-5 py-3 hover:bg-gray-100 transition">
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

        <main class="flex-1 overflow-y-auto p-8 bg-[#f8fafc]">
            @if (!($submissionPage ?? false))
            <div class="flex items-start justify-between mb-8">
                <div>
                    <h2 class="text-[38px] font-bold text-gray-900 leading-tight">
                        Submission Slot Management
                    </h2>
                    <p class="text-gray-500 mt-2 text-base">
                        Create and manage submission periods for Accomplishment Reports and Budget Documents
                    </p>
                </div>

                <button id="openModalBtn" class="bg-red-600 hover:bg-red-700 text-white px-5 py-3 rounded-lg text-sm font-semibold shadow-sm">
                    ＋ Create Submission Slot
                </button>
            </div>

            @if (session('status'))
                <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div id="submissionModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50 px-4">
                <div class="bg-white w-full max-w-3xl rounded-[24px] border-2 border-blue-500 shadow-2xl p-8 relative">
                    <button id="closeModalBtn" class="absolute top-4 right-5 text-gray-500 hover:text-red-600 text-2xl font-bold">
                        &times;
                    </button>

                    <h2 class="text-4xl font-bold text-gray-900 mb-2">
                        Create New Submission Slot
                    </h2>
                    <p class="text-gray-600 mb-8 text-base">
                        Set up a new submission period for SK officials to submit reports
                    </p>

                    <form id="slotForm" action="{{ route('sk_pres.module.store') }}" method="POST" class="space-y-6">
                        @csrf

                        <div>
                            <label class="block text-lg font-semibold text-gray-900 mb-2">
                                Submission Type
                            </label>
                            <select id="submissionType" name="submission_type"
                                class="w-full h-14 px-4 rounded-xl border border-red-300 bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-400">
                                <option value="accomplishment_report" @selected(old('submission_type') === 'accomplishment_report')>Accomplishment Report</option>
                                <option value="budget_report" @selected(old('submission_type') === 'budget_report')>Budget Report</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-lg font-semibold text-gray-900 mb-2">
                                Submission Title
                            </label>
                            <input type="text" id="submissionTitle" name="submission_title"
                                class="w-full h-14 px-4 rounded-xl border border-red-300 bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-400"
                                value="{{ old('submission_title') }}">
                        </div>

                        <div>
                            <label class="block text-lg font-semibold text-gray-900 mb-2">
                                Description
                            </label>
                            <input type="text" id="submissionDescription" name="description"
                                class="w-full h-14 px-4 rounded-xl border border-red-300 bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-400"
                                value="{{ old('description') }}">
                        </div>

                        <div>
                            <label class="block text-lg font-semibold text-gray-900 mb-2">
                                Who Can Submit
                            </label>
                            <select id="submissionRole" name="submission_role"
                                class="w-full h-14 px-4 rounded-xl border border-red-300 bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-400">
                                <option value="SK Chairman" @selected(old('submission_role') === 'SK Chairman')>SK Chairman</option>
                                <option value="SK Secretary" @selected(old('submission_role') === 'SK Secretary')>SK Secretary</option>
                                <option value="Both" @selected(old('submission_role') === 'Both')>SK Chairman & SK Secretary</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-lg font-semibold text-gray-900 mb-2">
                                    Start Date
                                </label>
                                <input type="date" id="startDate" name="start_date"
                                    class="w-full h-14 px-4 rounded-xl border border-red-300 bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-400"
                                    value="{{ old('start_date') }}">
                            </div>

                            <div>
                                <label class="block text-lg font-semibold text-gray-900 mb-2">
                                    End Date
                                </label>
                                <input type="date" id="endDate" name="end_date"
                                    class="w-full h-14 px-4 rounded-xl border border-red-300 bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-400"
                                    value="{{ old('end_date') }}">
                            </div>
                        </div>

                        <button type="submit"
                            class="w-full bg-red-600 hover:bg-red-700 text-white text-2xl font-bold py-4 rounded-2xl transition">
                            Create Slot
                        </button>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 mb-10">
                @foreach ($summaryCards as $card)
                    <div class="bg-white rounded-2xl border {{ $card['border'] }} p-5">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm text-gray-500 mb-2">{{ $card['label'] }}</p>
                                <h3 class="text-4xl font-bold text-gray-900 leading-none">{{ $card['value'] }}</h3>
                            </div>
                            <div class="w-12 h-12 rounded-xl {{ $card['iconBg'] }} flex items-center justify-center {{ $card['iconColor'] }} text-xl">
                                {{ $card['icon'] }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div id="slotContainer" class="grid grid-cols-1 xl:grid-cols-2 gap-5">
                @forelse ($slots as $slot)
                    <div class="bg-white rounded-2xl border border-green-400 p-6 min-h-[280px]">
                        <div class="flex justify-between items-start mb-5">
                            <div>
                                <p class="text-xs text-gray-400">{{ str_replace('_', ' ', $slot->submission_type) }}</p>
                                <h4 class="text-lg font-medium">{{ $slot->title }}</h4>
                                <p class="text-sm text-gray-400">{{ $slot->description }}</p>
                            </div>

                            <button type="button" onclick="deleteSlot({{ $slot->slot_id }})" class="text-red-500 text-xl hover:text-red-700">
                                ✕
                            </button>
                        </div>

                        <div class="text-sm text-gray-500 mb-4">
                            📅 {{ $slot->start_date }} - {{ $slot->end_date }}
                        </div>

                        <span class="text-xs bg-gray-100 px-2 py-1 rounded">
                            {{ $slot->role }}
                        </span>

                        <div class="mt-4 text-xs {{ $slot->status === 'open' ? 'text-green-600' : 'text-gray-500' }}">
                            {{ $slot->status === 'open' ? '🔓 Open' : '🔒 Closed' }}
                        </div>

                        <div class="mt-5 flex gap-2">
                            <a href="{{ route('sk_pres.module.submissions-page', $slot->slot_id) }}" class="flex-1 rounded-lg bg-blue-600 px-3 py-2 text-center text-xs font-semibold text-white hover:bg-blue-700">
                                View Barangays
                            </a>
                            <form method="POST" action="{{ route('sk_pres.module.toggle', $slot->slot_id) }}" class="flex-1">
                                @csrf
                                <button type="submit" class="w-full rounded-lg {{ $slot->status === 'open' ? 'bg-orange-500 hover:bg-orange-600' : 'bg-green-600 hover:bg-green-700' }} px-3 py-2 text-xs font-semibold text-white">
                                    {{ $slot->status === 'open' ? 'Close Slot' : 'Open Slot' }}
                                </button>
                            </form>
                        </div>

                        <form id="delete-slot-{{ $slot->slot_id }}" method="POST" action="{{ route('sk_pres.module.destroy', $slot->slot_id) }}" class="hidden">
                            @csrf
                        </form>
                    </div>
                @empty
                    <div id="emptySlotState" class="bg-white rounded-2xl border border-dashed border-gray-300 p-8 text-center text-gray-400 xl:col-span-2">
                        No submission slots yet.
                    </div>
                @endforelse
            </div>
            @else
                <div class="mx-auto max-w-6xl">
                    <a href="{{ route('sk_pres.module') }}" class="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-red-600 hover:text-red-700">
                        <span>&larr;</span> Back to Module Management
                    </a>
                    <section class="rounded-3xl bg-white p-6 shadow-sm md:p-8">
                        <div class="flex flex-col justify-between gap-5 md:flex-row md:items-start">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-widest text-red-500">Submission Slot</p>
                                <h1 class="mt-2 text-3xl font-black text-slate-900">{{ $submissionSlot->title }}</h1>
                                <p class="mt-2 text-sm text-slate-500">{{ $submissionSlot->description ?: 'Review submissions from every barangay.' }}</p>
                                <p class="mt-3 text-sm font-semibold text-slate-600">{{ str_replace('_', ' ', $submissionSlot->submission_type) }} &bull; {{ $submissionSlot->start_date }} to {{ $submissionSlot->end_date }}</p>
                            </div>
                            <span class="rounded-full px-4 py-2 text-xs font-bold uppercase {{ $submissionSlot->status === 'open' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' }}">{{ $submissionSlot->status === 'open' ? 'Open' : 'Closed' }}</span>
                        </div>
                        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <h2 class="text-xl font-black text-slate-900">Barangay Submissions</h2>
                            <input id="barangaySearch" type="search" placeholder="Search barangay..." class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-red-400 focus:ring-2 focus:ring-red-100 sm:max-w-sm">
                        </div>
                        <div id="barangayList" class="mt-5 divide-y divide-slate-100 rounded-2xl border border-slate-200">
                            @forelse ($submissions as $submission)
                                <article class="barangay-row flex flex-col gap-4 p-5 md:flex-row md:items-center md:justify-between" data-name="{{ strtolower($submission->barangay_name) }}">
                                    <div><h3 class="font-bold text-slate-800">{{ $submission->barangay_name }}</h3>
                                        @if ($submission->submitted)
                                            <p class="mt-1 text-sm font-semibold text-green-600">Submitted {{ $submission->submitted_at_label ? 'on '.$submission->submitted_at_label : '' }}</p>
                                            @if ($submission->uploaded_file_name)<p class="mt-1 text-xs text-slate-500">{{ $submission->uploaded_file_name }}</p>@endif
                                        @else
                                            <p class="mt-1 text-sm text-slate-400">No submission yet</p>
                                        @endif
                                    </div>
                                    @if ($submission->file_url)
                                        <a href="{{ $submission->file_url }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center rounded-xl bg-red-600 px-4 py-2 text-sm font-bold text-white hover:bg-red-700">View File</a>
                                    @elseif ($submission->submitted)
                                        <span class="text-xs font-semibold text-slate-400">No viewable file</span>
                                    @endif
                                </article>
                            @empty
                                <p class="p-8 text-center text-slate-400">No barangays found.</p>
                            @endforelse
                        </div>
                        <p id="noSearchResults" class="mt-5 hidden text-center text-sm text-slate-400">No barangay matches your search.</p>
                    </section>
                </div>
            @endif
        </main>
    </div>
</div>
@endsection

@push('scripts')
@vite(['resources/js/app.js'])
<script>
    const notifBtn = document.getElementById('notifBtn');
    const notifDropdown = document.getElementById('notifDropdown');
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userDropdown = document.getElementById('userDropdown');
    const openModalBtn = document.getElementById('openModalBtn');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const submissionModal = document.getElementById('submissionModal');

    notifBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        notifDropdown.classList.toggle('hidden');
        userDropdown.classList.add('hidden');
    });

    userMenuBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        userDropdown.classList.toggle('hidden');
        notifDropdown.classList.add('hidden');
    });

    document.addEventListener('click', function (e) {
        if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
            notifDropdown.classList.add('hidden');
        }

        if (!userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)) {
            userDropdown.classList.add('hidden');
        }
    });

    if (openModalBtn && closeModalBtn && submissionModal) {
        openModalBtn.addEventListener('click', function () {
            submissionModal.classList.remove('hidden');
            submissionModal.classList.add('flex');
        });

        closeModalBtn.addEventListener('click', function () {
            submissionModal.classList.add('hidden');
            submissionModal.classList.remove('flex');
        });

        submissionModal.addEventListener('click', function (e) {
            if (e.target === submissionModal) {
                submissionModal.classList.add('hidden');
                submissionModal.classList.remove('flex');
            }
        });
    }

    const barangaySearch = document.getElementById('barangaySearch');
    if (barangaySearch) {
        const barangayRows = [...document.querySelectorAll('.barangay-row')];
        const noSearchResults = document.getElementById('noSearchResults');
        barangaySearch.addEventListener('input', function () {
            const value = this.value.trim().toLowerCase();
            let visible = 0;
            barangayRows.forEach((row) => {
                const matches = row.dataset.name.includes(value);
                row.classList.toggle('hidden', !matches);
                if (matches) visible++;
            });
            noSearchResults?.classList.toggle('hidden', visible !== 0 || barangayRows.length === 0);
        });
    }

    function deleteSlot(id) {
        Swal.fire({
            title: 'Delete Slot?',
            text: 'This will be permanently removed.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Delete'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(`delete-slot-${id}`).submit();
            }
        });
    }

    async function viewSubmissions(id, title) {
        Swal.fire({ title, text: 'Loading barangay submissions...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        try {
            const response = await fetch(`/sk_pres/module/${id}/submissions`, { headers: { 'Accept': 'application/json' } });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'Unable to load submissions.');
            const rows = payload.submissions || [];
            const html = rows.length ? `<div class="max-h-96 overflow-y-auto text-left">${rows.map((row) => `
                <div class="flex items-center justify-between gap-3 border-b py-3">
                    <div><div class="font-semibold">${escapeHtml(row.barangay_name || 'Barangay')}</div>
                    <div class="text-xs ${row.submitted ? 'text-green-600' : 'text-gray-400'}">${row.submitted ? 'Submitted' : 'No submission yet'}</div></div>
                    ${row.file_url ? `<a href="${row.file_url}" target="_blank" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white">View File</a>` : ''}
                </div>`).join('')}</div>` : '<p class="text-gray-500">No barangays found.</p>';
            Swal.fire({ title, html, confirmButtonText: 'Close', width: 650 });
        } catch (error) {
            Swal.fire('Unable to load', error.message, 'error');
        }
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>\'"]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char]));
    }

    @if ($errors->any())
        submissionModal.classList.remove('hidden');
        submissionModal.classList.add('flex');
    @endif
</script>
@endpush

