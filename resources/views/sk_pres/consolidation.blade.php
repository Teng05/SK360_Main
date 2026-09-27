{{-- File guide: Blade view template for resources/views/sk_pres/consolidation.blade.php. --}}
@extends('layouts.app')

@section('title', 'Report Consolidation')

@section('page_css')
<script src="https://cdn.tailwindcss.com"></script>
@endsection

@section('content')
@php
    $statStyles=[
        'Total Barangays'=>['icon'=>'building-2','tone'=>'blue'],
        'Submitted'=>['icon'=>'circle-check','tone'=>'green'],
        'Pending'=>['icon'=>'hourglass','tone'=>'yellow'],
        'Late'=>['icon'=>'triangle-alert','tone'=>''],
    ];
@endphp
<div class="flex h-screen overflow-hidden bg-gray-100">
        @include('partials.app.sidebar')


    <div class="flex-1 flex flex-col">
                @include('partials.app.topbar')


        <main class="flex-1 overflow-y-auto p-10 bg-gray-100">
            <div class="sk-page-head">
                <div class="sk-page-head__text">
                    <span class="sk-eyebrow"><span class="sk-dot"></span>Consolidation</span>
                    <h1 class="sk-page-title">Report Consolidation</h1>
                    <p class="sk-page-subtitle">Automatically compile barangay reports into unified monthly, quarterly, and annual documents.</p>
                </div>
                <div class="sk-page-head__actions">
                    <a href="{{ $downloadRoute }}" class="sk-btn sk-btn--primary sk-btn--lg">
                        @include('partials.ui.icon', ['icon'=>'download','iconSize'=>18])
                        Download Consolidated PDF
                    </a>
                </div>
            </div>

            @if(session('quality_status'))
                <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-700">
                    {{ session('quality_status') }}
                </div>
            @endif

            @if($errors->has('quality_review'))
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
                    {{ $errors->first('quality_review') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
                @foreach($stats as $stat)
                    @php $style=$statStyles[$stat['label']] ?? ['icon'=>'layout-grid','tone'=>'']; @endphp
                    <div class="sk-stat">
                        <div class="sk-stat__top">
                            <div><p class="sk-stat__label">{{ $stat['label'] }}</p><p class="sk-stat__value {{ $stat['valueClass'] }}">{{ $stat['value'] }}</p></div>
                            <span class="sk-icon-tile {{ $style['tone'] ? 'sk-icon-tile--'.$style['tone'] : '' }}">@include('partials.ui.icon',['icon'=>$style['icon'],'iconSize'=>21])</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <section class="sk-card overflow-hidden">
                <div class="px-6 pt-6">
                    <h2 class="sk-section-title">Barangay Submissions</h2>
                    <p class="sk-section-subtitle">Review citywide report completion and archive consolidated outputs.</p>
                </div>

                <form method="GET" action="{{ route('sk_pres.consolidation') }}" class="mx-6 mt-5 flex flex-col xl:flex-row xl:items-end gap-4 rounded-2xl border border-gray-100 bg-[#f8f9fb] p-4">
                    <div class="w-full xl:max-w-xs">
                        <label for="barangaySearch" class="sk-overline block mb-2">Search Barangay</label>
                        <div class="sk-search">
                            @include('partials.ui.icon', ['icon'=>'search','iconSize'=>18])
                            <input id="barangaySearch" type="text" placeholder="Search barangay..." class="!bg-white !border-gray-200">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 flex-1">
                        <div>
                            <label class="sk-overline block mb-2">Year</label>
                            <select name="year" class="w-full h-11 rounded-xl border border-gray-200 bg-white px-3.5 text-sm font-semibold text-gray-700">
                                @foreach($years as $year)
                                    <option value="{{ $year }}" {{ (int)$filters['year']===(int)$year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="sk-overline block mb-2">Period</label>
                            <select name="period" id="periodFilter" class="w-full h-11 rounded-xl border border-gray-200 bg-white px-3.5 text-sm font-semibold text-gray-700">
                                <option value="all" {{ $filters['period']==='all' ? 'selected' : '' }}>All Reports</option>
                                <option value="monthly" {{ $filters['period']==='monthly' ? 'selected' : '' }}>Monthly</option>
                                <option value="quarterly" {{ $filters['period']==='quarterly' ? 'selected' : '' }}>Quarterly</option>
                                <option value="annual" {{ $filters['period']==='annual' ? 'selected' : '' }}>Annual</option>
                            </select>
                        </div>
                        <div id="monthFilterWrap">
                            <label class="sk-overline block mb-2">Month</label>
                            <select name="month" class="w-full h-11 rounded-xl border border-gray-200 bg-white px-3.5 text-sm font-semibold text-gray-700">
                                @foreach($months as $number=>$month)
                                    <option value="{{ $number }}" {{ (int)$filters['month']===(int)$number ? 'selected' : '' }}>{{ $month }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="quarterFilterWrap">
                            <label class="sk-overline block mb-2">Quarter</label>
                            <select name="quarter" class="w-full h-11 rounded-xl border border-gray-200 bg-white px-3.5 text-sm font-semibold text-gray-700">
                                @foreach($quarters as $quarter)
                                    <option value="{{ $quarter }}" {{ $filters['quarter']===$quarter ? 'selected' : '' }}>{{ $quarter }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="sk-btn sk-btn--primary">
                            @include('partials.ui.icon', ['icon'=>'filter','iconSize'=>16])
                            Apply
                        </button>
                        <a href="{{ route('sk_pres.consolidation') }}" class="sk-btn sk-btn--secondary">Reset</a>
                    </div>
                </form>

                <div class="sk-alert sk-alert--info mx-6 mt-4">
                    @include('partials.ui.icon', ['icon'=>'info','iconSize'=>18])
                    <span>This module compiles barangay accomplishment reports into one citywide view for monthly, quarterly, and annual monitoring.</span>
                </div>

                <div class="overflow-x-auto mt-5 border-t border-gray-100">
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr>
                                <th class="px-6 py-3.5">Barangay</th>
                                <th class="px-6 py-3.5">Monthly</th>
                                <th class="px-6 py-3.5">Quarterly</th>
                                <th class="px-6 py-3.5">Annual</th>
                                <th class="px-6 py-3.5">Last Submission</th>
                                <th class="px-6 py-3.5 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="submissionRows">
                            @forelse($submissions as $submission)
                                <tr data-barangay="{{ strtolower($submission['barangay']) }}">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <span class="sk-icon-tile sk-icon-tile--sm sk-icon-tile--gray">
                                                @include('partials.ui.icon', ['icon'=>'building-2','iconSize'=>16])
                                            </span>
                                            <span class="font-bold text-gray-900">Barangay {{ $submission['barangay'] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4"><span class="sk-badge {{ $submission['monthly_count']>0 ? 'sk-badge--green' : 'sk-badge--yellow' }}">{{ $submission['monthly'] }}</span></td>
                                    <td class="px-6 py-4"><span class="sk-badge {{ $submission['quarterly_count']>0 ? 'sk-badge--green' : 'sk-badge--yellow' }}">{{ $submission['quarterly'] }}</span></td>
                                    <td class="px-6 py-4"><span class="sk-badge {{ $submission['annual_count']>0 ? 'sk-badge--green' : 'sk-badge--yellow' }}">{{ $submission['annual'] }}</span></td>
                                    <td class="px-6 py-4 font-semibold text-gray-600 whitespace-nowrap">{{ $submission['last_submission'] }}</td>
                                    <td class="px-6 py-4 text-center"><span class="sk-badge sk-badge--dot {{ $submission['status']==='submitted' ? 'sk-badge--green' : 'sk-badge--yellow' }} capitalize">{{ $submission['status'] }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="sk-empty">
                                            <span class="sk-icon-tile sk-icon-tile--gray">@include('partials.ui.icon',['icon'=>'inbox','iconSize'=>24])</span>
                                            <p class="sk-empty__text">No barangay submissions yet.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                            <tr id="submissionNoResults" class="hidden">
                                <td colspan="6" class="px-6 py-10 text-center text-gray-400">No barangay matched your search.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div id="submissionPagination" class="px-6 py-5 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <p id="submissionPaginationInfo" class="text-xs text-gray-500"></p>
                    <div class="flex items-center gap-2">
                        <button id="submissionPrevPage" type="button" class="sk-btn sk-btn--secondary !px-3 !py-2 disabled:opacity-40 disabled:cursor-not-allowed">Previous</button>
                        <div id="submissionPageButtons" class="flex items-center gap-1"></div>
                        <button id="submissionNextPage" type="button" class="sk-btn sk-btn--secondary !px-3 !py-2 disabled:opacity-40 disabled:cursor-not-allowed">Next</button>
                    </div>
                </div>
            </section>

            @php
                $focusType=(string)request()->query('focus_type','');
                $focusId=(int)request()->query('focus_id',0);
            @endphp

            <section id="qualityDocumentationSection" class="sk-card p-6 mt-8 mb-10">
                <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-5 mb-6">
                    <div class="flex items-start gap-3">
                        <span class="sk-icon-tile">
                            @include('partials.ui.icon', ['icon'=>'shield-check','iconSize'=>21])
                        </span>
                        <div>
                            <h2 class="sk-section-title">Document Quality Review</h2>
                            <p class="sk-section-subtitle">Review submitted documents before awarding Document Quality points.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 w-full xl:w-auto">
                        <div class="sm:min-w-[220px]">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Search Barangay</label>

                            <div class="border rounded-xl px-4 py-3 flex items-center gap-3 bg-white">
                                <span class="text-gray-400">@include('partials.ui.icon', ['icon'=>'search','iconSize'=>16])</span>
                                <input id="qualityBarangaySearch" type="text" placeholder="Search barangay..." class="w-full outline-none text-sm">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Review Status</label>

                            <select id="qualityStatusFilter" class="w-full border rounded-xl px-4 py-3 text-sm text-gray-600 outline-none bg-white">
                                <option value="all">All Statuses</option>
                                <option value="pending">Pending Review</option>
                                <option value="needs_revision">Needs Revision</option>
                                <option value="approved">Approved</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Document Type</label>

                            <select id="qualityTypeFilter" class="w-full border rounded-xl px-4 py-3 text-sm text-gray-600 outline-none bg-white">
                                <option value="all">All Documents</option>
                                <option value="accomplishment_report">Accomplishment</option>
                                <option value="budget_report">Budget / COA</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-blue-100 bg-blue-50 px-5 py-4 text-sm text-blue-800 mb-6">
                    Documents are grouped by barangay. Expand a barangay to review its accomplishment and budget/COA submissions.
                </div>

                <div id="qualityGroups" class="space-y-4">
                    @forelse($qualityGroups as $group)
                        @php
                            $groupFocused=$group['items']->contains(fn($item)=>$focusType===(string)$item->source_type && $focusId===(int)$item->source_id);
                        @endphp

                        <div class="quality-group border border-gray-200 rounded-2xl overflow-hidden {{ $groupFocused ? 'ring-4 ring-yellow-100 border-yellow-300' : '' }}" data-quality-group data-barangay="{{ strtolower($group['barangay_name']) }}">
                            <button type="button" class="quality-group-toggle w-full flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 p-5 text-left hover:bg-gray-50 transition" data-quality-toggle>
                                <div class="flex items-center gap-4 min-w-0">
                                    <span class="sk-icon-tile sk-icon-tile--lg shrink-0">
                                        @include('partials.ui.icon',['icon'=>'file-text','iconSize'=>21])
                                    </span>

                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="font-bold text-gray-900">Barangay {{ $group['barangay_name'] }}</h3>

                                            <span class="bg-gray-100 text-gray-600 rounded-full px-2.5 py-1 text-[10px] font-bold">
                                                {{ $group['total'] }} {{ $group['total']===1 ? 'Document' : 'Documents' }}
                                            </span>
                                        </div>

                                        <div class="flex flex-wrap gap-2 mt-2 text-[10px] font-bold">
                                            <span class="bg-yellow-100 text-yellow-700 rounded-full px-2.5 py-1">{{ $group['pending'] }} Pending</span>
                                            <span class="bg-red-100 text-red-700 rounded-full px-2.5 py-1">{{ $group['needs_revision'] }} Needs Revision</span>
                                            <span class="bg-green-100 text-green-700 rounded-full px-2.5 py-1">{{ $group['approved'] }} Approved</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 shrink-0">
                                    <span class="text-xs text-gray-400">View documents</span>
                                    <span class="quality-group-arrow text-gray-400 text-lg transition-transform {{ $groupFocused ? 'rotate-180' : '' }}">⌄</span>
                                </div>
                            </button>

                            <div class="quality-group-body border-t border-gray-100 bg-gray-50/50 p-5 {{ $groupFocused ? '' : 'hidden' }}">
                                @foreach([
                                    ['key'=>'accomplishment_reports','label'=>'Accomplishment Reports','type'=>'accomplishment_report'],
                                    ['key'=>'budget_reports','label'=>'Budget / COA Reports','type'=>'budget_report'],
                                ] as $section)
                                    @php
                                        $sectionItems=$group[$section['key']];
                                    @endphp

                                    <div class="quality-type-section {{ !$loop->first ? 'mt-6' : '' }}" data-quality-type-section="{{ $section['type'] }}">
                                        <div class="flex items-center justify-between gap-3 mb-3">
                                            <h4 class="text-[11px] font-black uppercase tracking-widest text-gray-500">{{ $section['label'] }}</h4>
                                            <span class="text-[10px] text-gray-400">{{ $sectionItems->count() }} {{ $sectionItems->count()===1 ? 'record' : 'records' }}</span>
                                        </div>

                                        <div class="space-y-4">
                                            @forelse($sectionItems as $item)
                                                @php
                                                    $status=$item->quality_status ?? 'pending';

                                                    $statusClass=match($status){
                                                        'approved'=>'bg-green-100 text-green-700',
                                                        'needs_revision'=>'bg-red-100 text-red-700',
                                                        default=>'bg-yellow-100 text-yellow-700',
                                                    };

                                                    $statusLabel=match($status){
                                                        'approved'=>'Approved',
                                                        'needs_revision'=>'Needs Revision',
                                                        default=>'Pending Review',
                                                    };

                                                    $isFocused=$focusType===(string)$item->source_type && $focusId===(int)$item->source_id;
                                                @endphp

                                                <div
                                                    id="quality-submission-{{ $item->source_type }}-{{ $item->source_id }}"
                                                    data-quality-item
                                                    data-status="{{ $status }}"
                                                    data-type="{{ $item->source_type }}"
                                                    data-focus-target="{{ $isFocused ? '1' : '0' }}"
                                                    class="bg-white border rounded-2xl p-5 transition-all duration-500 {{ $isFocused ? 'border-yellow-400 bg-yellow-50 ring-4 ring-yellow-200 shadow-lg' : 'border-gray-200' }}"
                                                >
                                                    @if($isFocused)
                                                        <div class="mb-4 flex items-center gap-2 rounded-xl border border-yellow-200 bg-yellow-100 px-4 py-3 text-sm font-semibold text-yellow-800">
                                                            @include('partials.ui.icon', ['icon'=>'bell','iconSize'=>15])
                                                            <span>This is the submission from the notification you opened.</span>
                                                        </div>
                                                    @endif

                                                    <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-4 mb-5">
                                                        <div>
                                                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                                                <p class="text-sm text-gray-800 font-bold">{{ $item->title ?: $item->source_label }}</p>

                                                                <span class="{{ $statusClass }} rounded-full px-3 py-1 text-[10px] font-bold uppercase">
                                                                    {{ $statusLabel }}
                                                                </span>
                                                            </div>

                                                            <div class="flex flex-wrap gap-x-5 gap-y-1 text-xs text-gray-500">
                                                                <span>{{ $item->source_label }}</span>
                                                                <span>{{ $item->period_label }}</span>
                                                                <span>Submitted: {{ $item->submitted_label }}</span>
                                                            </div>
                                                        </div>

                                                        @if($item->file_url)
                                                            <a href="{{ $item->file_url }}" target="_blank" rel="noopener noreferrer" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-xl text-sm font-semibold whitespace-nowrap">
                                                                View Document
                                                            </a>
                                                        @else
                                                            <span class="bg-gray-100 text-gray-400 px-4 py-2 rounded-xl text-sm font-semibold whitespace-nowrap">
                                                                No File Available
                                                            </span>
                                                        @endif
                                                    </div>

                                                    @if($status==='approved')
                                                        <div class="rounded-xl bg-green-50 border border-green-100 p-4">
                                                            <p class="text-sm font-semibold text-green-700">Document Approved</p>

                                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mt-3 text-xs">
                                                                <span class="{{ $item->complete_contents ? 'text-green-700' : 'text-gray-400' }}">
                                                                    {{ $item->complete_contents ? '✓' : '—' }} Complete Contents
                                                                </span>

                                                                <span class="{{ $item->correct_document ? 'text-green-700' : 'text-gray-400' }}">
                                                                    {{ $item->correct_document ? '✓' : '—' }} Correct Document
                                                                </span>

                                                                <span class="{{ $item->correct_period ? 'text-green-700' : 'text-gray-400' }}">
                                                                    {{ $item->correct_period ? '✓' : '—' }} Correct Period
                                                                </span>
                                                            </div>

                                                            @if($item->quality_remarks)
                                                                <p class="text-xs text-gray-600 mt-3">Remarks: {{ $item->quality_remarks }}</p>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <form method="POST" action="{{ route('sk_pres.consolidation.quality-review') }}">
                                                            @csrf
                                                            <input type="hidden" name="source_type" value="{{ $item->source_type }}">
                                                            <input type="hidden" name="source_id" value="{{ $item->source_id }}">

                                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                                                <label class="flex items-start gap-2 border rounded-xl p-3 cursor-pointer hover:bg-gray-50">
                                                                    <input type="hidden" name="complete_contents" value="0">
                                                                    <input type="checkbox" name="complete_contents" value="1" class="mt-1" {{ $item->complete_contents ? 'checked' : '' }}>

                                                                    <span>
                                                                        <span class="block text-xs font-bold text-gray-700">Complete Contents</span>
                                                                        <span class="text-[10px] text-gray-400">Required content is present.</span>
                                                                    </span>
                                                                </label>

                                                                <label class="flex items-start gap-2 border rounded-xl p-3 cursor-pointer hover:bg-gray-50">
                                                                    <input type="hidden" name="correct_document" value="0">
                                                                    <input type="checkbox" name="correct_document" value="1" class="mt-1" {{ $item->correct_document ? 'checked' : '' }}>

                                                                    <span>
                                                                        <span class="block text-xs font-bold text-gray-700">Correct Document</span>
                                                                        <span class="text-[10px] text-gray-400">Matches the required submission.</span>
                                                                    </span>
                                                                </label>

                                                                <label class="flex items-start gap-2 border rounded-xl p-3 cursor-pointer hover:bg-gray-50">
                                                                    <input type="hidden" name="correct_period" value="0">
                                                                    <input type="checkbox" name="correct_period" value="1" class="mt-1" {{ $item->correct_period ? 'checked' : '' }}>

                                                                    <span>
                                                                        <span class="block text-xs font-bold text-gray-700">Correct Period</span>
                                                                        <span class="text-[10px] text-gray-400">Reporting period and details are correct.</span>
                                                                    </span>
                                                                </label>
                                                            </div>

                                                            <div class="mt-4">
                                                                <label class="block text-xs font-bold text-gray-600 mb-2">Review Remarks</label>

                                                                <textarea
                                                                    name="remarks"
                                                                    rows="3"
                                                                    placeholder="Add remarks, especially when revision is needed..."
                                                                    class="w-full border rounded-xl px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-red-100"
                                                                >{{ $item->quality_remarks }}</textarea>
                                                            </div>

                                                            <div class="flex flex-wrap justify-end gap-3 mt-4">
                                                                <button type="submit" name="status" value="needs_revision" class="bg-white border border-red-200 hover:bg-red-50 text-red-600 px-4 py-2 rounded-xl text-sm font-semibold">
                                                                    Needs Revision
                                                                </button>

                                                                <button type="submit" name="status" value="approved" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-xl text-sm font-semibold">
                                                                    Approve Document
                                                                </button>
                                                            </div>
                                                        </form>
                                                    @endif
                                                </div>
                                            @empty
                                                <div class="quality-type-empty border border-dashed border-gray-200 rounded-xl px-4 py-5 text-center text-xs text-gray-400">
                                                    No {{ strtolower($section['label']) }} for this barangay.
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="border border-dashed border-gray-200 rounded-2xl p-10 text-center">
                            <p class="text-gray-400 text-sm">No submitted documents available for document review.</p>
                        </div>
                    @endforelse
                </div>

                <div id="qualityNoResults" class="hidden border border-dashed border-gray-200 rounded-2xl p-10 text-center mt-4">
                    <p class="text-gray-400 text-sm">No documents matched the selected review filters.</p>
                </div>
            </section>
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>
const notifBtn=document.getElementById('notifBtn');
const notifDropdown=document.getElementById('notifDropdown');
const userMenuBtn=document.getElementById('userMenuBtn');
const userDropdown=document.getElementById('userDropdown');
const searchInput=document.getElementById('barangaySearch');
const periodFilter=document.getElementById('periodFilter');
const monthFilterWrap=document.getElementById('monthFilterWrap');
const quarterFilterWrap=document.getElementById('quarterFilterWrap');
const submissionRows=Array.from(document.querySelectorAll('#submissionRows tr[data-barangay]'));
const submissionNoResults=document.getElementById('submissionNoResults');
const submissionPagination=document.getElementById('submissionPagination');
const submissionPaginationInfo=document.getElementById('submissionPaginationInfo');
const submissionPageButtons=document.getElementById('submissionPageButtons');
const submissionPrevPage=document.getElementById('submissionPrevPage');
const submissionNextPage=document.getElementById('submissionNextPage');
const qualityBarangaySearch=document.getElementById('qualityBarangaySearch');
const qualityStatusFilter=document.getElementById('qualityStatusFilter');
const qualityTypeFilter=document.getElementById('qualityTypeFilter');
const qualityGroups=Array.from(document.querySelectorAll('[data-quality-group]'));
const qualityNoResults=document.getElementById('qualityNoResults');
const submissionPerPage=10;
let submissionCurrentPage=1;

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

function syncPeriodControls(){
    monthFilterWrap.classList.toggle('hidden',periodFilter.value!=='monthly');
    quarterFilterWrap.classList.toggle('hidden',periodFilter.value!=='quarterly');
}

periodFilter.addEventListener('change',syncPeriodControls);
syncPeriodControls();

function filteredSubmissionRows(){
    const keyword=searchInput.value.toLowerCase().trim();

    return submissionRows.filter((row)=>{
        return row.dataset.barangay.includes(keyword);
    });
}

function renderSubmissionPagination(){
    const filteredRows=filteredSubmissionRows();
    const totalRows=filteredRows.length;
    const totalPages=Math.max(1,Math.ceil(totalRows/submissionPerPage));

    if(submissionCurrentPage>totalPages){
        submissionCurrentPage=totalPages;
    }

    const startIndex=(submissionCurrentPage-1)*submissionPerPage;
    const endIndex=Math.min(startIndex+submissionPerPage,totalRows);

    submissionRows.forEach((row)=>{
        row.classList.add('hidden');
    });

    filteredRows.slice(startIndex,endIndex).forEach((row)=>{
        row.classList.remove('hidden');
    });

    submissionNoResults.classList.toggle('hidden',totalRows!==0);
    submissionPagination.classList.toggle('hidden',submissionRows.length===0);

    submissionPaginationInfo.textContent=totalRows>0
        ? `Showing ${startIndex+1}-${endIndex} of ${totalRows} barangays`
        : 'No barangays to display';

    submissionPrevPage.disabled=submissionCurrentPage<=1;
    submissionNextPage.disabled=submissionCurrentPage>=totalPages || totalRows===0;
    submissionPageButtons.innerHTML='';

    if(totalRows===0){
        return;
    }

    for(let page=1;page<=totalPages;page++){
        const button=document.createElement('button');
        button.type='button';
        button.textContent=page;
        button.className=page===submissionCurrentPage
            ? 'w-8 h-8 rounded-lg bg-red-500 text-white text-xs font-bold'
            : 'w-8 h-8 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 text-xs font-semibold';

        button.addEventListener('click',function(){
            submissionCurrentPage=page;
            renderSubmissionPagination();
        });

        submissionPageButtons.appendChild(button);
    }
}

submissionPrevPage.addEventListener('click',function(){
    if(submissionCurrentPage>1){
        submissionCurrentPage--;
        renderSubmissionPagination();
    }
});

submissionNextPage.addEventListener('click',function(){
    const totalPages=Math.max(1,Math.ceil(filteredSubmissionRows().length/submissionPerPage));

    if(submissionCurrentPage<totalPages){
        submissionCurrentPage++;
        renderSubmissionPagination();
    }
});

searchInput.addEventListener('input',function(){
    submissionCurrentPage=1;
    renderSubmissionPagination();
});

renderSubmissionPagination();

document.querySelectorAll('[data-quality-toggle]').forEach((button)=>{
    button.addEventListener('click',function(){
        const group=this.closest('[data-quality-group]');
        const body=group.querySelector('.quality-group-body');
        const arrow=group.querySelector('.quality-group-arrow');

        body.classList.toggle('hidden');
        arrow.classList.toggle('rotate-180',!body.classList.contains('hidden'));
    });
});

function applyQualityFilters(){
    const keyword=qualityBarangaySearch.value.toLowerCase().trim();
    const status=qualityStatusFilter.value;
    const type=qualityTypeFilter.value;
    let visibleGroups=0;

    qualityGroups.forEach((group)=>{
        const barangayMatches=group.dataset.barangay.includes(keyword);
        let visibleItems=0;

        group.querySelectorAll('[data-quality-item]').forEach((item)=>{
            const statusMatches=status==='all' || item.dataset.status===status;
            const typeMatches=type==='all' || item.dataset.type===type;
            const visible=barangayMatches && statusMatches && typeMatches;

            item.classList.toggle('hidden',!visible);

            if(visible){
                visibleItems++;
            }
        });

        group.querySelectorAll('[data-quality-type-section]').forEach((section)=>{
            const hasVisible=Array.from(section.querySelectorAll('[data-quality-item]'))
                .some((item)=>!item.classList.contains('hidden'));

            section.classList.toggle('hidden',!hasVisible);
        });

        group.classList.toggle('hidden',visibleItems===0);

        if(visibleItems>0){
            visibleGroups++;
        }
    });

    qualityNoResults.classList.toggle(
        'hidden',
        visibleGroups!==0 || qualityGroups.length===0
    );
}

qualityBarangaySearch.addEventListener('input',applyQualityFilters);
qualityStatusFilter.addEventListener('change',applyQualityFilters);
qualityTypeFilter.addEventListener('change',applyQualityFilters);
applyQualityFilters();

document.addEventListener('DOMContentLoaded',function(){
    const focusedSubmission=document.querySelector('[data-focus-target="1"]');

    if(!focusedSubmission){
        return;
    }

    const group=focusedSubmission.closest('[data-quality-group]');
    const body=group?.querySelector('.quality-group-body');
    const arrow=group?.querySelector('.quality-group-arrow');

    body?.classList.remove('hidden');
    arrow?.classList.add('rotate-180');

    setTimeout(()=>{
        focusedSubmission.scrollIntoView({
            behavior:'smooth',
            block:'center'
        });
    },250);
});
</script>
@endpush