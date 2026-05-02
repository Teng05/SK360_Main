{{-- File guide: Blade view template for resources/views/shared/submission-slots-page.blade.php. --}}
<div class="flex h-screen bg-gray-100 overflow-hidden">
    <div class="w-64 bg-red-600 text-white flex flex-col p-3 overflow-y-auto">
        <div class="flex items-center gap-2 mb-3">
            <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" class="w-7 h-7" alt="logo">
            <h2 class="text-base font-bold">SK 360&deg;</h2>
        </div>

        <div class="bg-red-500 rounded-lg p-2 flex items-center gap-2 mb-3 shadow text-xs">
            <div class="bg-yellow-400 text-red-600 p-1 rounded-full text-sm">👤</div>
            <div>
                <p class="font-semibold text-xs">{{ $fullName }}</p>
                <p class="text-xs opacity-80">{{ $roleLabel }}</p>
            </div>
        </div>

        <nav class="space-y-1 text-xs">
            @foreach ($menuItems as $item)
                @php $isActive = $item['link'] === $currentUrl; @endphp
                <a href="{{ $item['link'] }}" class="flex items-center gap-2 p-2 rounded-lg {{ $isActive ? 'bg-red-500 shadow-inner' : 'hover:bg-red-500 transition' }}">
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
                        <a href="{{ $profileRoute ?? '#' }}" class="flex items-center gap-3 px-5 py-3 hover:bg-gray-100 transition">
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

        <main class="flex-1 overflow-y-auto p-8 bg-gray-50">
            <div class="max-w-6xl mx-auto">
                @if (session('report_success'))
                    <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-semibold text-green-700">{{ session('report_success') }}</div>
                @endif

                @if (session('report_error'))
                    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700">{{ session('report_error') }}</div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
                        <p class="font-bold mb-2">Please fix the following:</p>
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mb-8">
                    <h1 class="text-3xl font-black text-gray-800 uppercase tracking-tighter">{{ $pageTitle }}</h1>
                    <p class="text-gray-500 font-medium italic">{{ $pageDescription }}</p>
                </div>

                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 mb-8">
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h2 class="text-lg font-black text-gray-800 uppercase tracking-tight">{{ $slotSectionTitle }}</h2>
                            <p class="text-xs text-gray-400">Only active slots created by the SK President can accept submissions.</p>
                        </div>
                        <span class="rounded-full bg-red-50 px-4 py-2 text-xs font-black uppercase text-red-600">{{ $slots->count() }} Active</span>
                    </div>

                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
                        @forelse ($slots as $slot)
                            <div class="rounded-2xl border border-red-100 bg-red-50/30 p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-[10px] font-black uppercase tracking-widest text-red-400">{{ str_replace('_', ' ', $slot->submission_type) }}</p>
                                        <h3 class="text-lg font-bold text-gray-800">{{ $slot->title }}</h3>
                                        <p class="text-sm text-gray-500 mt-1">{{ $slot->description ?: 'No description provided.' }}</p>
                                    </div>
                                    <div class="flex flex-col items-end gap-2">
                                        <span class="rounded-full bg-white px-3 py-1 text-[10px] font-black uppercase text-red-600">{{ $slot->role }}</span>
                                        <span class="rounded-full px-3 py-1 text-[10px] font-black uppercase {{ $slot->slot_status_badge ?? 'bg-red-100 text-red-600' }}">{{ $slot->slot_status_label ?? 'Open' }}</span>
                                    </div>
                                </div>
                                <div class="mt-4 grid grid-cols-2 gap-3 text-xs text-gray-600">
                                    <div class="rounded-xl bg-white px-3 py-3">
                                        <p class="font-black uppercase text-gray-400">Start</p>
                                        <p class="mt-1 font-semibold">{{ \Carbon\Carbon::parse($slot->start_date)->format('M d, Y') }}</p>
                                    </div>
                                    <div class="rounded-xl bg-white px-3 py-3">
                                        <p class="font-black uppercase text-gray-400">End</p>
                                        <p class="mt-1 font-semibold">{{ \Carbon\Carbon::parse($slot->end_date)->format('M d, Y') }}</p>
                                    </div>
                                </div>
                                <div class="mt-4 flex justify-end">
                                    @if (!empty($slot->has_submitted) && empty($allowResubmission))
                                        <button type="button" class="rounded-xl bg-green-600 px-4 py-3 text-xs font-black uppercase text-white cursor-default">
                                            Submitted
                                        </button>
                                    @else
                                        <button type="button" class="rounded-xl {{ !empty($slot->has_submitted) ? 'bg-amber-500 hover:bg-amber-600' : 'bg-red-600 hover:bg-red-700' }} px-4 py-3 text-xs font-black uppercase text-white" onclick="openSlotSubmission({{ $slot->slot_id }}, @js($slot->title))">
                                            {{ !empty($slot->has_submitted) ? (($submissionType ?? '') === 'report' ? 'Resubmit Report File' : 'Resubmit Budget File') : $slotActionLabel }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="xl:col-span-2 rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-6 py-12 text-center text-sm text-gray-400">{{ $slotEmptyMessage }}</div>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-8 py-5 border-b border-gray-50 bg-gray-50/50">
                        <h3 class="font-black text-gray-800 uppercase tracking-tighter">Recent Submissions</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="text-[10px] text-gray-400 uppercase font-black tracking-widest border-b bg-gray-50">
                                <tr>
                                    <th class="px-8 py-4">Title</th>
                                    <th class="px-6 py-4">Method</th>
                                    <th class="px-6 py-4">Date Submitted</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-8 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm text-gray-600">
                                @forelse ($submissions as $submission)
                                    <tr class="hover:bg-gray-50 transition border-b border-gray-50">
                                        <td class="px-8 py-5">
                                            <div class="font-bold text-gray-800 uppercase tracking-tighter">{{ $submission->title ?? $submission->report_title }}</div>
                                            <div class="text-[10px] text-gray-400 font-bold uppercase mt-1">{{ $submission->period_label ?? optional($submission->submitted_at)->format('F Y') ?? 'Submission' }}</div>
                                        </td>
                                        <td class="px-6 py-5">
                                            <span class="{{ $submission->method_badge }} px-3 py-1 rounded-full text-[9px] font-black uppercase">{{ $submission->method_label }}</span>
                                        </td>
                                        <td class="px-6 py-5 text-xs font-semibold">{{ optional($submission->submitted_at)->format('M d, Y') ?? '--' }}</td>
                                        <td class="px-6 py-5">
                                            <span class="{{ $submission->status_badge }} px-3 py-1 rounded-full text-[9px] font-black uppercase">{{ $submission->status }}</span>
                                        </td>
                                        <td class="px-8 py-5 text-right space-x-2">
                                            @if (!empty($submission->view_url))
                                                <a href="{{ $submission->view_url }}" target="_blank" class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-gray-100 text-gray-500 hover:bg-blue-100 hover:text-blue-600 transition shadow-sm">V</a>
                                            @else
                                                <button type="button" onclick="window.alert('No preview is available for this submission.')" class="w-8 h-8 rounded-xl bg-gray-100 text-gray-500 hover:bg-blue-100 hover:text-blue-600 transition shadow-sm">V</button>
                                            @endif
                                            @if ($submission->download_url)
                                                <a href="{{ $submission->download_url }}" target="_blank" class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-gray-100 text-gray-500 hover:bg-green-100 hover:text-green-600 transition shadow-sm">D</a>
                                            @else
                                                <button type="button" onclick="window.alert('{{ ($submissionType ?? '') === 'report' ? 'No PDF file is available for this report.' : 'This slot used the system template.' }}')" class="w-8 h-8 rounded-xl bg-gray-100 text-gray-500 hover:bg-green-100 hover:text-green-600 transition shadow-sm">D</button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-8 py-12 text-center text-sm text-gray-400">No submissions yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<div id="slotSubmissionModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden">
        <div class="bg-red-600 px-6 py-5 text-white flex justify-between items-center">
            <div>
                <h3 class="font-black uppercase tracking-tighter">Submit to Slot</h3>
                <p id="slotSubmissionTitle" class="text-xs text-red-100 mt-1"></p>
            </div>
            <button type="button" onclick="closeSlotSubmission()" class="text-white">X</button>
        </div>

        <form action="{{ $storeRoute }}" method="POST" enctype="multipart/form-data" class="p-8 space-y-6">
            @csrf
            <input type="hidden" name="slot_id" id="slotIdField" value="{{ old('slot_id') }}">

            @if (($submissionType ?? '') === 'budget')
                <div class="grid grid-cols-1 gap-4 rounded-2xl border border-gray-100 bg-gray-50 p-4">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 ml-1">Submission Period</label>
                        <select id="reportTypeField" name="report_type" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-xs font-bold text-gray-700 outline-none focus:ring-2 focus:ring-red-300">
                            <option value="monthly" {{ old('report_type', 'monthly') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                            <option value="quarterly" {{ old('report_type') === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                            <option value="annual" {{ old('report_type') === 'annual' ? 'selected' : '' }}>Annual</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 ml-1">Year</label>
                            <input type="number" name="reporting_year" value="{{ old('reporting_year', now()->year) }}" min="2000" max="2100" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-xs font-bold text-gray-700 outline-none focus:ring-2 focus:ring-red-300">
                        </div>

                        <div id="reportMonthWrap">
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 ml-1">Month</label>
                            <select name="reporting_month" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-xs font-bold text-gray-700 outline-none focus:ring-2 focus:ring-red-300">
                                @foreach ([1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'] as $number => $month)
                                    <option value="{{ $number }}" {{ (int) old('reporting_month', now()->month) === $number ? 'selected' : '' }}>{{ $month }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="reportQuarterWrap" class="hidden">
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 ml-1">Quarter</label>
                            <select name="reporting_quarter" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-xs font-bold text-gray-700 outline-none focus:ring-2 focus:ring-red-300">
                                @foreach (['Q1', 'Q2', 'Q3', 'Q4'] as $quarter)
                                    <option value="{{ $quarter }}" {{ old('reporting_quarter') === $quarter ? 'selected' : '' }}>{{ $quarter }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            @endif

            @if (($submissionType ?? '') === 'report')
                <input type="hidden" name="sub_method" value="pdf">
            @else
                <div class="grid grid-cols-2 gap-3">
                    <label data-submission-mode="template" class="slot-mode-label text-center p-3 rounded-xl border-2 border-gray-100 bg-gray-50 cursor-pointer text-[10px] font-black uppercase transition">
                        <input type="radio" name="sub_method" value="template" class="hidden" {{ old('sub_method', 'template') === 'template' ? 'checked' : '' }}>
                        System Template
                    </label>
                    <label data-submission-mode="pdf" class="slot-mode-label text-center p-3 rounded-xl border-2 border-gray-100 bg-gray-50 cursor-pointer text-[10px] font-black uppercase transition">
                        <input type="radio" name="sub_method" value="pdf" class="hidden" {{ old('sub_method') === 'pdf' ? 'checked' : '' }}>
                        PDF Upload
                    </label>
                </div>
            @endif

            <div id="slotFileSection" class="{{ ($submissionType ?? '') === 'report' || old('sub_method') === 'pdf' ? '' : 'hidden' }}">
                <label class="block text-[10px] font-black text-red-500 uppercase mb-2 ml-1">Select PDF File</label>
                <div class="border-2 border-dashed border-red-200 bg-red-50 p-4 rounded-xl text-center relative hover:bg-red-100 transition cursor-pointer">
                    <input type="file" name="report_file" accept=".pdf" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    <p class="text-[10px] font-black text-red-600 uppercase">Click or drag PDF here</p>
                    <p id="slotFileName" class="mt-2 text-xs text-red-500 font-semibold"></p>
                </div>
            </div>

            <button id="slotSubmitButton" type="submit" class="w-full bg-red-600 text-white py-4 rounded-2xl font-black uppercase tracking-tighter shadow-lg hover:bg-red-700 active:scale-95 transition">Submit Slot</button>
        </form>
    </div>
</div>
@push('scripts')
<script>
    const notifBtn = document.getElementById('notifBtn');
    const notifDropdown = document.getElementById('notifDropdown');
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userDropdown = document.getElementById('userDropdown');
    const reportTypeField = document.getElementById('reportTypeField');
    const reportMonthWrap = document.getElementById('reportMonthWrap');
    const reportQuarterWrap = document.getElementById('reportQuarterWrap');

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

    function syncReportPeriodFields() {
        if (!reportTypeField || !reportMonthWrap || !reportQuarterWrap) {
            return;
        }

        reportMonthWrap.classList.toggle('hidden', reportTypeField.value !== 'monthly');
        reportQuarterWrap.classList.toggle('hidden', reportTypeField.value !== 'quarterly');
    }

    reportTypeField?.addEventListener('change', syncReportPeriodFields);
    syncReportPeriodFields();
</script>
@endpush
