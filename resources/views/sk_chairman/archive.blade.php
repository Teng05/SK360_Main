{{-- File guide: Blade view template for resources/views/sk_chairman/archive.blade.php. --}}
@extends('layouts.app')

@section('title','SK 360° | Archive')

@section('page_css')
<script src="https://cdn.tailwindcss.com"></script>
@endsection

@section('content')
<div class="flex h-screen bg-gray-100 overflow-hidden">
    @include('partials.app.sidebar')

    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        @include('partials.app.topbar',[
            'accountButtonId'=>'profileDropdownBtn',
            'accountMenuId'=>'profileMenu',
            'bindBell'=>true
        ])

        <main class="flex-1 overflow-y-auto bg-gray-50 p-8">
            <div class="mb-6">
                <span class="sk-eyebrow">
                    <span class="sk-dot"></span>
                    Archive
                </span>

                <h1 class="text-4xl font-bold text-gray-900">
                    Document Archive
                </h1>

                <p class="text-gray-600 text-lg">
                    Long-term record preservation for Barangay {{ $barangayName }}
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3 mb-8">
                @foreach($archiveCards as $card)
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 text-center shadow-sm">
                        <div class="mb-2 text-3xl text-gray-700">
                            {!! $card['icon'] !!}
                        </div>

                        <h3 class="text-[11px] font-black text-gray-800 uppercase leading-4">
                            {{ $card['label'] }}
                        </h3>

                        <div class="mt-3 inline-flex rounded-full bg-gray-100 px-3 py-1 text-[10px] font-bold text-gray-600">
                            {{ $card['count'] }} records
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">
                            All Documents
                        </h2>

                        <p class="text-sm text-gray-500">
                            Filter archived accomplishment reports, budget documents, and event records.
                        </p>
                    </div>

                    <a href="{{ route('sk_chairman.archive.bulk-download',request()->query()) }}"
                        class="sk-btn sk-btn--secondary">
                        @include('partials.ui.icon',[
                            'icon'=>'download',
                            'iconSize'=>16
                        ])
                        Bulk Download
                    </a>
                </div>

                @if(session('archive_error'))
                    <div class="sk-alert sk-alert--error mb-5">
                        @include('partials.ui.icon',[
                            'icon'=>'circle-alert',
                            'iconSize'=>18
                        ])

                        <span>
                            {{ session('archive_error') }}
                        </span>
                    </div>
                @endif

                <form method="GET"
                    action="{{ route('sk_chairman.archive') }}"
                    class="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">

                    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                        <label class="sr-only" for="archive_term">
                            Filter by administration
                        </label>

                        <select id="archive_term"
                            name="term_id"
                            onchange="this.form.submit()"
                            class="min-w-[220px] rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-red-300">

                            <option value="">
                                All Administrations
                            </option>

                            @foreach($administrationTerms as $term)
                                <option value="{{ $term->term_id }}"
                                    {{ (int)($filters['term_id']??0)===(int)$term->term_id?'selected':'' }}>
                                    Administration {{ $term->start_year }}-{{ $term->end_year }}
                                </option>
                            @endforeach
                        </select>

                        <label class="sr-only" for="archive_year">
                            Filter by year
                        </label>

                        <select id="archive_year"
                            name="year"
                            onchange="this.form.submit()"
                            class="min-w-[180px] rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-red-300">

                            <option value="">
                                All Years
                            </option>

                            @foreach($filterYears as $year)
                                <option value="{{ $year }}"
                                    {{ ($filters['year']??'')===(string)$year?'selected':'' }}>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>

                        <label class="sr-only" for="archive_type">
                            Filter by document type
                        </label>

                        <select id="archive_type"
                            name="type"
                            onchange="this.form.submit()"
                            class="min-w-[220px] rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-red-300">

                            <option value="">
                                All Types
                            </option>

                            @foreach($typeOptions as $value=>$label)
                                <option value="{{ $value }}"
                                    {{ ($filters['type']??'')===$value?'selected':'' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if(
                        ($filters['term_id']??null)
                        || ($filters['year']??'')!==''
                        || ($filters['type']??'')!==''
                    )
                        <a href="{{ route('sk_chairman.archive') }}"
                            class="text-xs font-bold text-red-600 hover:text-red-700">
                            Clear filters
                        </a>
                    @endif
                </form>

                <div class="mb-4 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-400">
                    <span>
                        Showing {{ $documentCount }} documents
                    </span>

                    @if($filters['term_id']??null)
                        @php
                            $selectedAdministration=$administrationTerms->firstWhere(
                                'term_id',
                                $filters['term_id']
                            );
                        @endphp

                        @if($selectedAdministration)
                            <span class="rounded-full bg-red-50 px-3 py-1 font-bold text-red-600">
                                Administration {{ $selectedAdministration->start_year }}-{{ $selectedAdministration->end_year }}
                            </span>
                        @endif
                    @else
                        <span>
                            All completed administrations
                        </span>
                    @endif
                </div>

                <div class="space-y-3">
                    @forelse($documents as $document)
                        <div data-archive-document
                            class="flex items-center justify-between gap-4 rounded-2xl border border-gray-100 bg-white px-4 py-4 hover:bg-gray-50 transition">

                            <div class="flex min-w-0 items-start gap-3">
                                <div class="pt-1 text-red-400">
                                    {!! $document->icon !!}
                                </div>

                                <div class="min-w-0">
                                    <h3 class="text-sm font-bold text-gray-800 break-words">
                                        {{ $document->title }}
                                    </h3>

                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-[10px]">
                                        <span class="rounded-full bg-red-50 px-2 py-1 font-bold text-red-600">
                                            {{ $document->administration_label }}
                                        </span>

                                        <span class="rounded-full bg-blue-50 px-2 py-1 font-bold text-blue-600">
                                            {{ $document->category }}
                                        </span>

                                        <span class="rounded-full bg-gray-100 px-2 py-1 font-bold text-gray-600">
                                            {{ $document->badge }}
                                        </span>

                                        <span class="rounded-full bg-gray-100 px-2 py-1 font-bold text-gray-600">
                                            {{ $document->owner?:$barangayName }}
                                        </span>

                                        <span class="text-gray-400">
                                            {{ $document->size }}
                                        </span>

                                        <span class="text-gray-400">
                                            {{ $document->formatted_date }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            @if($document->downloadable)
                                <a href="{{ route('sk_chairman.archive.download',[
                                        $document->source_type,
                                        $document->source_id
                                    ]) }}"
                                    class="shrink-0 text-gray-500 hover:text-red-500 transition"
                                    title="Download record">

                                    @include('partials.ui.icon',[
                                        'icon'=>'download',
                                        'iconSize'=>15
                                    ])
                                </a>
                            @else
                                <span class="shrink-0 cursor-not-allowed text-gray-300"
                                    title="The archived record exists, but its source file is missing from storage.">

                                    @include('partials.ui.icon',[
                                        'icon'=>'download',
                                        'iconSize'=>15
                                    ])
                                </span>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 px-6 py-10 text-center">
                            <p class="text-sm font-semibold text-gray-600">
                                No archived documents found.
                            </p>

                            <p class="mt-1 text-xs text-gray-400">
                                Try another administration, year, or document type.
                            </p>
                        </div>
                    @endforelse
                </div>

                @if($documentCount>0)
                    <div id="archivePagination"
                        class="mt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-t border-gray-100 pt-5">

                        <p id="archivePaginationInfo"
                            class="text-xs text-gray-500">
                        </p>

                        <div class="flex items-center gap-2">
                            <button id="archivePrevPage"
                                type="button"
                                class="sk-btn sk-btn--secondary !px-3 !py-2 disabled:opacity-40 disabled:cursor-not-allowed">
                                Previous
                            </button>

                            <div id="archivePageButtons"
                                class="flex items-center gap-1">
                            </div>

                            <button id="archiveNextPage"
                                type="button"
                                class="sk-btn sk-btn--secondary !px-3 !py-2 disabled:opacity-40 disabled:cursor-not-allowed">
                                Next
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            <div class="mt-6 rounded-2xl border border-purple-100 bg-purple-50 p-5 text-sm text-purple-700">
                <h3 class="mb-2 font-black">
                    Archive Information
                </h3>

                <p>
                    Completed-administration accomplishment reports and budget documents for Barangay {{ $barangayName }} are preserved here. Federation Calendar Program and Other event records are also available as generated Event Record PDFs.
                </p>
            </div>
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>
const archiveDropdownBtn=document.getElementById('profileDropdownBtn');
const archiveProfileMenu=document.getElementById('profileMenu');

