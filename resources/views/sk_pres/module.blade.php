{{-- File guide: Blade view template for resources/views/sk_pres/module.blade.php. --}}
@extends('layouts.app')
@section('title','SK 360 Dashboard')
@section('page_css')
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection
@section('content')
<div class="flex h-screen bg-[#f1f5f9] overflow-hidden">
        @include('partials.app.sidebar')

    <div class="flex-1 flex flex-col">
                @include('partials.app.topbar')

        <main class="flex-1 overflow-y-auto p-8 bg-[#f8fafc]">
            <div class="sk-page-head">
                <div class="sk-page-head__text">
                    <span class="sk-eyebrow"><span class="sk-dot"></span>Module Management</span>
                    <h1 class="sk-page-title">Submission &amp; Document Management</h1>
                    <p class="sk-page-subtitle">Create and manage submission periods and public portal documents.</p>
                </div>
                <div class="sk-page-head__actions">
                    <button id="openModalBtn" class="sk-btn sk-btn--primary sk-btn--lg">
                        @include('partials.ui.icon',['icon'=>'plus','iconSize'=>19,'iconStroke'=>2.4])
                        Create Submission Slot
                    </button>
                </div>
            </div>
            @if(session('status'))
                <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
            @endif
            @if(session('warning'))
                <div class="mb-6 rounded-2xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-700">{{ session('warning') }}</div>
            @endif
            @if($errors->getBag('default')->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <p class="font-bold">Unable to save the submission slot. Please correct the following:</p>
                    <ul class="mt-2 list-disc pl-5 space-y-1">
                        @foreach($errors->getBag('default')->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <section id="lydp-public-document" class="mb-8 overflow-hidden rounded-3xl border border-blue-100 bg-white shadow-sm">
                <div class="flex flex-col gap-4 border-b border-blue-100 bg-gradient-to-r from-blue-50 to-white px-6 py-5 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-start gap-4">
                        <span class="sk-icon-tile sk-icon-tile--lg sk-icon-tile--blue">@include('partials.ui.icon', ['icon'=>'file-text','iconSize'=>23])</span>
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-blue-600">Public Portal Documents</p>
                            <h3 class="mt-1 text-xl font-black text-gray-900">Local Youth Development Plan (LYDP)</h3>
                            <p class="mt-1 text-sm text-gray-500">Publish the LYDP PDF shown in Public Portal → Annual Budget & LYDP.</p>
                        </div>
                    </div>
                    @if($lydpAvailable)
                        <span class="inline-flex shrink-0 items-center rounded-full bg-green-100 px-3 py-1.5 text-[10px] font-black uppercase tracking-wider text-green-700">● Published</span>
                    @else
                        <span class="inline-flex shrink-0 items-center rounded-full bg-gray-100 px-3 py-1.5 text-[10px] font-black uppercase tracking-wider text-gray-500">Not Published</span>
                    @endif
                </div>
                <div class="p-6">
                    @if($errors->lydpUpload->any())
                        <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            <p class="font-bold">LYDP upload rejected.</p>
                            <ul class="mt-2 list-disc space-y-1 pl-5">
                                @foreach($errors->lydpUpload->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(360px,520px)] xl:items-center">
                        <div>
                            @if($lydpAvailable)
                                <p class="text-sm font-bold text-gray-800">Current public document is available.</p>
                                <p class="mt-1 text-xs text-gray-500">Last updated: {{ $lydpUpdatedAt ? $lydpUpdatedAt->format('M d, Y h:i A') : 'Unknown' }}</p>
                                <p class="mt-3 text-xs leading-relaxed text-gray-500">Uploading another PDF replaces the current LYDP shown to public visitors. The public page remains the same.</p>
                                <a href="{{ $lydpUrl }}" target="_blank" rel="noopener" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-gray-900 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-gray-700">@include('partials.ui.icon', ['icon'=>'eye','iconSize'=>15]) View Current LYDP</a>
                            @else
                                <p class="text-sm font-bold text-gray-800">No LYDP PDF has been published yet.</p>
                                <p class="mt-1 text-xs leading-relaxed text-gray-500">Once published, the PDF automatically becomes available in the existing Annual Budget & LYDP public page.</p>
                            @endif
                        </div>
                        <form id="lydpUploadForm" action="{{ route('sk_pres.module.store') }}" method="POST" enctype="multipart/form-data" class="rounded-2xl border border-dashed border-blue-200 bg-blue-50/40 p-4" novalidate>
                            @csrf
                            <input type="hidden" name="form_context" value="lydp_upload">
                            <label for="lydpFile" class="block text-xs font-black uppercase tracking-wider text-gray-600">{{ $lydpAvailable ? 'Replacement LYDP PDF' : 'LYDP PDF' }}</label>
                            <input id="lydpFile" type="file" name="lydp_file" accept=".pdf,application/pdf" required class="mt-3 block w-full rounded-xl border border-blue-200 bg-white px-3 py-2.5 text-xs text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-100 file:px-3 file:py-2 file:text-xs file:font-bold file:text-blue-700 hover:file:bg-blue-200">
                            <p class="mt-2 text-[11px] text-gray-500">PDF only. No application-level file size limit is added.</p>
                            <button type="submit" class="mt-4 w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-black text-white shadow-sm transition hover:bg-blue-700">{{ $lydpAvailable ? 'Replace LYDP PDF' : 'Publish LYDP PDF' }}</button>
                        </form>
                    </div>
                </div>
            </section>
            <div id="submissionModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50 px-4">
                <div class="bg-white w-full max-w-3xl max-h-[92vh] overflow-y-auto rounded-[24px] border-2 border-blue-500 shadow-2xl p-8 relative">
                    <button id="closeModalBtn" type="button" class="absolute top-4 right-5 text-gray-500 hover:text-red-600 text-2xl font-bold">&times;</button>
                    <h2 id="slotModalTitle" class="text-4xl font-bold text-gray-900 mb-2">Create New Submission Slot</h2>
                    <p id="slotModalDescription" class="text-gray-600 mb-8 text-base">Set up a new submission period for SK officials to submit reports</p>
                    <form id="slotForm" action="{{ route('sk_pres.module.store') }}" method="POST" class="space-y-6" novalidate>
                        @csrf
                        <input type="hidden" id="slotFormMethod" name="_method" value="PUT" disabled>
                        <input type="hidden" id="slotFormContext" name="form_context" value="{{ old('form_context','create') }}">
                        <input type="hidden" id="editingSlotId" name="editing_slot_id" value="{{ old('editing_slot_id') }}">
                        <div>
                            <label class="block text-lg font-semibold text-gray-900 mb-2">Submission Type</label>
                            <select id="submissionType" name="submission_type" required class="w-full h-14 px-4 rounded-xl border border-red-300 bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-400">
                                <option value="accomplishment_report" @selected(old('submission_type','accomplishment_report')==='accomplishment_report')>Accomplishment Report</option>
                                <option value="budget_report" @selected(old('submission_type')==='budget_report')>Budget / Financial Report</option>
                            </select>
                        </div>
                        <div id="accomplishmentDetails" class="space-y-5 rounded-2xl border border-blue-200 bg-blue-50/40 p-5">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Accomplishment Report Details</h3>
                                <p class="text-sm text-gray-500 mt-1">Classify the accomplishment requirement for reporting and ranking purposes.</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-900 mb-2">Accomplishment Category</label>
                                <select id="accomplishmentCategory" name="accomplishment_category" class="w-full h-14 px-4 rounded-xl border border-blue-300 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400">
                                    <option value="general" @selected(old('accomplishment_category','general')==='general')>General Accomplishment</option>
                                    <option value="youth_development_program" @selected(old('accomplishment_category')==='youth_development_program')>Youth Development Program</option>
                                    <option value="kk_assembly" @selected(old('accomplishment_category')==='kk_assembly')>KK Assembly</option>
                                </select>
                            </div>
                            <div id="ydpProgramSection" class="hidden">
                                <label class="block text-sm font-semibold text-gray-900 mb-2">YDP Program Type</label>
                                <select id="ydpProgramType" name="ydp_program_type" class="w-full h-14 px-4 rounded-xl border border-blue-300 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400">
                                    <option value="">Select program type</option>
                                    @foreach($ydpProgramTypes as $value=>$label)
                                        <option value="{{ $value }}" @selected(old('ydp_program_type')===$value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-500 mt-2">Choose the program type based on the SK Youth Development Program checklist.</p>
                            </div>
                        </div>
                        <div id="budgetDetails" class="hidden space-y-5 rounded-2xl border border-red-200 bg-red-50/40 p-5">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Budget / Financial Report Details</h3>
                                <p class="text-sm text-gray-500 mt-1">Specify what type of financial submission is expected from the barangay.</p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-900 mb-2">Report Category</label>
                                    <select id="budgetCategory" name="budget_category" class="w-full h-14 px-4 rounded-xl border border-red-300 bg-white focus:outline-none focus:ring-2 focus:ring-red-400">
                                        <option value="">Select category</option>
                                        <option value="annual_budget" @selected(old('budget_category')==='annual_budget')>Annual Budget</option>
                                        <option value="supplemental_budget" @selected(old('budget_category')==='supplemental_budget')>Supplemental Budget</option>
                                        <option value="coa_report" @selected(old('budget_category')==='coa_report')>Financial / COA Report</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-900 mb-2">Fiscal Year</label>
                                    <input id="fiscalYear" type="number" name="fiscal_year" min="2000" max="2100" value="{{ old('fiscal_year',now()->year) }}" class="w-full h-14 px-4 rounded-xl border border-red-300 bg-white focus:outline-none focus:ring-2 focus:ring-red-400">
                                </div>
                            </div>
                            <div id="reportPeriodSection" class="hidden">
                                <label class="block text-sm font-semibold text-gray-900 mb-2">Reporting Period</label>
                                <select id="budgetPeriodType" name="budget_period_type" class="w-full h-14 px-4 rounded-xl border border-red-300 bg-white focus:outline-none focus:ring-2 focus:ring-red-400">
                                    <option value="">Select reporting period</option>
                                    <option value="monthly" @selected(old('budget_period_type')==='monthly')>Monthly</option>
                                    <option value="quarterly" @selected(old('budget_period_type')==='quarterly')>Quarterly</option>
                                    <option value="semi_annual" @selected(old('budget_period_type')==='semi_annual')>Semi-Annual</option>
                                    <option value="annual" @selected(old('budget_period_type')==='annual')>Annual</option>
                                </select>
                            </div>
                            <div id="monthlySection" class="hidden">
                                <label class="block text-sm font-semibold text-gray-900 mb-2">Month</label>
                                <select id="fiscalMonth" name="fiscal_month" class="w-full h-14 px-4 rounded-xl border border-red-300 bg-white focus:outline-none focus:ring-2 focus:ring-red-400">
                                    <option value="">Select month</option>
                                    @foreach([
                                        1=>'January',
                                        2=>'February',
                                        3=>'March',
                                        4=>'April',
                                        5=>'May',
                                        6=>'June',
                                        7=>'July',
                                        8=>'August',
                                        9=>'September',
                                        10=>'October',
                                        11=>'November',
                                        12=>'December'
                                    ] as $monthNumber=>$monthName)
                                        <option value="{{ $monthNumber }}" @selected((string)old('fiscal_month')===(string)$monthNumber)>{{ $monthName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div id="quarterlySection" class="hidden">
                                <label class="block text-sm font-semibold text-gray-900 mb-2">Quarter</label>
                                <select id="fiscalQuarter" name="fiscal_quarter" class="w-full h-14 px-4 rounded-xl border border-red-300 bg-white focus:outline-none focus:ring-2 focus:ring-red-400">
                                    <option value="">Select quarter</option>
                                    <option value="Q1" @selected(old('fiscal_quarter')==='Q1')>Q1 - January to March</option>
                                    <option value="Q2" @selected(old('fiscal_quarter')==='Q2')>Q2 - April to June</option>
                                    <option value="Q3" @selected(old('fiscal_quarter')==='Q3')>Q3 - July to September</option>
                                    <option value="Q4" @selected(old('fiscal_quarter')==='Q4')>Q4 - October to December</option>
                                </select>
                            </div>
                            <div id="semiAnnualSection" class="hidden">
                                <label class="block text-sm font-semibold text-gray-900 mb-2">Semi-Annual Period</label>
                                <select id="fiscalHalf" name="fiscal_half" class="w-full h-14 px-4 rounded-xl border border-red-300 bg-white focus:outline-none focus:ring-2 focus:ring-red-400">
                                    <option value="">Select period</option>
                                    <option value="H1" @selected(old('fiscal_half')==='H1')>First Half - January to June</option>
                                    <option value="H2" @selected(old('fiscal_half')==='H2')>Second Half - July to December</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-lg font-semibold text-gray-900 mb-2">Submission Title</label>
                            <input type="text" id="submissionTitle" name="submission_title" maxlength="255" required class="w-full h-14 px-4 rounded-xl border border-red-300 bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-400" value="{{ old('submission_title') }}" placeholder="Enter the specific report or program title">
                        </div>
                        <div>
                            <label class="block text-lg font-semibold text-gray-900 mb-2">Description</label>
                            <input type="text" id="submissionDescription" name="description" maxlength="2000" required class="w-full h-14 px-4 rounded-xl border border-red-300 bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-400" value="{{ old('description') }}">
                        </div>
                        <div>
                            <label class="block text-lg font-semibold text-gray-900 mb-2">Who Can Submit</label>
                            <select id="submissionRole" name="submission_role" required class="w-full h-14 px-4 rounded-xl border border-red-300 bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-400">
                                <option value="SK Chairman" @selected(old('submission_role')==='SK Chairman')>SK Chairman</option>
                                <option value="SK Secretary" @selected(old('submission_role')==='SK Secretary')>SK Secretary</option>
                                <option value="Both" @selected(old('submission_role','Both')==='Both')>SK Chairman & SK Secretary</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-lg font-semibold text-gray-900 mb-2">Start Date</label>
                                <input type="date" id="startDate" name="start_date" required class="w-full h-14 px-4 rounded-xl border border-red-300 bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-400" value="{{ old('start_date',now('Asia/Manila')->toDateString()) }}">
                            </div>
                            <div>
                                <label class="block text-lg font-semibold text-gray-900 mb-2">Submission Deadline</label>
                                <input type="date" id="endDate" name="end_date" required class="w-full h-14 px-4 rounded-xl border border-red-300 bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-400" value="{{ old('end_date') }}">
                            </div>
                        </div>
                        <button id="slotSubmitButton" type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white text-2xl font-bold py-4 rounded-2xl transition">Create Slot</button>
                    </form>
                </div>
            </div>
            @php
                $summaryStyles=[
                    'Total Slots'=>['icon'=>'layout-grid','tone'=>''],
                    'Open Slots'=>['icon'=>'lock-open','tone'=>'green'],
                    'Past Deadline'=>['icon'=>'triangle-alert','tone'=>'yellow'],
                    'Closed Slots'=>['icon'=>'lock','tone'=>''],
                    'Current Submissions'=>['icon'=>'file-text','tone'=>'blue'],
                    'All-Time Total'=>['icon'=>'archive','tone'=>'yellow'],
                ];
            @endphp
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 mb-10">
                @foreach($summaryCards as $card)
                    @php $style=$summaryStyles[$card['label']] ?? ['icon'=>'layout-grid','tone'=>'']; @endphp
                    <div class="sk-stat">
                        <div class="sk-stat__top">
                            <div><p class="sk-stat__label">{{ $card['label'] }}</p><p class="sk-stat__value">{{ $card['value'] }}</p></div>
                            <span class="sk-icon-tile {{ $style['tone'] ? 'sk-icon-tile--'.$style['tone'] : '' }}">@include('partials.ui.icon',['icon'=>$style['icon'],'iconSize'=>21])</span>
                        </div>
                    </div>
                @endforeach
            </div>
            @php
                $accomplishmentCategoryLabels=[
                    'general'=>'General Accomplishment',
                    'youth_development_program'=>'Youth Development Program',
                    'kk_assembly'=>'KK Assembly',
                ];
                $budgetCategoryLabels=[
                    'annual_budget'=>'Annual Budget',
                    'supplemental_budget'=>'Supplemental Budget',
                    'coa_report'=>'Financial / COA Report',
                ];
                $budgetPeriodLabels=[
                    'monthly'=>'Monthly',
                    'quarterly'=>'Quarterly',
                    'semi_annual'=>'Semi-Annual',
                    'annual'=>'Annual',
                ];
                $monthNames=[
                    1=>'January',
                    2=>'February',
                    3=>'March',
                    4=>'April',
                    5=>'May',
                    6=>'June',
                    7=>'July',
                    8=>'August',
                    9=>'September',
                    10=>'October',
                    11=>'November',
                    12=>'December',
                ];
                $slotGroupSettings=[
                    'past_deadline'=>[
                        'title'=>'Past Deadline',
                        'description'=>'Deadline has passed. Late submissions may still be accepted until the slot is closed.',
                        'icon'=>'triangle-alert','tone'=>'orange',
                        'sectionBorder'=>'border-orange-200',
                        'sectionBg'=>'bg-orange-50/40',
                        'cardBorder'=>'border-orange-200',
                    ],
                    'open'=>[
                        'title'=>'Open Now',
                        'description'=>'Submission slots currently within their active submission period.',
                        'icon'=>'lock-open','tone'=>'green',
                        'sectionBorder'=>'border-green-200',
                        'sectionBg'=>'bg-green-50/30',
                        'cardBorder'=>'border-green-200',
                    ],
                    'upcoming'=>[
                        'title'=>'Upcoming',
                        'description'=>'Scheduled submission slots that have not started yet.',
                        'icon'=>'calendar-clock','tone'=>'blue',
                        'sectionBorder'=>'border-blue-200',
                        'sectionBg'=>'bg-blue-50/30',
                        'cardBorder'=>'border-blue-200',
                    ],
                    'closed'=>[
                        'title'=>'Closed',
                        'description'=>'Submission slots that no longer accept submissions.',
                        'icon'=>'lock','tone'=>'gray',
                        'sectionBorder'=>'border-gray-200',
                        'sectionBg'=>'bg-gray-50/70',
                        'cardBorder'=>'border-gray-200',
                    ],
                ];
            @endphp
            <div id="slotContainer" class="space-y-8">
                @foreach($slotGroupSettings as $groupKey=>$group)
                    @php
                        $groupSlots=$slotGroups[$groupKey];
                    @endphp
                    <section id="{{ $groupKey }}-slots" class="rounded-3xl border {{ $group['sectionBorder'] }} {{ $group['sectionBg'] }} overflow-hidden">
                        <div class="px-6 py-5 bg-white/80 border-b {{ $group['sectionBorder'] }} flex items-center justify-between gap-4">
                            <div class="flex items-start gap-3">
                                <span class="sk-icon-tile sk-icon-tile--sm {{ !empty($group['tone']) ? 'sk-icon-tile--'.$group['tone'] : '' }}">
                                    @include('partials.ui.icon', ['icon'=>$group['icon'],'iconSize'=>17])
                                </span>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">{{ $group['title'] }}</h3>
                                    <p class="text-sm text-gray-500 mt-1">{{ $group['description'] }}</p>
                                </div>
                            </div>
                            <span class="shrink-0 min-w-9 h-9 px-3 rounded-full bg-white border {{ $group['sectionBorder'] }} flex items-center justify-center text-sm font-bold text-gray-700">{{ $groupSlots->total() }}</span>
                        </div>
                        <div class="p-5">
                            <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
                                @forelse($groupSlots as $slot)
                                    @php
                                        $isBudget=$slot->submission_type==='budget_report';
                                        $isOpen=$slot->status==='open';
                                        $slotIcon=$isBudget ? 'wallet' : 'clipboard-list';
                                        $recordTone=!$isOpen ? 'sk-record--muted' : ($isBudget ? 'sk-record--blue' : ($groupKey==='past_deadline' ? 'sk-record--yellow' : ''));
                                    @endphp
                                    <article class="sk-record {{ $recordTone }}" data-slot-card data-status="{{ $slot->status }}" data-search="{{ \Illuminate\Support\Str::lower($slot->title.' '.$slot->description.' '.$slot->role) }}">
                                        <div class="flex items-start gap-4">
                                            <span class="sk-thumb {{ !$isOpen ? 'sk-thumb--muted' : ($isBudget ? 'sk-thumb--blue' : '') }}">
                                                @include('partials.ui.icon', ['icon'=>$slotIcon,'iconSize'=>24])
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="sk-overline">{{ $isBudget ? 'Budget / Financial Report' : 'Accomplishment Report' }}</p>
                                                    <span class="text-[10px] font-black uppercase px-2.5 py-1 rounded-full {{ $slot->management_state_badge ?? 'bg-gray-100 text-gray-600' }}">{{ $slot->management_state_label ?? ucfirst($slot->status) }}</span>
                                                </div>
                                                <h4 class="mt-1 text-[17px] font-bold leading-snug text-gray-900 break-words">{{ $slot->title }}</h4>
                                                @if(!empty($slot->description))
                                                    <p class="mt-1 text-sm leading-relaxed text-gray-500 break-words">{{ $slot->description }}</p>
                                                @endif
                                            </div>
                                            <button type="button" onclick="deleteSlot({{ $slot->slot_id }},@js($slot->title))" class="sk-icon-btn text-gray-400 hover:!text-red-600 hover:!bg-red-50" title="Delete slot" aria-label="Delete {{ $slot->title }}">
                                                @include('partials.ui.icon', ['icon'=>'trash-2','iconSize'=>18])
                                            </button>
                                        </div>
                                        @if($slot->submission_type==='accomplishment_report')
                                            <div class="flex flex-wrap gap-2">
                                                @if(!empty($slot->accomplishment_category))
                                                    <span class="sk-badge sk-badge--blue">{{ $accomplishmentCategoryLabels[$slot->accomplishment_category] ?? $slot->accomplishment_category }}</span>
                                                @endif
                                                @if($slot->accomplishment_category==='youth_development_program' && !empty($slot->ydp_program_type))
                                                    <span class="sk-badge sk-badge--green">{{ $ydpProgramTypes[$slot->ydp_program_type] ?? $slot->ydp_program_type }}</span>
                                                @endif
                                            </div>
                                        @endif
                                        @if($slot->submission_type==='budget_report')
                                            <div class="flex flex-wrap gap-2">
                                                @if(!empty($slot->budget_category))
                                                    <span class="sk-badge sk-badge--red">{{ $budgetCategoryLabels[$slot->budget_category] ?? $slot->budget_category }}</span>
                                                @endif
                                                @if(!empty($slot->fiscal_year))
                                                    <span class="sk-badge sk-badge--blue">FY {{ $slot->fiscal_year }}</span>
                                                @endif
                                                @if($slot->budget_category==='coa_report' && !empty($slot->budget_period_type))
                                                    <span class="sk-badge sk-badge--yellow">
                                                        {{ $budgetPeriodLabels[$slot->budget_period_type] ?? $slot->budget_period_type }}
                                                        @if($slot->budget_period_type==='monthly' && !empty($slot->fiscal_month))
                                                            - {{ $monthNames[(int)$slot->fiscal_month] ?? '' }}
                                                        @elseif($slot->budget_period_type==='quarterly' && !empty($slot->fiscal_quarter))
                                                            - {{ $slot->fiscal_quarter }}
                                                        @elseif($slot->budget_period_type==='semi_annual' && !empty($slot->fiscal_half))
                                                            - {{ $slot->fiscal_half==='H1' ? 'First Half' : 'Second Half' }}
                                                        @endif
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4">
                                            <div class="sk-meta">
                                                <span class="sk-meta__item">@include('partials.ui.icon', ['icon'=>'calendar-days','iconSize'=>16]) {{ \Carbon\Carbon::parse($slot->start_date)->format('M d, Y') }} &ndash; {{ \Carbon\Carbon::parse($slot->end_date)->format('M d, Y') }}</span>
                                                <span class="sk-meta__item">@include('partials.ui.icon', ['icon'=>'users','iconSize'=>16]) {{ $slot->role }}</span>
                                                <span class="sk-meta__item text-gray-400">Slot #{{ $slot->slot_id }}</span>
                                            </div>
                                            <span class="sk-badge sk-badge--dot {{ $isOpen ? 'sk-badge--green' : 'sk-badge--gray' }}">{{ $slot->management_state_label ?? ucfirst($slot->status) }}</span>
                                        </div>
                                        <div class="flex flex-wrap justify-end gap-2 border-t border-gray-100 pt-4">
                                            @if($slot->status==='open')
                                                <button type="button" class="edit-slot-btn sk-btn sk-btn--secondary sk-btn--sm"
                                                    data-slot-id="{{ $slot->slot_id }}"
                                                    data-update-url="{{ route('sk_pres.module.update',$slot->slot_id) }}"
                                                    data-submission-type="{{ $slot->submission_type }}"
                                                    data-accomplishment-category="{{ $slot->accomplishment_category }}"
                                                    data-ydp-program-type="{{ $slot->ydp_program_type }}"
                                                    data-budget-category="{{ $slot->budget_category }}"
                                                    data-fiscal-year="{{ $slot->fiscal_year }}"
                                                    data-budget-period-type="{{ $slot->budget_period_type }}"
                                                    data-fiscal-month="{{ $slot->fiscal_month }}"
                                                    data-fiscal-quarter="{{ $slot->fiscal_quarter }}"
                                                    data-fiscal-half="{{ $slot->fiscal_half }}"
                                                    data-title="{{ $slot->title }}"
                                                    data-description="{{ $slot->description }}"
                                                    data-role="{{ $slot->role }}"
                                                    data-start-date="{{ $slot->start_date }}"
                                                    data-end-date="{{ $slot->end_date }}">@include('partials.ui.icon', ['icon'=>'pencil','iconSize'=>14]) Edit</button>
                                                <button type="button" onclick="closeSlot({{ $slot->slot_id }},@js($slot->title))" class="sk-btn sk-btn--dark sk-btn--sm">@include('partials.ui.icon', ['icon'=>'lock','iconSize'=>14]) Close Slot</button>
                                            @endif
                                        </div>
                                        @if($slot->status==='open')
                                            <form id="close-slot-{{ $slot->slot_id }}" method="POST" action="{{ route('sk_pres.module.close',$slot->slot_id) }}" class="hidden">
                                                @csrf
                                                @method('PATCH')
                                            </form>
                                        @endif
                                        <form id="delete-slot-{{ $slot->slot_id }}" method="POST" action="{{ route('sk_pres.module.destroy',$slot->slot_id) }}" class="hidden">@csrf</form>
                                    </article>
                                @empty
                                    <div class="xl:col-span-2 rounded-2xl border border-dashed {{ $group['sectionBorder'] }} bg-white/60 p-8 text-center text-sm text-gray-400">No {{ strtolower($group['title']) }} submission slots.</div>
                                @endforelse
                            </div>
                            @if($groupSlots->hasPages())
                                <div class="mt-6 pt-5 border-t {{ $group['sectionBorder'] }}">{{ $groupSlots->onEachSide(1)->fragment($groupKey.'-slots')->links() }}</div>
                            @endif
                        </div>
                    </section>
                @endforeach
            </div>
        </main>
    </div>
</div>
@endsection
@push('scripts')
@vite(['resources/js/app.js'])
<script>
const notifBtn=document.getElementById('notifBtn');
const notifDropdown=document.getElementById('notifDropdown');
const userMenuBtn=document.getElementById('userMenuBtn');
const userDropdown=document.getElementById('userDropdown');
const openModalBtn=document.getElementById('openModalBtn');
const closeModalBtn=document.getElementById('closeModalBtn');
const submissionModal=document.getElementById('submissionModal');
const slotForm=document.getElementById('slotForm');
const slotFormMethod=document.getElementById('slotFormMethod');
const slotFormContext=document.getElementById('slotFormContext');
const editingSlotId=document.getElementById('editingSlotId');
const slotModalTitle=document.getElementById('slotModalTitle');
const slotModalDescription=document.getElementById('slotModalDescription');
const slotSubmitButton=document.getElementById('slotSubmitButton');
const submissionType=document.getElementById('submissionType');
const accomplishmentDetails=document.getElementById('accomplishmentDetails');
const accomplishmentCategory=document.getElementById('accomplishmentCategory');
const ydpProgramSection=document.getElementById('ydpProgramSection');
const ydpProgramType=document.getElementById('ydpProgramType');
const budgetDetails=document.getElementById('budgetDetails');
const budgetCategory=document.getElementById('budgetCategory');
const fiscalYear=document.getElementById('fiscalYear');
const reportPeriodSection=document.getElementById('reportPeriodSection');
const budgetPeriodType=document.getElementById('budgetPeriodType');
const monthlySection=document.getElementById('monthlySection');
const quarterlySection=document.getElementById('quarterlySection');
const semiAnnualSection=document.getElementById('semiAnnualSection');
const fiscalMonth=document.getElementById('fiscalMonth');
const fiscalQuarter=document.getElementById('fiscalQuarter');
const fiscalHalf=document.getElementById('fiscalHalf');
const submissionTitle=document.getElementById('submissionTitle');
const submissionDescription=document.getElementById('submissionDescription');
const submissionRole=document.getElementById('submissionRole');
const startDate=document.getElementById('startDate');
const endDate=document.getElementById('endDate');
const lydpUploadForm=document.getElementById('lydpUploadForm');
const lydpFile=document.getElementById('lydpFile');
const createSlotUrl=@js(route('sk_pres.module.store'));
const todayDate=@js(now('Asia/Manila')->toDateString());
function syncAccomplishmentFields(){
    const isAccomplishment=submissionType.value==='accomplishment_report';
    const isYdp=isAccomplishment && accomplishmentCategory.value==='youth_development_program';
    accomplishmentDetails.classList.toggle('hidden',!isAccomplishment);
    accomplishmentCategory.required=isAccomplishment;
    ydpProgramSection.classList.toggle('hidden',!isYdp);
    ydpProgramType.required=isYdp;
}
function syncBudgetFields(){
    const isBudget=submissionType.value==='budget_report';
    const isCoaReport=isBudget && budgetCategory.value==='coa_report';
    const period=budgetPeriodType.value;
    const isMonthly=isCoaReport && period==='monthly';
    const isQuarterly=isCoaReport && period==='quarterly';
    const isSemiAnnual=isCoaReport && period==='semi_annual';
    budgetDetails.classList.toggle('hidden',!isBudget);
    budgetCategory.required=isBudget;
    fiscalYear.required=isBudget;
    reportPeriodSection.classList.toggle('hidden',!isCoaReport);
    budgetPeriodType.required=isCoaReport;
    monthlySection.classList.toggle('hidden',!isMonthly);
    quarterlySection.classList.toggle('hidden',!isQuarterly);
    semiAnnualSection.classList.toggle('hidden',!isSemiAnnual);
    fiscalMonth.required=isMonthly;
    fiscalQuarter.required=isQuarterly;
    fiscalHalf.required=isSemiAnnual;
}
function syncSlotFields(){
    syncAccomplishmentFields();
    syncBudgetFields();
}
notifBtn.addEventListener('click',function(e){
    e.stopPropagation();
    notifDropdown.classList.toggle('hidden');
    userDropdown.classList.add('hidden');
});
userMenuBtn.addEventListener('click',function(e){
    e.stopPropagation();
    userDropdown.classList.toggle('hidden');
    notifDropdown.classList.add('hidden');
});
document.addEventListener('click',function(e){
    if(!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)){
        notifDropdown.classList.add('hidden');
    }
    if(!userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)){
        userDropdown.classList.add('hidden');
    }
});
function openSlotModal(){
    submissionModal.classList.remove('hidden');
    submissionModal.classList.add('flex');
    syncSlotFields();
}
function closeSlotModal(){
    submissionModal.classList.add('hidden');
    submissionModal.classList.remove('flex');
}
function openCreateSlotModal(){
    slotForm.reset();
    slotForm.action=createSlotUrl;
    slotFormMethod.disabled=true;
    slotFormContext.value='create';
    editingSlotId.value='';
    slotModalTitle.textContent='Create New Submission Slot';
    slotModalDescription.textContent='Set up a new submission period for SK officials to submit reports';
    slotSubmitButton.textContent='Create Slot';
    fiscalYear.value=new Date().getFullYear();
    submissionType.value='accomplishment_report';
    accomplishmentCategory.value='general';
    ydpProgramType.value='';
    budgetCategory.value='';
    budgetPeriodType.value='';
    fiscalMonth.value='';
    fiscalQuarter.value='';
    fiscalHalf.value='';
    submissionTitle.value='';
    submissionDescription.value='';
    submissionRole.value='Both';
    startDate.value=todayDate;
    endDate.value='';
    openSlotModal();
}
function setFieldValue(field,value){
    field.value=value || '';
}
function openEditSlotModal(button){
    slotForm.reset();
    slotForm.action=button.dataset.updateUrl;
    slotFormMethod.disabled=false;
    slotFormContext.value='edit';
    editingSlotId.value=button.dataset.slotId || '';
    slotModalTitle.textContent='Edit Submission Slot';
    slotModalDescription.textContent='Update the deadline, description, authorized role, or allowed submission settings.';
    slotSubmitButton.textContent='Save Changes';
    setFieldValue(submissionType,button.dataset.submissionType);
    setFieldValue(accomplishmentCategory,button.dataset.accomplishmentCategory || 'general');
    setFieldValue(ydpProgramType,button.dataset.ydpProgramType);
    setFieldValue(budgetCategory,button.dataset.budgetCategory);
    setFieldValue(fiscalYear,button.dataset.fiscalYear);
    setFieldValue(budgetPeriodType,button.dataset.budgetPeriodType);
    setFieldValue(fiscalMonth,button.dataset.fiscalMonth);
    setFieldValue(fiscalQuarter,button.dataset.fiscalQuarter);
    setFieldValue(fiscalHalf,button.dataset.fiscalHalf);
    setFieldValue(submissionTitle,button.dataset.title);
    setFieldValue(submissionDescription,button.dataset.description);
    setFieldValue(submissionRole,button.dataset.role);
    setFieldValue(startDate,button.dataset.startDate);
    setFieldValue(endDate,button.dataset.endDate);
    openSlotModal();
}
openModalBtn.addEventListener('click',openCreateSlotModal);
closeModalBtn.addEventListener('click',closeSlotModal);
submissionModal.addEventListener('click',function(e){
    if(e.target===submissionModal){
        closeSlotModal();
    }
});
document.querySelectorAll('.edit-slot-btn').forEach(button=>{
    button.addEventListener('click',()=>openEditSlotModal(button));
});
submissionType.addEventListener('change',syncSlotFields);
accomplishmentCategory.addEventListener('change',syncAccomplishmentFields);
budgetCategory.addEventListener('change',syncBudgetFields);
budgetPeriodType.addEventListener('change',syncBudgetFields);
function fieldLabel(field){
    const labels={
        submission_type:'Submission Type',
        accomplishment_category:'Accomplishment Category',
        ydp_program_type:'YDP Program Type',
        budget_category:'Report Category',
        fiscal_year:'Fiscal Year',
        budget_period_type:'Reporting Period',
        fiscal_month:'Month',
        fiscal_quarter:'Quarter',
        fiscal_half:'Semi-Annual Period',
        submission_title:'Submission Title',
        description:'Description',
        submission_role:'Who Can Submit',
        start_date:'Start Date',
        end_date:'Submission Deadline'
    };
    return labels[field.name] || field.name;
}
lydpUploadForm?.addEventListener('submit',function(e){
    const file=lydpFile?.files?.[0];
    const isPdf=file && (file.type==='application/pdf' || file.name.toLowerCase().endsWith('.pdf'));
    if(isPdf){
        return;
    }
    e.preventDefault();
    Swal.fire({
        icon:'warning',
        title:'LYDP Upload Rejected',
        text:file ? 'The LYDP document must be a PDF file.' : 'Please select an LYDP PDF to publish.',
        confirmButtonColor:'#2563eb'
    }).then(()=>{
        lydpFile?.focus();
    });
});
slotForm.addEventListener('submit',function(e){
    syncSlotFields();
    const invalidFields=Array.from(slotForm.querySelectorAll('[required]')).filter(field=>!field.checkValidity());
    if(invalidFields.length===0 && slotForm.checkValidity()){
        return;
    }
    e.preventDefault();
    const labels=[...new Set(invalidFields.map(field=>fieldLabel(field)))];
    Swal.fire({
        icon:'warning',
        title:'Incomplete or Invalid Fields',
        html:labels.length
            ? `Please complete or correct:<br><strong>${labels.join(', ')}</strong>`
            : 'Please complete all required fields correctly.',
        confirmButtonColor:'#dc2626'
    }).then(()=>{
        invalidFields[0]?.focus();
        slotForm.reportValidity();
    });
});
function closeSlot(id,title){
    Swal.fire({
        title:'Close Submission Slot?',
        text:`"${title}" will stop accepting new submissions.`,
        icon:'warning',
        showCancelButton:true,
        confirmButtonColor:'#111827',
        confirmButtonText:'Close Slot'
    }).then(result=>{
        if(result.isConfirmed){
            document.getElementById(`close-slot-${id}`).submit();
        }
    });
}
function deleteSlot(id,title){
    Swal.fire({
        title:'Delete Slot?',
        text:`"${title}" will be permanently removed.`,
        icon:'warning',
        showCancelButton:true,
        confirmButtonColor:'#dc2626',
        confirmButtonText:'Delete'
    }).then(result=>{
        if(result.isConfirmed){
            document.getElementById(`delete-slot-${id}`).submit();
        }
    });
}
document.addEventListener('DOMContentLoaded',syncSlotFields);
@if($errors->lydpUpload->any())
Swal.fire({
    icon:'error',
    title:'LYDP Upload Rejected',
    html:{!! json_encode(implode('<br>',$errors->lydpUpload->all())) !!},
    confirmButtonColor:'#2563eb'
}).then(()=>{
    document.getElementById('lydp-public-document')?.scrollIntoView({
        behavior:'smooth',
        block:'center'
    });
});
@endif
@if($errors->getBag('default')->any())
slotForm.action=@js(old('form_context')==='edit' && old('editing_slot_id')
    ? route('sk_pres.module.update',old('editing_slot_id'))
    : route('sk_pres.module.store'));
slotFormMethod.disabled={{ old('form_context')==='edit' && old('editing_slot_id') ? 'false' : 'true' }};
slotFormContext.value=@js(old('form_context','create'));
editingSlotId.value=@js(old('editing_slot_id',''));
slotModalTitle.textContent=@js(old('form_context')==='edit' ? 'Edit Submission Slot' : 'Create New Submission Slot');
slotModalDescription.textContent=@js(old('form_context')==='edit'
    ? 'Correct the invalid fields. The previous saved slot data has not been changed.'
    : 'Complete the required fields to create a submission slot.');
slotSubmitButton.textContent=@js(old('form_context')==='edit' ? 'Save Changes' : 'Create Slot');
submissionModal.classList.remove('hidden');
submissionModal.classList.add('flex');
syncSlotFields();
Swal.fire({
    icon:'warning',
    title:'Incomplete or Invalid Fields',
    html:{!! json_encode(implode('<br>',$errors->getBag('default')->all())) !!},
    confirmButtonColor:'#dc2626'
});
@endif
</script>
@endpush