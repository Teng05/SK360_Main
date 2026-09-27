{{-- File guide: Blade view template for resources/views/shared/submission-slots-page.blade.php. --}}
<div class="flex h-screen bg-gray-100 overflow-hidden">
    {{-- SIDEBAR --}}
    <div class="w-64 bg-red-600 text-white flex flex-col p-3 overflow-y-auto">
        <div class="flex items-center gap-3 mb-4">
            <img
                src="{{ asset('images/logo.png') }}"
                class="w-8 h-8 rounded-full object-cover"
                alt="logo"
            >
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
                👤
            </div>
            <div>
                <p class="font-semibold text-xs">
                    {{ $fullName }}
                </p>
                <p class="text-xs opacity-80">
                    {{ $roleLabel }}
                </p>
            </div>
        </div>
        <nav class="space-y-1 text-xs">
            @foreach($menuItems as $item)
                @php
                    $isActive=$item['link']===$currentUrl;
                @endphp
                <a
                    href="{{ $item['link'] }}"
                    class="flex items-center gap-2 p-2 rounded-lg {{ $isActive ? 'bg-red-500 shadow-inner' : 'hover:bg-red-500 transition' }}"
                >
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
    {{-- MAIN --}}
    <div class="flex-1 flex flex-col overflow-hidden">
        {{-- TOPBAR --}}
        <div class="bg-red-600 text-white px-6 py-3 flex justify-between items-center shadow">
            <input
                id="submissionSearch"
                type="text"
                placeholder="Search..."
                class="px-4 py-2 rounded-full text-black w-1/3 focus:outline-none"
                autocomplete="off"
            >
            <div class="flex items-center gap-3 relative">
                {{-- NOTIFICATIONS --}}
                <div class="relative">
                    <button
                        id="notifBtn"
                        type="button"
                        class="text-xl hover:bg-red-500 p-2 rounded-lg transition"
                    >
                        🔔
                    </button>
                    <div
                        id="notifDropdown"
                        class="hidden absolute right-0 mt-3 w-72 bg-white rounded-2xl shadow-xl border z-50 overflow-hidden"
                    >
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
                {{-- USER MENU --}}
                <div class="relative">
                    <button
                        id="userMenuBtn"
                        type="button"
                        class="flex items-center gap-2 hover:bg-red-500 px-3 py-2 rounded-lg transition"
                    >
                        <span class="font-semibold">
                            {{ $fullName }}
                        </span>
                    </button>
                    <div
                        id="userDropdown"
                        class="hidden absolute right-0 mt-3 w-64 bg-white rounded-2xl shadow-xl border overflow-hidden z-50"
                    >
                        <div class="px-5 py-4 font-semibold text-gray-800 border-b">
                            My Account
                        </div>
                        <a
                            href="{{ $profileRoute ?? '#' }}"
                            class="flex items-center gap-3 px-5 py-3 hover:bg-gray-100 transition"
                        >
                            <span>👤</span>
                            <span class="text-gray-700">
                                Profile Settings
                            </span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button
                                type="submit"
                                class="w-full text-left flex items-center gap-3 px-5 py-3 text-red-500 hover:bg-gray-100 transition"
                            >
                                <span>↩️</span>
                                <span>Log Out</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        {{-- CONTENT --}}
        <main class="flex-1 overflow-y-auto p-8 bg-gray-50">
            <div class="max-w-6xl mx-auto">
                {{-- SUCCESS --}}
                @if(session('report_success'))
                    <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-semibold text-green-700">
                        {{ session('report_success') }}
                    </div>
                @endif
                {{-- ERROR --}}
                @if(session('report_error'))
                    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700">
                        {{ session('report_error') }}
                    </div>
                @endif
                {{-- VALIDATION ERRORS --}}
                @if($errors->any())
                    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
                        <p class="font-bold mb-2">
                            Please fix the following:
                        </p>
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                {{-- PAGE TITLE --}}
                <div class="mb-8">
                    <h1 class="text-3xl font-black text-gray-800 uppercase tracking-tighter">
                        {{ $pageTitle }}
                    </h1>
                    <p class="text-gray-500 font-medium italic">
                        {{ $pageDescription }}
                    </p>
                </div>
                {{-- BUDGET FILTER --}}
                @if(($submissionType ?? '')==='budget')
                    <form
                        id="budgetFilterForm"
                        method="GET"
                        action="{{ url()->current() }}"
                        class="mb-8 rounded-3xl border border-gray-100 bg-white p-5 shadow-sm"
                    >
                        <div class="mb-4">
                            <h2 class="text-sm font-black uppercase tracking-tight text-gray-800">
                                Filter Budget Records
                            </h2>
                            <p class="mt-1 text-xs text-gray-400">
                                Show active slots and submitted records for the selected reporting period.
                            </p>
                        </div>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label class="mb-1.5 block text-[10px] font-black uppercase tracking-widest text-gray-400">
                                    Reporting Period
                                </label>
                                <select
                                    id="budgetPeriodFilter"
                                    name="period"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 outline-none focus:ring-2 focus:ring-red-200"
                                >
                                    <option value="all" {{ ($budgetFilters['period'] ?? 'all')==='all' ? 'selected' : '' }}>
                                        All Records
                                    </option>
                                    <option value="monthly" {{ ($budgetFilters['period'] ?? '')==='monthly' ? 'selected' : '' }}>
                                        Monthly
                                    </option>
                                    <option value="quarterly" {{ ($budgetFilters['period'] ?? '')==='quarterly' ? 'selected' : '' }}>
                                        Quarterly
                                    </option>
                                    <option value="semi_annual" {{ ($budgetFilters['period'] ?? '')==='semi_annual' ? 'selected' : '' }}>
                                        Semi-Annual
                                    </option>
                                    <option value="annual" {{ ($budgetFilters['period'] ?? '')==='annual' ? 'selected' : '' }}>
                                        Annual
                                    </option>
                                </select>
                            </div>
                            <div
                                id="budgetMonthFilterWrap"
                                class="{{ ($budgetFilters['period'] ?? '')==='monthly' ? '' : 'hidden' }}"
                            >
                                <label class="mb-1.5 block text-[10px] font-black uppercase tracking-widest text-gray-400">
                                    Month
                                </label>
                                <select
                                    id="budgetMonthFilter"
                                    name="month"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 outline-none focus:ring-2 focus:ring-red-200"
                                    @disabled(($budgetFilters['period'] ?? '')!=='monthly')
                                >
                                    @foreach($budgetMonths as $number=>$month)
                                        <option
                                            value="{{ $number }}"
                                            {{ (int)($budgetFilters['month'] ?? 0)===(int)$number ? 'selected' : '' }}
                                        >
                                            {{ $month }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div
                                id="budgetQuarterFilterWrap"
                                class="{{ ($budgetFilters['period'] ?? '')==='quarterly' ? '' : 'hidden' }}"
                            >
                                <label class="mb-1.5 block text-[10px] font-black uppercase tracking-widest text-gray-400">
                                    Quarter
                                </label>
                                <select
                                    id="budgetQuarterFilter"
                                    name="quarter"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 outline-none focus:ring-2 focus:ring-red-200"
                                    @disabled(($budgetFilters['period'] ?? '')!=='quarterly')
                                >
                                    @foreach($budgetQuarters as $quarter)
                                        <option
                                            value="{{ $quarter }}"
                                            {{ ($budgetFilters['quarter'] ?? '')===$quarter ? 'selected' : '' }}
                                        >
                                            {{ $quarter }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div
                                id="budgetHalfFilterWrap"
                                class="{{ ($budgetFilters['period'] ?? '')==='semi_annual' ? '' : 'hidden' }}"
                            >
                                <label class="mb-1.5 block text-[10px] font-black uppercase tracking-widest text-gray-400">
                                    Half
                                </label>
                                <select
                                    id="budgetHalfFilter"
                                    name="half"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 outline-none focus:ring-2 focus:ring-red-200"
                                    @disabled(($budgetFilters['period'] ?? '')!=='semi_annual')
                                >
                                    @foreach($budgetHalves as $value=>$label)
                                        <option
                                            value="{{ $value }}"
                                            {{ ($budgetFilters['half'] ?? '')===$value ? 'selected' : '' }}
                                        >
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                            <p id="budgetFilterHelp" class="text-[10px] text-gray-400">
                                @if(($budgetFilters['period'] ?? 'all')==='annual')
                                    Annual records do not use a month or quarter filter.
                                @elseif(($budgetFilters['period'] ?? 'all')==='monthly')
                                    Showing the selected month only.
                                @elseif(($budgetFilters['period'] ?? 'all')==='quarterly')
                                    Showing the selected quarter only.
                                @elseif(($budgetFilters['period'] ?? 'all')==='semi_annual')
                                    Showing the selected half of the year only.
                                @else
                                    Showing all available budget records.
                                @endif
                            </p>
                            <div class="flex gap-2">
                                <a
                                    href="{{ url()->current() }}"
                                    class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-bold text-gray-500 hover:bg-gray-50 transition"
                                >
                                    Reset
                                </a>
                                <button
                                    type="submit"
                                    class="rounded-xl bg-red-600 px-5 py-2.5 text-xs font-black uppercase text-white hover:bg-red-700 transition"
                                >
                                    Apply Filter
                                </button>
                            </div>
                        </div>
                    </form>
                @endif
                @php
                    $focusId=(int)request()->query('focus_id',0);
                    $focusSlot=(int)request()->query('focus_slot',0);
                @endphp
                {{-- ACTIVE SLOTS --}}
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 mb-8">
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h2 class="text-lg font-black text-gray-800 uppercase tracking-tight">
                                {{ $slotSectionTitle }}
                            </h2>
                            <p class="text-xs text-gray-400">
                                Only active slots created by the SK President can accept submissions.
                            </p>
                        </div>
                        <span class="rounded-full bg-red-50 px-4 py-2 text-xs font-black uppercase text-red-600">
                            {{ $slots->count() }} Active
                        </span>
                    </div>
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
                        @forelse($slots as $slot)
                            @php
                                $slotSubmission=collect($submissions)->first(
                                    fn($submission)=>(int)($submission->slot_id ?? 0)===(int)$slot->slot_id
                                );
                                $hasSubmission=!empty($slot->has_submitted) || $slotSubmission;
                                $qualityStatus=strtolower(
                                    (string)($slotSubmission->quality_status ?? 'pending')
                                );
                                $qualityRemarks=trim(
                                    (string)($slotSubmission->quality_remarks ?? '')
                                );
                                $canResubmit=$hasSubmission
                                    && $qualityStatus==='needs_revision'
                                    && !empty($allowResubmission);
                                $slotSubmissionId=($submissionType ?? '')==='budget'
                                    ? (int)($slotSubmission->budget_report_id ?? 0)
                                    : (int)($slotSubmission->report_id ?? 0);
                                $isFocusedSlot=$focusSlot>0
                                    && (int)$slot->slot_id===$focusSlot;
                                $isFocusedSubmissionSlot=$focusId>0
                                    && $slotSubmissionId===$focusId;
                                $isFocusedCard=$isFocusedSlot || $isFocusedSubmissionSlot;
                            @endphp
                            <div
                                id="submission-slot-{{ $slot->slot_id }}"
                                class="submission-slot-card rounded-2xl border p-5 transition-all duration-500 {{ $isFocusedCard ? 'border-yellow-400 bg-yellow-50 ring-4 ring-yellow-200 shadow-lg' : 'border-red-100 bg-red-50/30' }}"
                                data-focus-slot-card="{{ $isFocusedCard ? '1' : '0' }}"
                                data-search="{{ strtolower(($slot->title ?? '').' '.($slot->description ?? '').' '.($slot->role ?? '').' '.($slot->budget_category_label ?? '').' '.($slot->budget_period_label ?? '').' '.($slot->accomplishment_category_label ?? '')) }}"
                            >
                                @if($isFocusedCard)
                                    <div class="mb-4 flex items-center gap-2 rounded-xl border border-yellow-200 bg-yellow-100 px-4 py-3 text-xs font-bold text-yellow-800">
                                        <span>🔔</span>
                                        <span>
                                            {{ $isFocusedSubmissionSlot
                                                ? 'This is the submission from the notification you opened.'
                                                : 'This is the submission slot from the notification you opened.' }}
                                        </span>
                                    </div>
                                @endif
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-[10px] font-black uppercase tracking-widest text-red-400">
                                            {{ str_replace('_',' ',$slot->submission_type) }}
                                        </p>
                                        <h3 class="text-lg font-bold text-gray-800">
                                            {{ $slot->title }}
                                        </h3>
                                        <p class="text-sm text-gray-500 mt-1">
                                            {{ $slot->description ?: 'No description provided.' }}
                                        </p>
                                    </div>
                                    <div class="flex flex-col items-end gap-2">
                                        <span class="rounded-full bg-white px-3 py-1 text-[10px] font-black uppercase text-red-600">
                                            {{ $slot->role }}
                                        </span>
                                        <span class="rounded-full px-3 py-1 text-[10px] font-black uppercase {{ $slot->slot_status_badge ?? 'bg-red-100 text-red-600' }}">
                                            {{ $slot->slot_status_label ?? 'Open' }}
                                        </span>
                                    </div>
                                </div>
                                {{-- ACCOMPLISHMENT METADATA --}}
                                @if(($submissionType ?? '')==='report')
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <span class="rounded-full bg-blue-50 px-3 py-1 text-[10px] font-black uppercase text-blue-600">
                                            {{ $slot->accomplishment_category_label ?? 'Accomplishment Report' }}
                                        </span>
                                        @if(($slot->accomplishment_category ?? null)==='youth_development_program' && !empty($slot->ydp_program_type_label))
                                            <span class="rounded-full bg-green-50 px-3 py-1 text-[10px] font-black uppercase text-green-600">
                                                {{ $slot->ydp_program_type_label }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                                {{-- BUDGET METADATA --}}
                                @if(($submissionType ?? '')==='budget')
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <span class="rounded-full bg-blue-50 px-3 py-1 text-[10px] font-black uppercase text-blue-600">
                                            {{ $slot->budget_category_label ?? 'Budget / Financial Report' }}
                                        </span>
                                        @if(!empty($slot->fiscal_year))
                                            <span class="rounded-full bg-purple-50 px-3 py-1 text-[10px] font-black uppercase text-purple-600">
                                                FY {{ $slot->fiscal_year }}
                                            </span>
                                        @endif
                                        @if(!empty($slot->budget_period_label))
                                            <span class="rounded-full bg-amber-50 px-3 py-1 text-[10px] font-black uppercase text-amber-600">
                                                {{ $slot->budget_period_label }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                                {{-- DATES --}}
                                <div class="mt-4 grid grid-cols-2 gap-3 text-xs text-gray-600">
                                    <div class="rounded-xl bg-white px-3 py-3">
                                        <p class="font-black uppercase text-gray-400">
                                            Start
                                        </p>
                                        <p class="mt-1 font-semibold">
                                            {{ \Carbon\Carbon::parse($slot->start_date)->format('M d, Y') }}
                                        </p>
                                    </div>
                                    <div class="rounded-xl bg-white px-3 py-3">
                                        <p class="font-black uppercase text-gray-400">
                                            Deadline
                                        </p>
                                        <p class="mt-1 font-semibold">
                                            {{ \Carbon\Carbon::parse($slot->end_date)->format('M d, Y') }}
                                        </p>
                                    </div>
                                </div>
                                {{-- QUALITY REVIEW STATUS --}}
                                @if($hasSubmission)
                                    <div class="mt-4 rounded-xl border px-4 py-3 {{ $qualityStatus==='approved' ? 'border-green-200 bg-green-50' : ($qualityStatus==='needs_revision' ? 'border-red-200 bg-red-50' : 'border-amber-200 bg-amber-50') }}">
                                        <div class="flex items-center justify-between gap-3">
                                            <p class="text-[10px] font-black uppercase {{ $qualityStatus==='approved' ? 'text-green-700' : ($qualityStatus==='needs_revision' ? 'text-red-700' : 'text-amber-700') }}">
                                                {{ $qualityStatus==='approved'
                                                    ? 'Approved'
                                                    : ($qualityStatus==='needs_revision'
                                                        ? 'Needs Revision'
                                                        : 'Pending Review') }}
                                            </p>
                                            @if($qualityStatus==='approved')
                                                <span class="text-[10px] font-bold text-green-600">
                                                    Locked
                                                </span>
                                            @endif
                                        </div>
                                        @if($qualityStatus==='needs_revision' && $qualityRemarks!=='')
                                            <p class="mt-2 text-xs leading-relaxed text-red-700">
                                                <span class="font-black">
                                                    President Remarks:
                                                </span>
                                                {{ $qualityRemarks }}
                                            </p>
                                        @elseif($qualityStatus==='pending')
                                            <p class="mt-2 text-xs leading-relaxed text-amber-700">
                                                Your submission is waiting for Quality Documentation review.
                                            </p>
                                        @elseif($qualityStatus==='approved')
                                            <p class="mt-2 text-xs leading-relaxed text-green-700">
                                                This submission has been approved and can no longer be replaced.
                                            </p>
                                        @endif
                                    </div>
                                @endif
                                {{-- SLOT ACTION --}}
                                <div class="mt-4 flex justify-end">
                                    @if(!empty($slot->is_upcoming))
                                        <button
                                            type="button"
                                            disabled
                                            class="rounded-xl bg-gray-300 px-4 py-3 text-xs font-black uppercase text-gray-500 cursor-not-allowed"
                                        >
                                            Opens {{ \Carbon\Carbon::parse($slot->start_date)->format('M d, Y') }}
                                        </button>
                                    @elseif($hasSubmission && $qualityStatus==='approved')
                                        <button
                                            type="button"
                                            disabled
                                            class="rounded-xl bg-green-600 px-4 py-3 text-xs font-black uppercase text-white cursor-not-allowed"
                                        >
                                            Approved / Locked
                                        </button>
                                    @elseif($hasSubmission && $qualityStatus==='pending')
                                        <button
                                            type="button"
                                            disabled
                                            class="rounded-xl bg-amber-100 px-4 py-3 text-xs font-black uppercase text-amber-700 cursor-not-allowed"
                                        >
                                            Pending Review
                                        </button>
                                    @elseif($canResubmit)
                                        <button
                                            type="button"
                                            class="rounded-xl bg-red-600 hover:bg-red-700 px-4 py-3 text-xs font-black uppercase text-white"
                                            @if(($submissionType ?? '')==='report')
                                                onclick="openAccomplishmentSlotSubmission(
                                                    {{ $slot->slot_id }},
                                                    @js($slot->title),
                                                    @js($slot->accomplishment_category_label ?? 'Accomplishment Report'),
                                                    @js($slot->ydp_program_type_label ?? null)
                                                )"
                                            @else
                                                onclick="openSlotSubmission(
                                                    {{ $slot->slot_id }},
                                                    @js($slot->title),
                                                    @js($slot->budget_category ?? null),
                                                    @js($slot->fiscal_year ?? null),
                                                    @js($slot->budget_period_type ?? null),
                                                    @js($slot->fiscal_month ?? null),
                                                    @js($slot->fiscal_quarter ?? null),
                                                    @js($slot->fiscal_half ?? null),
                                                    @js($slot->template_available ?? false)
                                                )"
                                            @endif
                                        >
                                            {{ ($submissionType ?? '')==='report'
                                                ? 'Resubmit Report File'
                                                : 'Resubmit Budget File' }}
                                        </button>
                                    @elseif($hasSubmission)
                                        <button
                                            type="button"
                                            disabled
                                            class="rounded-xl bg-gray-300 px-4 py-3 text-xs font-black uppercase text-gray-500 cursor-not-allowed"
                                        >
                                            Submitted
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            class="rounded-xl bg-red-600 hover:bg-red-700 px-4 py-3 text-xs font-black uppercase text-white"
                                            @if(($submissionType ?? '')==='report')
                                                onclick="openAccomplishmentSlotSubmission(
                                                    {{ $slot->slot_id }},
                                                    @js($slot->title),
                                                    @js($slot->accomplishment_category_label ?? 'Accomplishment Report'),
                                                    @js($slot->ydp_program_type_label ?? null)
                                                )"
                                            @else
                                                onclick="openSlotSubmission(
                                                    {{ $slot->slot_id }},
                                                    @js($slot->title),
                                                    @js($slot->budget_category ?? null),
                                                    @js($slot->fiscal_year ?? null),
                                                    @js($slot->budget_period_type ?? null),
                                                    @js($slot->fiscal_month ?? null),
                                                    @js($slot->fiscal_quarter ?? null),
                                                    @js($slot->fiscal_half ?? null),
                                                    @js($slot->template_available ?? false)
                                                )"
                                            @endif
                                        >
                                            {{ $slotActionLabel }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="xl:col-span-2 rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-6 py-12 text-center text-sm text-gray-400">
                                {{ $slotEmptyMessage }}
                            </div>
                        @endforelse
                    </div>
                </div>
                {{-- RECENT SUBMISSIONS --}}
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-8 py-5 border-b border-gray-50 bg-gray-50/50">
                        <h3 class="font-black text-gray-800 uppercase tracking-tighter">
                            Recent Submissions
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="text-[10px] text-gray-400 uppercase font-black tracking-widest border-b bg-gray-50">
                                <tr>
                                    <th class="px-8 py-4">
                                        Title
                                    </th>
                                    <th class="px-6 py-4">
                                        Method
                                    </th>
                                    <th class="px-6 py-4">
                                        Date Submitted
                                    </th>
                                    <th class="px-6 py-4">
                                        Quality Review
                                    </th>
                                    <th class="px-8 py-4 text-right">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="text-sm text-gray-600">
                                @forelse($submissions as $submission)
                                    @php
                                        $submissionId=($submissionType ?? '')==='budget'
                                            ? (int)($submission->budget_report_id ?? 0)
                                            : (int)($submission->report_id ?? 0);
                                        $isFocusedSubmission=$focusId>0
                                            && $submissionId===$focusId;
                                    @endphp
                                    <tr
                                        id="submission-{{ $submissionId }}"
                                        class="submission-record-row transition border-b {{ $isFocusedSubmission ? 'border-yellow-300 bg-yellow-50 ring-2 ring-inset ring-yellow-200' : 'border-gray-50 hover:bg-gray-50' }}"
                                        data-focus-submission="{{ $isFocusedSubmission ? '1' : '0' }}"
                                        data-search="{{ strtolower(($submission->title ?? $submission->report_title ?? '').' '.($submission->period_label ?? '').' '.($submission->method_label ?? '').' '.($submission->quality_status_label ?? '')) }}"
                                    >
                                        <td class="px-8 py-5">
                                            <div class="font-bold text-gray-800 uppercase tracking-tighter">
                                                {{ $submission->title ?? $submission->report_title }}
                                            </div>
                                            @if($isFocusedSubmission)
                                                <div class="mt-2 inline-flex items-center gap-1 rounded-full bg-yellow-100 px-2 py-1 text-[9px] font-black uppercase text-yellow-700">
                                                    <span>🔔</span>
                                                    Opened from notification
                                                </div>
                                            @endif
                                            <div class="text-[10px] text-gray-400 font-bold uppercase mt-1">
                                                {{ $submission->period_label ?? optional($submission->submitted_at)->format('F Y') ?? 'Submission' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-5">
                                            <span class="{{ $submission->method_badge }} px-3 py-1 rounded-full text-[9px] font-black uppercase">
                                                {{ $submission->method_label }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-5 text-xs font-semibold">
                                            {{ optional($submission->submitted_at)->format('M d, Y') ?? '--' }}
                                        </td>
                                        <td class="px-6 py-5">
                                            @php
                                                $submissionQualityStatus=strtolower(
                                                    (string)($submission->quality_status ?? 'pending')
                                                );
                                                $submissionQualityLabel=
                                                    $submission->quality_status_label
                                                    ?? (
                                                        $submissionQualityStatus==='approved'
                                                            ? 'Approved'
                                                            : (
                                                                $submissionQualityStatus==='needs_revision'
                                                                    ? 'Needs Revision'
                                                                    : 'Pending Review'
                                                            )
                                                    );
                                                $submissionQualityBadge=
                                                    $submission->quality_status_badge
                                                    ?? (
                                                        $submissionQualityStatus==='approved'
                                                            ? 'bg-green-100 text-green-700'
                                                            : (
                                                                $submissionQualityStatus==='needs_revision'
                                                                    ? 'bg-red-100 text-red-700'
                                                                    : 'bg-amber-100 text-amber-700'
                                                            )
                                                    );
                                            @endphp
                                            <span class="{{ $submissionQualityBadge }} px-3 py-1 rounded-full text-[9px] font-black uppercase">
                                                {{ $submissionQualityLabel }}
                                            </span>
                                            @if(
                                                $submissionQualityStatus==='needs_revision'
                                                && !empty($submission->quality_remarks)
                                            )
                                                <p class="mt-2 max-w-xs text-[10px] leading-relaxed text-red-600">
                                                    <span class="font-black">
                                                        Remarks:
                                                    </span>
                                                    {{ $submission->quality_remarks }}
                                                </p>
                                            @endif
                                        </td>
                                        <td class="px-8 py-5 text-right space-x-2">
                                            @if(!empty($submission->view_url))
                                                <a
                                                    href="{{ $submission->view_url }}"
                                                    target="_blank"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-gray-100 text-gray-500 hover:bg-blue-100 hover:text-blue-600 transition shadow-sm"
                                                    title="View submission"
                                                >
                                                    <svg
                                                        class="h-4 w-4"
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="2"
                                                    >
                                                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path>
                                                        <circle cx="12" cy="12" r="3"></circle>
                                                    </svg>
                                                </a>
                                            @endif
                                            @if(!empty($submission->download_url))
                                                <a
                                                    href="{{ $submission->download_url }}"
                                                    target="_blank"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-gray-100 text-gray-500 hover:bg-green-100 hover:text-green-600 transition shadow-sm"
                                                    title="Download submission"
                                                >
                                                    <svg
                                                        class="h-4 w-4"
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="2"
                                                    >
                                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                        <path d="M7 10l5 5 5-5"></path>
                                                        <path d="M12 15V3"></path>
                                                    </svg>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td
                                            colspan="5"
                                            class="px-8 py-12 text-center text-sm text-gray-400"
                                        >
                                            No submissions yet.
                                        </td>
                                    </tr>
                                @endforelse
                                <tr id="submissionSearchEmpty" class="hidden">
                                    <td colspan="5" class="px-8 py-10 text-center text-sm text-gray-400">
                                        No records matched your search.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
{{-- SUBMISSION MODAL --}}
<div
    id="slotSubmissionModal"
    class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4"
>
    <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden">
        <div class="bg-red-600 px-6 py-5 text-white flex justify-between items-center">
            <div>
                <h3 class="font-black uppercase tracking-tighter">
                    Submit to Slot
                </h3>
                <p id="slotSubmissionTitle" class="text-xs text-red-100 mt-1"></p>
            </div>
            <button
                type="button"
                onclick="closeSlotSubmission()"
                class="text-white"
            >
                X
            </button>
        </div>
        <form
            id="slotSubmissionForm"
            action="{{ $storeRoute }}"
            method="POST"
            enctype="multipart/form-data"
            class="p-8 space-y-6"
            novalidate
        >
            @csrf
            <div
                id="slotFormWarning"
                class="hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
            >
                <p class="font-black">
                    Incomplete fields
                </p>
                <ul id="slotFormWarningList" class="mt-2 list-disc space-y-1 pl-5 text-xs"></ul>
            </div>
            <input
                type="hidden"
                name="slot_id"
                id="slotIdField"
                value="{{ old('slot_id') }}"
            >
            {{-- ACCOMPLISHMENT DETAILS --}}
            @if(($submissionType ?? '')==='report')
                <div
                    id="slotAccomplishmentDetails"
                    class="rounded-2xl border border-blue-100 bg-blue-50/50 p-4"
                >
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-3">
                        Submission Details
                    </p>
                    <div class="space-y-3 text-xs">
                        <div class="flex items-center justify-between gap-4">
                            <span class="font-bold text-gray-400 uppercase">
                                Category
                            </span>
                            <span
                                id="modalAccomplishmentCategory"
                                class="font-black text-gray-700 text-right"
                            >
                                --
                            </span>
                        </div>
                        <div
                            id="modalYdpProgramRow"
                            class="hidden flex items-center justify-between gap-4"
                        >
                            <span class="font-bold text-gray-400 uppercase">
                                Program Type
                            </span>
                            <span
                                id="modalYdpProgramType"
                                class="font-black text-gray-700 text-right"
                            >
                                --
                            </span>
                        </div>
                    </div>
                </div>
            @endif
            {{-- BUDGET DETAILS --}}
            @if(($submissionType ?? '')==='budget')
                <div
                    id="slotBudgetDetails"
                    class="rounded-2xl border border-gray-100 bg-gray-50 p-4"
                >
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-3">
                        Submission Details
                    </p>
                    <div class="space-y-3 text-xs">
                        <div class="flex items-center justify-between gap-4">
                            <span class="font-bold text-gray-400 uppercase">
                                Category
                            </span>
                            <span
                                id="modalBudgetCategory"
                                class="font-black text-gray-700 text-right"
                            >
                                --
                            </span>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <span class="font-bold text-gray-400 uppercase">
                                Fiscal Year
                            </span>
                            <span
                                id="modalFiscalYear"
                                class="font-black text-gray-700 text-right"
                            >
                                --
                            </span>
                        </div>
                        <div
                            id="modalPeriodRow"
                            class="flex items-center justify-between gap-4"
                        >
                            <span class="font-bold text-gray-400 uppercase">
                                Reporting Period
                            </span>
                            <span
                                id="modalReportingPeriod"
                                class="font-black text-gray-700 text-right"
                            >
                                --
                            </span>
                        </div>
                    </div>
                </div>
                {{-- ANNUAL BUDGET AMOUNT --}}
                <div id="annualBudgetAmountSection" class="hidden">
                    <label
                        for="annualBudgetAmount"
                        class="block text-[10px] font-black text-gray-500 uppercase mb-2 ml-1"
                    >
                        Annual Budget Amount
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-bold">
                            ₱
                        </span>
                        <input
                            type="number"
                            id="annualBudgetAmount"
                            name="annual_budget_amount"
                            value="{{ old('annual_budget_amount') }}"
                            min="0.01"
                            step="0.01"
                            placeholder="0.00"
                            class="w-full rounded-xl border border-gray-200 bg-white pl-9 pr-4 py-3 text-sm font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-red-200 focus:border-red-400"
                        >
                    </div>
                    <p class="mt-2 text-[10px] leading-relaxed text-gray-400">
                        Enter the total Annual Budget amount stated in the submitted Annual Budget document.
                    </p>
                </div>
                {{-- ANNUAL COA ACTUAL EXPENDITURE --}}
                <div id="actualExpenditureSection" class="hidden">
                    <label
                        for="actualExpenditure"
                        class="block text-[10px] font-black text-gray-500 uppercase mb-2 ml-1"
                    >
                        Actual Expenditure
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-bold">
                            ₱
                        </span>
                        <input
                            type="number"
                            id="actualExpenditure"
                            name="actual_expenditure"
                            value="{{ old('actual_expenditure') }}"
                            min="0.01"
                            step="0.01"
                            placeholder="0.00"
                            class="w-full rounded-xl border border-gray-200 bg-white pl-9 pr-4 py-3 text-sm font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-red-200 focus:border-red-400"
                        >
                    </div>
                    <p class="mt-2 text-[10px] leading-relaxed text-gray-400">
                        Enter the summarized total actual expenditure stated in the Annual COA / Financial Report.
                    </p>
                </div>
            @endif
            {{-- SUBMISSION METHOD --}}
            @if(($submissionType ?? '')==='report')
                <input
                    type="hidden"
                    name="sub_method"
                    value="pdf"
                >
            @else
                <div class="grid grid-cols-2 gap-3">
                    <label
                        id="templateModeLabel"
                        data-submission-mode="template"
                        class="slot-mode-label text-center p-3 rounded-xl border-2 border-gray-100 bg-gray-50 cursor-pointer text-[10px] font-black uppercase transition"
                    >
                        <input
                            type="radio"
                            name="sub_method"
                            value="template"
                            class="hidden"
                        >
                        System Template
                    </label>
                    <label
                        id="pdfModeLabel"
                        data-submission-mode="pdf"
                        class="slot-mode-label text-center p-3 rounded-xl border-2 border-gray-100 bg-gray-50 cursor-pointer text-[10px] font-black uppercase transition"
                    >
                        <input
                            type="radio"
                            name="sub_method"
                            value="pdf"
                            class="hidden"
                        >
                        PDF Upload
                    </label>
                </div>
                <div
                    id="templateUnavailableNote"
                    class="hidden rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-700"
                >
                    The SK360 system template is not currently available for this submission type. Please upload the prepared PDF report.
                </div>
            @endif
            {{-- PDF UPLOAD --}}
            <div
                id="slotFileSection"
                class="{{ ($submissionType ?? '')==='report' ? '' : 'hidden' }}"
            >
                <label class="block text-[10px] font-black text-red-500 uppercase mb-2 ml-1">
                    Select PDF File
                </label>
                <div class="border-2 border-dashed border-red-200 bg-red-50 p-4 rounded-xl text-center relative hover:bg-red-100 transition cursor-pointer">
                    <input
                        type="file"
                        name="report_file"
                        accept=".pdf"
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                    >
                    <p class="text-[10px] font-black text-red-600 uppercase">
                        Click or drag PDF here
                    </p>
                    <p
                        id="slotFileName"
                        class="mt-2 text-xs text-red-500 font-semibold"
                    ></p>
                </div>
            </div>
            <button
                id="slotSubmitButton"
                type="submit"
                class="w-full bg-red-600 text-white py-4 rounded-2xl font-black uppercase tracking-tighter shadow-lg hover:bg-red-700 active:scale-95 transition"
            >
                Submit Slot
            </button>
        </form>
    </div>
</div>
@push('scripts')
<script>
function openAccomplishmentSlotSubmission(slotId,title,categoryLabel,programTypeLabel=null){
    openSlotSubmission(slotId,title);
    const category=document.getElementById('modalAccomplishmentCategory');
    const programRow=document.getElementById('modalYdpProgramRow');
    const programType=document.getElementById('modalYdpProgramType');
    if(category){
        category.textContent=categoryLabel || 'Accomplishment Report';
    }
    if(programRow && programType){
        const hasProgramType=programTypeLabel!==null && programTypeLabel!=='';
        programRow.classList.toggle('hidden',!hasProgramType);
        programType.textContent=hasProgramType ? programTypeLabel : '--';
    }
}
const notifBtn=document.getElementById('notifBtn');
const notifDropdown=document.getElementById('notifDropdown');
const userMenuBtn=document.getElementById('userMenuBtn');
const userDropdown=document.getElementById('userDropdown');
document.addEventListener('DOMContentLoaded',()=>{
    const focusedSlot=document.querySelector('[data-focus-slot-card="1"]');
    const focusedSubmission=document.querySelector('[data-focus-submission="1"]');
    const target=focusedSlot || focusedSubmission;
    if(!target){
        return;
    }
    setTimeout(()=>{
        target.scrollIntoView({
            behavior:'smooth',
            block:'center'
        });
    },250);
});
notifBtn?.addEventListener('click',(e)=>{
    e.stopPropagation();
    notifDropdown?.classList.toggle('hidden');
    userDropdown?.classList.add('hidden');
});
userMenuBtn?.addEventListener('click',(e)=>{
    e.stopPropagation();
    userDropdown?.classList.toggle('hidden');
    notifDropdown?.classList.add('hidden');
});
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
});
const budgetFilterForm=document.getElementById('budgetFilterForm');
const budgetPeriodFilter=document.getElementById('budgetPeriodFilter');
const budgetMonthFilterWrap=document.getElementById('budgetMonthFilterWrap');
const budgetQuarterFilterWrap=document.getElementById('budgetQuarterFilterWrap');
const budgetHalfFilterWrap=document.getElementById('budgetHalfFilterWrap');
const budgetMonthFilter=document.getElementById('budgetMonthFilter');
const budgetQuarterFilter=document.getElementById('budgetQuarterFilter');
const budgetHalfFilter=document.getElementById('budgetHalfFilter');
const budgetFilterHelp=document.getElementById('budgetFilterHelp');
function syncBudgetFilterFields(){
    if(!budgetPeriodFilter){
        return;
    }
    const period=budgetPeriodFilter.value;
    const isMonthly=period==='monthly';
    const isQuarterly=period==='quarterly';
    const isSemiAnnual=period==='semi_annual';
    budgetMonthFilterWrap?.classList.toggle('hidden',!isMonthly);
    budgetQuarterFilterWrap?.classList.toggle('hidden',!isQuarterly);
    budgetHalfFilterWrap?.classList.toggle('hidden',!isSemiAnnual);
    if(budgetMonthFilter){
        budgetMonthFilter.disabled=!isMonthly;
    }
    if(budgetQuarterFilter){
        budgetQuarterFilter.disabled=!isQuarterly;
    }
    if(budgetHalfFilter){
        budgetHalfFilter.disabled=!isSemiAnnual;
    }
    if(budgetFilterHelp){
        budgetFilterHelp.textContent=period==='annual'
            ? 'Annual records do not use a month or quarter filter.'
            : period==='monthly'
                ? 'Showing the selected month only.'
                : period==='quarterly'
                    ? 'Showing the selected quarter only.'
                    : period==='semi_annual'
                        ? 'Showing the selected half of the year only.'
                        : 'Showing all available budget records.';
    }
}
budgetPeriodFilter?.addEventListener('change',syncBudgetFilterFields);
budgetFilterForm?.addEventListener('submit',syncBudgetFilterFields);
syncBudgetFilterFields();
const submissionSearch=document.getElementById('submissionSearch');
const submissionSlotCards=Array.from(document.querySelectorAll('.submission-slot-card'));
const submissionRecordRows=Array.from(document.querySelectorAll('.submission-record-row'));
const submissionSearchEmpty=document.getElementById('submissionSearchEmpty');
function applySubmissionSearch(){
    const term=(submissionSearch?.value || '').trim().toLowerCase();
    submissionSlotCards.forEach((card)=>{
        const matches=term==='' || (card.dataset.search || '').includes(term);
        card.classList.toggle('hidden',!matches);
    });
    let visibleRows=0;
    submissionRecordRows.forEach((row)=>{
        const matches=term==='' || (row.dataset.search || '').includes(term);
        row.classList.toggle('hidden',!matches);
        if(matches){
            visibleRows++;
        }
    });
    submissionSearchEmpty?.classList.toggle(
        'hidden',
        term==='' || visibleRows>0
    );
}
submissionSearch?.addEventListener('input',applySubmissionSearch);
const slotSubmissionForm=document.getElementById('slotSubmissionForm');
const slotFormWarning=document.getElementById('slotFormWarning');
const slotFormWarningList=document.getElementById('slotFormWarningList');
window.clearSlotFormWarning=function(){
    if(slotFormWarning){
        slotFormWarning.classList.add('hidden');
    }
    if(slotFormWarningList){
        slotFormWarningList.innerHTML='';
    }
};
function showSlotFormWarning(messages){
    if(!slotFormWarning || !slotFormWarningList){
        return;
    }
    slotFormWarningList.innerHTML='';
    messages.forEach((message)=>{
        const item=document.createElement('li');
        item.textContent=message;
        slotFormWarningList.appendChild(item);
    });
    slotFormWarning.classList.remove('hidden');
    slotFormWarning.scrollIntoView({
        behavior:'smooth',
        block:'nearest'
    });
}
slotSubmissionForm?.addEventListener('submit',(event)=>{
    const missing=[];
    const slotId=document.getElementById('slotIdField')?.value;
    const selectedMethod=document.querySelector('input[name="sub_method"]:checked')
        ?? document.querySelector('input[name="sub_method"][type="hidden"]');
    const method=selectedMethod?.value || '';
    const annualBudgetAmountSection=document.getElementById('annualBudgetAmountSection');
    const annualBudgetAmount=document.getElementById('annualBudgetAmount');
    const actualExpenditureSection=document.getElementById('actualExpenditureSection');
    const actualExpenditure=document.getElementById('actualExpenditure');
    const fileInput=document.querySelector('input[name="report_file"]');
    if(!slotId){
        missing.push('Please select a valid submission slot.');
    }
    if(!method){
        missing.push('Please select a submission method.');
    }
    if(
        annualBudgetAmountSection &&
        !annualBudgetAmountSection.classList.contains('hidden') &&
        (!annualBudgetAmount?.value || Number(annualBudgetAmount.value)<=0)
    ){
        missing.push('Annual Budget Amount is required.');
    }
    if(
        actualExpenditureSection &&
        !actualExpenditureSection.classList.contains('hidden') &&
        (!actualExpenditure?.value || Number(actualExpenditure.value)<=0)
    ){
        missing.push('Actual Expenditure is required.');
    }
    if(
        method==='pdf' &&
        fileInput &&
        (!fileInput.files || fileInput.files.length===0)
    ){
        missing.push('A PDF file is required for PDF Upload.');
    }
    if(missing.length){
        event.preventDefault();
        showSlotFormWarning(missing);
        return;
    }
    window.clearSlotFormWarning();
});
slotSubmissionForm?.querySelectorAll('input,select,textarea').forEach((field)=>{
    field.addEventListener('input',window.clearSlotFormWarning);
    field.addEventListener('change',window.clearSlotFormWarning);
});
document.addEventListener('click',(event)=>{
    const trigger=event.target.closest(
        'button[onclick*="openSlotSubmission"],button[onclick*="openAccomplishmentSlotSubmission"]'
    );
    if(trigger){
        window.clearSlotFormWarning();
    }
});
@if(($submissionType ?? '')==='budget' && $errors->any() && old('slot_id'))
    @php
        $failedBudgetSlot=collect($slots)->first(
            fn($slot)=>(int)$slot->slot_id===(int)old('slot_id')
        );
    @endphp
    document.addEventListener('DOMContentLoaded',()=>{
        const failedSlot=@json($failedBudgetSlot);
        if(failedSlot && typeof openSlotSubmission==='function'){
            openSlotSubmission(
                failedSlot.slot_id,
                failedSlot.title,
                failedSlot.budget_category ?? null,
                failedSlot.fiscal_year ?? null,
                failedSlot.budget_period_type ?? null,
                failedSlot.fiscal_month ?? null,
                failedSlot.fiscal_quarter ?? null,
                failedSlot.fiscal_half ?? null,
                failedSlot.template_available ?? false
            );
            const oldMethod=@json(old('sub_method'));
            if(oldMethod){
                const oldMethodInput=document.querySelector(
                    `input[name="sub_method"][value="${oldMethod}"]`
                );
                if(oldMethodInput){
                    oldMethodInput.checked=true;
                    if(typeof syncBudgetSubmissionMode==='function'){
                        syncBudgetSubmissionMode();
                    }
                }
            }
            showSlotFormWarning(@json($errors->all()));
        }
    });
@endif
</script>
@endpush