if(archiveDropdownBtn&&archiveProfileMenu){
    archiveDropdownBtn.addEventListener('click',(e)=>{
        e.stopPropagation();
        archiveProfileMenu.classList.toggle('hidden');
    });

    window.addEventListener('click',(e)=>{
        if(
            !archiveProfileMenu.contains(e.target)
            && !archiveDropdownBtn.contains(e.target)
        ){
            archiveProfileMenu.classList.add('hidden');
        }
    });
}

const archiveRows=[...document.querySelectorAll('[data-archive-document]')];
const archivePagination=document.getElementById('archivePagination');
const archivePaginationInfo=document.getElementById('archivePaginationInfo');
const archivePrevPage=document.getElementById('archivePrevPage');
const archiveNextPage=document.getElementById('archiveNextPage');
const archivePageButtons=document.getElementById('archivePageButtons');
const archivePageSize=10;

let archivePage=1;

function renderArchivePage(){
    if(!archivePagination||!archiveRows.length){
        return;
    }

    const pageCount=Math.ceil(
        archiveRows.length/archivePageSize
    );

    archivePage=Math.min(
        Math.max(archivePage,1),
        pageCount
    );

    const start=
        (archivePage-1)*archivePageSize;

    const end=Math.min(
        start+archivePageSize,
        archiveRows.length
    );

    archiveRows.forEach((row,index)=>{
        row.classList.toggle(
            'hidden',
            index<start||index>=end
        );
    });

    archivePaginationInfo.textContent=
        `Showing ${start+1}-${end} of ${archiveRows.length} records`;

    archivePrevPage.disabled=
        archivePage===1;

    archiveNextPage.disabled=
        archivePage===pageCount;

    archivePageButtons.innerHTML='';

    for(let page=1;page<=pageCount;page++){
        const button=
            document.createElement('button');

        button.type='button';
        button.textContent=page;

        button.className=
            page===archivePage
                ? 'min-w-9 h-9 rounded-lg bg-red-600 px-3 text-xs font-black text-white shadow-sm'
                : 'min-w-9 h-9 rounded-lg border border-gray-200 bg-white px-3 text-xs font-bold text-gray-600 hover:border-red-200 hover:text-red-600';

        button.addEventListener(
            'click',
            ()=>{
                archivePage=page;
                renderArchivePage();
            }
        );

        archivePageButtons.appendChild(
            button
        );
    }
}

archivePrevPage?.addEventListener(
    'click',
    ()=>{
        archivePage--;
        renderArchivePage();
    }
);

archiveNextPage?.addEventListener(
    'click',
    ()=>{
        archivePage++;
        renderArchivePage();
    }
);

renderArchivePage();
</script>
@endpush