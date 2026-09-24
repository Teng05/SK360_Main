{{-- File guide: Blade view template for resources/views/shared/submission-slots-page.blade.php. --}}
@php
    $focusId = (int) request()->query('focus_id', 0);
    $focusSlot = (int) request()->query('focus_slot', 0);

    // Summary counts come from the slots and submissions already on this page.
    $submissionStatuses = collect($submissions)->map(
        fn ($submission) => strtolower((string) ($submission->quality_status ?? 'pending'))
    );
    $approvedCount = $submissionStatuses->filter(fn ($status) => $status === 'approved')->count();
    $revisionCount = $submissionStatuses->filter(fn ($status) => $status === 'needs_revision')->count();
    $pendingCount = $submissionStatuses->count() - $approvedCount - $revisionCount;
    $isBudgetPage = ($submissionType ?? '') === 'budget';
@endphp

<div class="flex h-screen bg-gray-100 overflow-hidden">
    {{-- SIDEBAR --}}
    @include('partials.app.sidebar')

    {{-- MAIN --}}
    <div class="flex-1 flex flex-col overflow-hidden min-w-0">
        {{-- TOPBAR --}}
        @include('partials.app.topbar')

        {{-- CONTENT --}}
        <main class="flex-1 overflow-y-auto p-8">
            <div class="max-w-6xl mx-auto">
                {{-- SUCCESS --}}
                @if (session('report_success'))
                    <div class="sk-alert sk-alert--success mb-6">
                        @include('partials.ui.icon', ['icon' => 'circle-check', 'iconSize' => 18])
                        <span>{{ session('report_success') }}</span>
                    </div>
                @endif

                {{-- ERROR --}}
                @if (session('report_error'))
                    <div class="sk-alert sk-alert--error mb-6">
                        @include('partials.ui.icon', ['icon' => 'circle-alert', 'iconSize' => 18])
                        <span>{{ session('report_error') }}</span>
                    </div>
                @endif

                {{-- VALIDATION ERRORS --}}
                @if ($errors->any())
                    <div class="sk-alert sk-alert--error mb-6">
                        @include('partials.ui.icon', ['icon' => 'circle-alert', 'iconSize' => 18])
                        <div>
                            <p class="font-bold mb-1">
                                Please fix the following:
                            </p>

                            <ul class="list-disc pl-5 space-y-1 font-medium">
                                @foreach ($errors->all() as $error)
                                    <li>
                                        {{ $error }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                {{-- PAGE TITLE --}}
                <div class="sk-page-head">
                    <div class="sk-page-head__text">
                        <span class="sk-eyebrow"><span class="sk-dot"></span>Submission Slots</span>
                        <h1 class="sk-page-title">
                            {{ $pageTitle }}
                        </h1>

                        <p class="sk-page-subtitle">
                            {{ $pageDescription }}
                        </p>
                    </div>
                </div>

                {{-- SUMMARY --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
                    <div class="sk-stat">
                        <div class="sk-stat__top">
                            <div>
                                <p class="sk-stat__label">Active Slots</p>
                                <p class="sk-stat__value">{{ $slots->count() }}</p>
                            </div>
                            <span class="sk-icon-tile">
                                @include('partials.ui.icon', ['icon' => 'lock-open', 'iconSize' => 21])
                            </span>
                        </div>
                        <p class="sk-stat__meta">Open windows from the SK President</p>
                    </div>

                    <div class="sk-stat">
                        <div class="sk-stat__top">
                            <div>
                                <p class="sk-stat__label">Submitted</p>
                                <p class="sk-stat__value">{{ $submissionStatuses->count() }}</p>
                            </div>
                            <span class="sk-icon-tile sk-icon-tile--blue">
                                @include('partials.ui.icon', ['icon' => $isBudgetPage ? 'wallet' : 'file-text', 'iconSize' => 21])
                            </span>
                        </div>
                        <p class="sk-stat__meta">Your recent submissions</p>
                    </div>

                    <div class="sk-stat">
                        <div class="sk-stat__top">
                            <div>
                                <p class="sk-stat__label">Pending Review</p>
                                <p class="sk-stat__value">{{ $pendingCount }}</p>
                            </div>
                            <span class="sk-icon-tile sk-icon-tile--orange">
                                @include('partials.ui.icon', ['icon' => 'hourglass', 'iconSize' => 21])
                            </span>
                        </div>
                        <p class="sk-stat__meta">Waiting for quality review</p>
                    </div>

                    <div class="sk-stat">
                        <div class="sk-stat__top">
                            <div>
                                <p class="sk-stat__label">Approved</p>
                                <p class="sk-stat__value">{{ $approvedCount }}</p>
                            </div>
                            <span class="sk-icon-tile sk-icon-tile--green">
                                @include('partials.ui.icon', ['icon' => 'circle-check', 'iconSize' => 21])
                            </span>
                        </div>
                        <p class="sk-stat__meta">
                            @if ($revisionCount > 0)
                                <span class="font-bold text-red-600">{{ $revisionCount }} need revision</span>
                            @else
                                No revisions requested
                            @endif
                        </p>
                    </div>
                </div>

                {{-- ACTIVE SLOTS --}}
                <section class="sk-card p-6 mb-8">
                    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
                        <div>
                            <h2 class="sk-section-title">
                                {{ $slotSectionTitle }}
                            </h2>

                            <p class="sk-section-subtitle">
                                Only active slots created by the SK President can accept submissions.
                            </p>
                        </div>

                        <span class="sk-badge sk-badge--red sk-badge--dot">
                            {{ $slots->count() }} Active
                        </span>
                    </div>

                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
                        @forelse ($slots as $slot)
                            @php
                                $slotSubmission = collect($submissions)->first(
                                    fn ($submission) => (int) ($submission->slot_id ?? 0) === (int) $slot->slot_id
                                );

                                $hasSubmission = !empty($slot->has_submitted) || $slotSubmission;

                                $qualityStatus = strtolower(
                                    (string) ($slotSubmission->quality_status ?? 'pending')
                                );

                                $qualityRemarks = trim(
                                    (string) ($slotSubmission->quality_remarks ?? '')
                                );

                                $canResubmit = $hasSubmission
                                    && $qualityStatus === 'needs_revision'
                                    && !empty($allowResubmission);

                                $slotSubmissionId = ($submissionType ?? '') === 'budget'
                                    ? (int) ($slotSubmission->budget_report_id ?? 0)
                                    : (int) ($slotSubmission->report_id ?? 0);

                                $isFocusedSlot = $focusSlot > 0
                                    && (int) $slot->slot_id === $focusSlot;

                                $isFocusedSubmissionSlot = $focusId > 0
                                    && $slotSubmissionId === $focusId;

                                $isFocusedCard =
                                    $isFocusedSlot ||
                                    $isFocusedSubmissionSlot;
                            @endphp

                            <div
                                id="submission-slot-{{ $slot->slot_id }}"
                                data-focus-slot-card="{{ $isFocusedCard ? '1' : '0' }}"
                                class="relative flex flex-col rounded-2xl border p-5 transition-all duration-500 {{ $isFocusedCard ? 'border-yellow-300 bg-[#fffbeb] ring-4 ring-yellow-100 shadow-lg' : 'border-gray-200 bg-white hover:shadow-md' }}"
                            >
                                @if ($isFocusedCard)
                                    <div class="sk-alert sk-alert--warning mb-4 !py-2.5 !text-xs">
                                        @include('partials.ui.icon', ['icon' => 'bell', 'iconSize' => 16])

                                        <span>
                                            {{ $isFocusedSubmissionSlot
                                                ? 'This is the submission from the notification you opened.'
                                                : 'This is the submission slot from the notification you opened.' }}
                                        </span>
                                    </div>
                                @endif

                                <div class="flex items-start gap-4">
                                    <span class="sk-thumb {{ $isBudgetPage ? 'sk-thumb--blue' : '' }}">
                                        @include('partials.ui.icon', ['icon' => $isBudgetPage ? 'wallet' : 'clipboard-list', 'iconSize' => 24])
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        <p class="sk-overline">
                                            {{ str_replace('_', ' ', $slot->submission_type) }}
                                        </p>

                                        <h3 class="mt-1 text-[17px] font-bold leading-snug text-gray-900">
                                            {{ $slot->title }}
                                        </h3>

                                        <p class="text-sm text-gray-500 mt-1 leading-relaxed">
                                            {{ $slot->description ?: 'No description provided.' }}
                                        </p>
                                    </div>

                                    <div class="flex flex-col items-end gap-2 shrink-0">
                                        <span class="rounded-full px-3 py-1 text-[11px] font-bold {{ $slot->slot_status_badge ?? 'bg-red-100 text-red-600' }}">
                                            {{ $slot->slot_status_label ?? 'Open' }}
                                        </span>

                                        <span class="sk-badge sk-badge--gray">
                                            {{ $slot->role }}
                                        </span>
                                    </div>
                                </div>

                                {{-- BUDGET METADATA --}}
                                @if (($submissionType ?? '') === 'budget')
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <span class="sk-badge sk-badge--blue">
                                            {{ $slot->budget_category_label ?? 'Budget / Financial Report' }}
                                        </span>

                                        @if (!empty($slot->fiscal_year))
                                            <span class="sk-badge sk-badge--red">
                                                FY {{ $slot->fiscal_year }}
                                            </span>
                                        @endif

                                        @if (!empty($slot->budget_period_label))
                                            <span class="sk-badge sk-badge--yellow">
                                                {{ $slot->budget_period_label }}
                                            </span>
                                        @endif
                                    </div>
                                @endif

                                {{-- DATES --}}
                                <div class="mt-4 grid grid-cols-2 gap-3">
                                    <div class="rounded-xl border border-gray-100 bg-[#f8f9fb] px-3.5 py-3">
                                        <p class="sk-overline">
                                            Start
                                        </p>

                                        <p class="mt-1 flex items-center gap-1.5 text-sm font-bold text-gray-800">
                                            @include('partials.ui.icon', ['icon' => 'calendar-days', 'iconSize' => 15, 'iconClass' => 'text-gray-400'])
                                            {{ \Carbon\Carbon::parse($slot->start_date)->format('M d, Y') }}
                                        </p>
                                    </div>

                                    <div class="rounded-xl border border-red-100 bg-[#fff8f8] px-3.5 py-3">
                                        <p class="sk-overline !text-red-400">
                                            Deadline
                                        </p>

                                        <p class="mt-1 flex items-center gap-1.5 text-sm font-bold text-gray-800">
                                            @include('partials.ui.icon', ['icon' => 'clock', 'iconSize' => 15, 'iconClass' => 'text-red-400'])
                                            {{ \Carbon\Carbon::parse($slot->end_date)->format('M d, Y') }}
                                        </p>
                                    </div>
                                </div>

                                @if ($hasSubmission)
                                    <div class="mt-4 rounded-xl border px-4 py-3 {{ $qualityStatus === 'approved' ? 'border-green-200 bg-green-50' : ($qualityStatus === 'needs_revision' ? 'border-red-200 bg-red-50' : 'border-amber-200 bg-amber-50') }}">
                                        <div class="flex items-center justify-between gap-3">
                                            <p class="flex items-center gap-1.5 text-xs font-extrabold {{ $qualityStatus === 'approved' ? 'text-green-700' : ($qualityStatus === 'needs_revision' ? 'text-red-700' : 'text-amber-700') }}">
                                                @include('partials.ui.icon', ['icon' => $qualityStatus === 'approved' ? 'circle-check' : ($qualityStatus === 'needs_revision' ? 'circle-alert' : 'hourglass'), 'iconSize' => 15])
                                                {{ $qualityStatus === 'approved'
                                                    ? 'Approved'
                                                    : ($qualityStatus === 'needs_revision'
                                                        ? 'Needs Revision'
                                                        : 'Pending Review') }}
                                            </p>

                                            @if ($qualityStatus === 'approved')
                                                <span class="flex items-center gap-1 text-[11px] font-bold text-green-600">
                                                    @include('partials.ui.icon', ['icon' => 'lock', 'iconSize' => 13])
                                                    Locked
                                                </span>
                                            @endif
                                        </div>

                                        @if ($qualityStatus === 'needs_revision' && $qualityRemarks !== '')
                                            <p class="mt-2 text-xs leading-relaxed text-red-700">
                                                <span class="font-extrabold">
                                                    President Remarks:
                                                </span>

                                                {{ $qualityRemarks }}
                                            </p>
                                        @elseif ($qualityStatus === 'pending')
                                            <p class="mt-2 text-xs leading-relaxed text-amber-700">
                                                Your submission is waiting for Quality Documentation review.
                                            </p>
                                        @elseif ($qualityStatus === 'approved')
                                            <p class="mt-2 text-xs leading-relaxed text-green-700">
                                                This submission has been approved and can no longer be replaced.
                                            </p>
                                        @endif
                                    </div>
                                @endif

                                {{-- SLOT ACTION --}}
                                <div class="mt-auto flex justify-end pt-4">
                                    @if (!empty($slot->is_upcoming))
                                        <button
                                            type="button"
                                            disabled
                                            class="sk-btn sk-btn--ghost !bg-gray-100 !text-gray-500"
                                        >
                                            @include('partials.ui.icon', ['icon' => 'calendar-clock', 'iconSize' => 16])
                                            Opens {{ \Carbon\Carbon::parse($slot->start_date)->format('M d, Y') }}
                                        </button>
                                    @elseif ($hasSubmission && $qualityStatus === 'approved')
                                        <button
                                            type="button"
                                            disabled
                                            class="sk-btn !bg-green-50 !text-green-700 !border-green-200"
                                        >
                                            @include('partials.ui.icon', ['icon' => 'lock', 'iconSize' => 16])
                                            Approved / Locked
                                        </button>
                                    @elseif ($hasSubmission && $qualityStatus === 'pending')
                                        <button
                                            type="button"
                                            disabled
                                            class="sk-btn !bg-amber-50 !text-amber-700 !border-amber-200"
                                        >
                                            @include('partials.ui.icon', ['icon' => 'hourglass', 'iconSize' => 16])
                                            Pending Review
                                        </button>
                                    @elseif ($canResubmit)
                                        <button
                                            type="button"
                                            class="sk-btn sk-btn--primary"
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
                                        >
                                            @include('partials.ui.icon', ['icon' => 'upload', 'iconSize' => 16])
                                            {{ ($submissionType ?? '') === 'report'
                                                ? 'Resubmit Report File'
                                                : 'Resubmit Budget File' }}
                                        </button>
                                    @elseif ($hasSubmission)
                                        <button
                                            type="button"
                                            disabled
                                            class="sk-btn sk-btn--ghost !bg-gray-100 !text-gray-500"
                                        >
                                            @include('partials.ui.icon', ['icon' => 'circle-check', 'iconSize' => 16])
                                            Submitted
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            class="sk-btn sk-btn--primary"
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
                                        >
                                            @include('partials.ui.icon', ['icon' => 'upload', 'iconSize' => 16])
                                            {{ $slotActionLabel }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="xl:col-span-2 sk-empty rounded-2xl border border-dashed border-gray-200 bg-[#f8f9fb]">
                                <span class="sk-icon-tile sk-icon-tile--gray">
                                    @include('partials.ui.icon', ['icon' => 'inbox', 'iconSize' => 26])
                                </span>
                                <p class="sk-empty__text">{{ $slotEmptyMessage }}</p>
                            </div>
                        @endforelse
                    </div>
                </section>

                {{-- RECENT SUBMISSIONS --}}
                <section class="sk-card overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-5 border-b border-gray-100">
                        <div>
                            <h3 class="sk-section-title">
                                Recent Submissions
                            </h3>
                            <p class="sk-section-subtitle">Files you have sent to submission slots.</p>
                        </div>
                        <span class="sk-badge sk-badge--gray">{{ $submissionStatuses->count() }} total</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3.5">
                                        Title
                                    </th>

                                    <th class="px-6 py-3.5">
                                        Method
                                    </th>

                                    <th class="px-6 py-3.5">
                                        Date Submitted
                                    </th>

                                    <th class="px-6 py-3.5">
                                        Quality Review
                                    </th>

                                    <th class="px-6 py-3.5 text-right">
                                        Actions
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="text-sm text-gray-600">
                                @forelse ($submissions as $submission)
                                    @php
                                        $submissionId = ($submissionType ?? '') === 'budget'
                                            ? (int) ($submission->budget_report_id ?? 0)
                                            : (int) ($submission->report_id ?? 0);

                                        $isFocusedSubmission = $focusId > 0
                                            && $submissionId === $focusId;
                                    @endphp

                                    <tr
                                        id="submission-{{ $submissionId }}"
                                        data-focus-submission="{{ $isFocusedSubmission ? '1' : '0' }}"
                                        class="transition {{ $isFocusedSubmission ? 'bg-[#fffbeb] ring-2 ring-inset ring-yellow-200' : '' }}"
                                    >
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <span class="sk-icon-tile sk-icon-tile--sm {{ $isBudgetPage ? 'sk-icon-tile--blue' : '' }}">
                                                    @include('partials.ui.icon', ['icon' => $isBudgetPage ? 'wallet' : 'file-text', 'iconSize' => 17])
                                                </span>
                                                <div class="min-w-0">
                                                    <div class="font-bold text-gray-900">
                                                        {{ $submission->title ?? $submission->report_title }}
                                                    </div>

                                                    <div class="text-xs text-gray-500 font-semibold mt-0.5">
                                                        {{ $submission->period_label
                                                            ?? optional($submission->submitted_at)->format('F Y')
                                                            ?? 'Submission' }}
                                                    </div>

                                                    @if ($isFocusedSubmission)
                                                        <div class="mt-2 inline-flex items-center gap-1 rounded-full bg-yellow-100 px-2 py-1 text-[10px] font-bold text-yellow-800">
                                                            @include('partials.ui.icon', ['icon' => 'bell', 'iconSize' => 12])
                                                            Opened from notification
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="{{ $submission->method_badge }} px-3 py-1 rounded-full text-[11px] font-bold">
                                                {{ $submission->method_label }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4 text-sm font-semibold text-gray-700 whitespace-nowrap">
                                            {{ optional($submission->submitted_at)->format('M d, Y') ?? '--' }}
                                        </td>

                                        <td class="px-6 py-4">
                                            @php
                                                $submissionQualityStatus = strtolower(
                                                    (string) ($submission->quality_status ?? 'pending')
                                                );

                                                $submissionQualityLabel =
                                                    $submission->quality_status_label
                                                    ?? ($submissionQualityStatus === 'approved'
                                                        ? 'Approved'
                                                        : ($submissionQualityStatus === 'needs_revision'
                                                            ? 'Needs Revision'
                                                            : 'Pending Review'));

                                                $submissionQualityBadge =
                                                    $submission->quality_status_badge
                                                    ?? ($submissionQualityStatus === 'approved'
                                                        ? 'bg-green-100 text-green-700'
                                                        : ($submissionQualityStatus === 'needs_revision'
                                                            ? 'bg-red-100 text-red-700'
                                                            : 'bg-amber-100 text-amber-700'));
                                            @endphp

                                            <span class="{{ $submissionQualityBadge }} inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold">
                                                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                                {{ $submissionQualityLabel }}
                                            </span>

                                            @if ($submissionQualityStatus === 'needs_revision' && !empty($submission->quality_remarks))
                                                <p class="mt-2 max-w-xs text-xs leading-relaxed text-red-600">
                                                    <span class="font-extrabold">
                                                        Remarks:
                                                    </span>

                                                    {{ $submission->quality_remarks }}
                                                </p>
                                            @endif
                                        </td>

                                        <td class="px-6 py-4 text-right whitespace-nowrap">
                                            <div class="inline-flex items-center gap-1.5">
                                                @if (!empty($submission->view_url))
                                                    <a
                                                        href="{{ $submission->view_url }}"
                                                        target="_blank"
                                                        class="sk-icon-btn !w-9 !h-9 border !border-gray-200 bg-white text-gray-500 hover:!bg-blue-50 hover:!text-blue-600 hover:!border-blue-100"
                                                        title="View submission"
                                                    >
                                                        @include('partials.ui.icon', ['icon' => 'eye', 'iconSize' => 16])
                                                    </a>
                                                @endif

                                                @if (!empty($submission->download_url))
                                                    <a
                                                        href="{{ $submission->download_url }}"
                                                        target="_blank"
                                                        class="sk-icon-btn !w-9 !h-9 border !border-gray-200 bg-white text-gray-500 hover:!bg-green-50 hover:!text-green-600 hover:!border-green-100"
                                                        title="Download submission"
                                                    >
                                                        @include('partials.ui.icon', ['icon' => 'download', 'iconSize' => 16])
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">
                                            <div class="sk-empty">
                                                <span class="sk-icon-tile sk-icon-tile--gray">
                                                    @include('partials.ui.icon', ['icon' => 'inbox', 'iconSize' => 24])
                                                </span>
                                                <p class="sk-empty__text">No submissions yet.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>

{{-- ========================================================= --}}
{{-- SUBMISSION MODAL --}}
{{-- ========================================================= --}}
<div
    id="slotSubmissionModal"
    class="hidden fixed inset-0 bg-black/40 z-[100] flex items-center justify-center p-4"
>
    <div class="bg-white w-full max-w-md rounded-[20px] overflow-hidden">
        {{-- MODAL HEADER --}}
        <div class="px-6 pt-6 pb-5 flex items-start gap-4 border-b border-gray-100">
            <span class="sk-icon-tile">
                @include('partials.ui.icon', ['icon' => 'upload', 'iconSize' => 21])
            </span>

            <div class="min-w-0 flex-1">
                <h3 class="sk-modal__title !text-xl">
                    Submit to Slot
                </h3>

                <p
                    id="slotSubmissionTitle"
                    class="sk-modal__subtitle"
                ></p>
            </div>

            <button
                type="button"
                onclick="closeSlotSubmission()"
                class="sk-icon-btn -mr-2 -mt-2"
                aria-label="Close"
            >
                @include('partials.ui.icon', ['icon' => 'x', 'iconSize' => 20])
            </button>
        </div>

        {{-- FORM --}}
        <form
            action="{{ $storeRoute }}"
            method="POST"
            enctype="multipart/form-data"
            class="p-6 space-y-5"
        >
            @csrf

            <input
                type="hidden"
                name="slot_id"
                id="slotIdField"
                value="{{ old('slot_id') }}"
            >

            {{-- ========================================================= --}}
            {{-- BUDGET DETAILS --}}
            {{-- ========================================================= --}}
            @if (($submissionType ?? '') === 'budget')
                <div
                    id="slotBudgetDetails"
                    class="rounded-2xl border border-gray-100 bg-[#f8f9fb] p-4"
                >
                    <p class="sk-overline mb-3">
                        Submission Details
                    </p>

                    <div class="space-y-2.5 text-sm">
                        {{-- CATEGORY --}}
                        <div class="flex items-center justify-between gap-4">
                            <span class="font-semibold text-gray-500">
                                Category
                            </span>

                            <span
                                id="modalBudgetCategory"
                                class="font-bold text-gray-800 text-right"
                            >
                                --
                            </span>
                        </div>

                        {{-- YEAR --}}
                        <div class="flex items-center justify-between gap-4">
                            <span class="font-semibold text-gray-500">
                                Fiscal Year
                            </span>

                            <span
                                id="modalFiscalYear"
                                class="font-bold text-gray-800 text-right"
                            >
                                --
                            </span>
                        </div>

                        {{-- PERIOD --}}
                        <div
                            id="modalPeriodRow"
                            class="flex items-center justify-between gap-4"
                        >
                            <span class="font-semibold text-gray-500">
                                Reporting Period
                            </span>

                            <span
                                id="modalReportingPeriod"
                                class="font-bold text-gray-800 text-right"
                            >
                                --
                            </span>
                        </div>
                    </div>
                </div>

                {{-- ===================================================== --}}
                {{-- ANNUAL BUDGET AMOUNT --}}
                {{-- Shown only when category = annual_budget --}}
                {{-- ===================================================== --}}
                <div
                    id="annualBudgetAmountSection"
                    class="hidden"
                >
                    <label
                        for="annualBudgetAmount"
                        class="sk-label"
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
                            class="w-full rounded-xl border border-gray-200 bg-white pl-9 pr-4 py-3 text-sm font-semibold text-gray-700 focus:outline-none"
                        >
                    </div>

                    <p class="mt-2 text-xs leading-relaxed text-gray-500">
                        Enter the total Annual Budget amount stated in the submitted Annual Budget document.
                    </p>
                </div>

                {{-- ===================================================== --}}
                {{-- ANNUAL COA ACTUAL EXPENDITURE --}}
                {{-- Shown only when category = coa_report + annual --}}
                {{-- ===================================================== --}}
                <div
                    id="actualExpenditureSection"
                    class="hidden"
                >
                    <label
                        for="actualExpenditure"
                        class="sk-label"
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
                            class="w-full rounded-xl border border-gray-200 bg-white pl-9 pr-4 py-3 text-sm font-semibold text-gray-700 focus:outline-none"
                        >
                    </div>

                    <p class="mt-2 text-xs leading-relaxed text-gray-500">
                        Enter the summarized total actual expenditure stated in the Annual COA / Financial Report.
                    </p>
                </div>
            @endif

            {{-- ========================================================= --}}
            {{-- SUBMISSION METHOD --}}
            {{-- ========================================================= --}}
            @if (($submissionType ?? '') === 'report')
                <input
                    type="hidden"
                    name="sub_method"
                    value="pdf"
                >
            @else
                {{-- The budget page script toggles border-red-500/bg-red-50/text-red-600 on these labels. --}}
                <div class="grid grid-cols-2 gap-3">
                    {{-- SYSTEM TEMPLATE --}}
                    <label
                        id="templateModeLabel"
                        data-submission-mode="template"
                        class="slot-mode-label flex flex-col items-center gap-1.5 text-center p-3.5 rounded-xl border-2 border-gray-100 bg-gray-50 cursor-pointer text-xs font-bold text-gray-700 transition"
                    >
                        <input
                            type="radio"
                            name="sub_method"
                            value="template"
                            class="hidden"
                        >

                        @include('partials.ui.icon', ['icon' => 'layout-grid', 'iconSize' => 19])
                        System Template
                    </label>

                    {{-- PDF --}}
                    <label
                        id="pdfModeLabel"
                        data-submission-mode="pdf"
                        class="slot-mode-label flex flex-col items-center gap-1.5 text-center p-3.5 rounded-xl border-2 border-gray-100 bg-gray-50 cursor-pointer text-xs font-bold text-gray-700 transition"
                    >
                        <input
                            type="radio"
                            name="sub_method"
                            value="pdf"
                            class="hidden"
                        >

                        @include('partials.ui.icon', ['icon' => 'file-text', 'iconSize' => 19])
                        PDF Upload
                    </label>
                </div>

                <div
                    id="templateUnavailableNote"
                    class="hidden sk-alert sk-alert--warning !text-xs"
                >
                    The SK360 system template is not currently available for this submission type. Please upload the prepared PDF report.
                </div>
            @endif

            {{-- ========================================================= --}}
            {{-- PDF UPLOAD --}}
            {{-- ========================================================= --}}
            <div
                id="slotFileSection"
                class="{{ ($submissionType ?? '') === 'report' ? '' : 'hidden' }}"
            >
                <label class="sk-label">
                    Select PDF File
                </label>

                <div class="border-2 border-dashed border-red-200 bg-[#fff8f8] px-4 py-6 rounded-2xl text-center relative hover:bg-red-50 hover:border-red-300 transition cursor-pointer">
                    <input
                        type="file"
                        name="report_file"
                        accept=".pdf"
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                    >

                    <span class="sk-icon-tile mx-auto mb-2">
                        @include('partials.ui.icon', ['icon' => 'upload', 'iconSize' => 20])
                    </span>

                    <p class="text-sm font-bold text-gray-800">
                        Click or drag PDF here
                    </p>

                    <p
                        id="slotFileName"
                        class="mt-1 text-xs text-red-600 font-semibold"
                    ></p>
                </div>
            </div>

            {{-- SUBMIT --}}
            <button
                id="slotSubmitButton"
                type="submit"
                class="sk-btn sk-btn--primary sk-btn--lg w-full"
            >
                Submit Slot
            </button>
        </form>
    </div>
</div>

{{-- ========================================================= --}}
{{-- SHARED SCRIPTS --}}
{{-- ========================================================= --}}
@push('scripts')
<script>
    const notifBtn =
        document.getElementById('notifBtn');

    const notifDropdown =
        document.getElementById('notifDropdown');

    const userMenuBtn =
        document.getElementById('userMenuBtn');

    const userDropdown =
        document.getElementById('userDropdown');

    document.addEventListener(
        'DOMContentLoaded',
        function () {
            const focusedSlot =
                document.querySelector('[data-focus-slot-card="1"]');

            const focusedSubmission =
                document.querySelector('[data-focus-submission="1"]');

            const target =
                focusedSlot || focusedSubmission;

            if (!target) {
                return;
            }

            setTimeout(
                function () {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                },
                250
            );
        }
    );

    notifBtn.addEventListener(
        'click',
        function (e) {
            e.stopPropagation();

            notifDropdown.classList.toggle('hidden');
            userDropdown.classList.add('hidden');
        }
    );

    userMenuBtn.addEventListener(
        'click',
        function (e) {
            e.stopPropagation();

            userDropdown.classList.toggle('hidden');
            notifDropdown.classList.add('hidden');
        }
    );

    document.addEventListener(
        'click',
        function (e) {
            if (
                !notifBtn.contains(e.target)
                &&
                !notifDropdown.contains(e.target)
            ) {
                notifDropdown.classList.add('hidden');
            }

            if (
                !userMenuBtn.contains(e.target)
                &&
                !userDropdown.contains(e.target)
            ) {
                userDropdown.classList.add('hidden');
            }
        }
    );
</script>
@endpush
