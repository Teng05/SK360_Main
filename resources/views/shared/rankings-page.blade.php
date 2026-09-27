{{-- File guide: Blade view template for resources/views/shared/rankings-page.blade.php. --}}
<div class="flex h-screen bg-gray-100">
    @include('partials.app.sidebar')
    <div class="flex-1 flex flex-col min-w-0">
        @include('partials.app.topbar')
        <div class="p-8 overflow-y-auto h-full bg-gray-50">
            <div class="sk-page-head">
                <div class="sk-page-head__text">
                    <span class="sk-eyebrow"><span class="sk-dot"></span>Barangay Rankings</span>
                    <h1 class="sk-page-title">Point System Rankings</h1>
                    <p class="sk-page-subtitle">Rankings based on recorded submissions, documentation, and participation{{ $latestPeriod?' for '.$latestPeriod:'' }}</p>
                </div>
                <form method="GET" action="{{ url()->current() }}" class="w-full md:w-64">
                    <label for="rankingPeriod" class="sk-overline block mb-2">Month / Year</label>
                    <div class="relative">
                        <select id="rankingPeriod" name="period" onchange="this.form.submit()" class="w-full appearance-none bg-white border border-gray-200 rounded-xl px-4 py-3 pr-10 text-xs font-bold text-gray-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-red-100 focus:border-red-300 cursor-pointer">
                            @foreach($rankingPeriods??[] as $period)
                                <option value="{{ $period['value'] }}" @selected(($selectedPeriod??'')===$period['value'])>{{ $period['label'] }}</option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-gray-400">@include('partials.ui.icon',['icon'=>'chevron-down','iconSize'=>15])</span>
                    </div>
                </form>
            </div>

            @php
                $podium=collect($topRankings??[]);
            @endphp

            @if($podium->isNotEmpty())
                <section class="mb-8 rounded-3xl border border-red-100 bg-gradient-to-b from-red-50/70 to-white px-6 py-8 shadow-sm">
                    <div class="mb-7 text-center">
                        <span class="sk-eyebrow"><span class="sk-dot"></span>Honor Roll</span>
                        <h2 class="mt-3 text-2xl font-black text-gray-900">Top Performing Councils</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ $latestPeriod??'Selected ranking period' }}</p>
                    </div>
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                        @foreach($podium as $top)
                            @php
                                $rank=(int)($top['rank']??($loop->index+1));
                                $rankIcon=match($rank){1=>'trophy',2=>'award',3=>'shield-check',default=>'trophy'};
                                $rankLabel=match($rank){1=>'1st Place',2=>'2nd Place',3=>'3rd Place',default=>'Rank #'.$rank};
                                $tone=match($rank){1=>'yellow',2=>'blue',3=>'orange',default=>'gray'};
                                $border=match($rank){1=>'border-yellow-300',2=>'border-blue-200',3=>'border-orange-200',default=>'border-gray-200'};
                            @endphp
                            <article class="rounded-[24px] border {{ $border }} bg-white p-6 shadow-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="sk-icon-tile sk-icon-tile--lg sk-icon-tile--{{ $tone }}">@include('partials.ui.icon',['icon'=>$rankIcon,'iconSize'=>24])</span>
                                    <span class="rounded-full bg-gray-100 px-3 py-1 text-[10px] font-black uppercase tracking-wide text-gray-600">{{ $rankLabel }}</span>
                                </div>
                                <h3 class="mt-6 text-lg font-black text-gray-900">{{ $top['name'] }}</h3>
                                <div class="mt-4 rounded-2xl bg-gray-50 px-4 py-4">
                                    <p class="text-[10px] font-black uppercase tracking-[0.15em] text-gray-400">Total Points</p>
                                    <p class="mt-1 text-3xl font-black text-gray-900">{{ (int)$top['points'] }} <span class="text-xs font-bold uppercase text-gray-400">pts</span></p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="mb-10 overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm">
                <div class="flex flex-col gap-4 border-b border-gray-100 px-6 py-6 md:flex-row md:items-end md:justify-between">
                    <div>
                        <h2 class="text-xl font-black text-gray-900">Complete Leaderboard</h2>
                        <p class="mt-1 text-sm text-gray-500">All barangays for {{ $latestPeriod??'the selected ranking period' }} using actual submission, document, and meeting scores.</p>
                    </div>
                    <div class="w-full md:w-80">
                        <label for="leaderboardSearch" class="sk-overline mb-2 block">Search Barangay</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">@include('partials.ui.icon',['icon'=>'search','iconSize'=>17])</span>
                            <input type="text" id="leaderboardSearch" placeholder="Search barangay..." autocomplete="off" class="w-full rounded-xl border border-gray-200 bg-gray-50 py-3 pl-10 pr-10 text-sm text-gray-700 outline-none transition focus:border-red-300 focus:bg-white focus:ring-4 focus:ring-red-50">
                            <button id="clearLeaderboardSearch" type="button" class="absolute right-3 top-1/2 hidden -translate-y-1/2 text-gray-400 hover:text-red-500" title="Clear search">@include('partials.ui.icon',['icon'=>'x','iconSize'=>16])</button>
                        </div>
                    </div>
                </div>

                <div class="hidden grid-cols-[minmax(220px,1.2fr)_repeat(3,minmax(150px,1fr))_110px] gap-4 bg-slate-50 px-6 py-3 text-[10px] font-black uppercase tracking-[0.12em] text-slate-500 xl:grid">
                    <span>Barangay</span>
                    <span>Submission Score</span>
                    <span>Document Score</span>
                    <span>Meeting Score</span>
                    <span class="text-right">Rank</span>
                </div>

                <div id="leaderboardList" class="divide-y divide-gray-100">
                    @forelse($leaderboard as $row)
                        @php
                            $metricData=[
                                ['label'=>'Submission Score','value'=>(int)$row->submission_score],
                                ['label'=>'Document Score','value'=>(int)$row->document_score],
                                ['label'=>'Meeting Score','value'=>(int)$row->meeting_score],
                            ];
                        @endphp
                        <div class="leaderboard-row grid gap-4 px-6 py-5 transition hover:bg-slate-50 xl:grid-cols-[minmax(220px,1.2fr)_repeat(3,minmax(150px,1fr))_110px] xl:items-center" data-barangay="{{ $row->name }}">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $row->rank!==null&&(int)$row->rank<=3?'bg-amber-50 text-amber-700':'bg-slate-100 text-slate-500' }} text-sm font-black">{{ $row->rank!==null?$row->rank:'—' }}</span>
                                <span class="sk-icon-tile sk-icon-tile--sm sk-icon-tile--gray">@include('partials.ui.icon',['icon'=>'building-2','iconSize'=>17])</span>
                                <div class="min-w-0">
                                    <h3 class="truncate text-sm font-black text-gray-900">{{ $row->name }}</h3>
                                    <p class="mt-1 text-xs text-gray-500">{{ (int)$row->points }} total points</p>
                                </div>
                            </div>

                            @foreach($metricData as $metric)
                                @php
                                    $value=$metric['value'];
                                    $valueClass=$value<0?'text-red-600':($value>0?'text-green-700':'text-gray-500');
                                    $prefix=$value>0?'+':'';
                                @endphp
                                <div class="rounded-2xl border border-gray-100 bg-white px-4 py-3 xl:border-0 xl:bg-transparent xl:px-0 xl:py-0">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-[10px] font-bold text-gray-500 xl:hidden">{{ $metric['label'] }}</span>
                                        <span class="text-sm font-black {{ $valueClass }}">{{ $prefix }}{{ $value }} pts</span>
                                    </div>
                                </div>
                            @endforeach

                            <div class="flex items-center justify-start gap-2 xl:justify-end">
                                @if($row->rank!==null)
                                    <span class="rounded-full border border-red-100 bg-red-50 px-3 py-1 text-[10px] font-black text-red-600">#{{ $row->rank }}</span>
                                    @if($row->trend==='up')
                                        <span class="font-black text-green-600">↑</span>
                                    @elseif($row->trend==='down')
                                        <span class="font-black text-red-600">↓</span>
                                    @elseif($row->trend==='same')
                                        <span class="font-black text-gray-400">—</span>
                                    @else
                                        <span class="rounded-full bg-blue-50 px-2 py-1 text-[9px] font-black uppercase text-blue-600">New</span>
                                    @endif
                                @else
                                    <span class="rounded-full bg-gray-100 px-3 py-1 text-[10px] font-black text-gray-400">—</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-12 text-center text-sm text-gray-400">No barangays found.</div>
                    @endforelse
                </div>

                <div id="leaderboardEmpty" class="hidden px-6 py-12 text-center">
                    <div class="mb-3 flex justify-center"><span class="sk-icon-tile sk-icon-tile--lg sk-icon-tile--gray">@include('partials.ui.icon',['icon'=>'search','iconSize'=>23])</span></div>
                    <p class="text-sm font-bold text-gray-600">No barangay found</p>
                    <p class="mt-1 text-xs text-gray-400">Try another barangay name.</p>
                </div>

                @if($leaderboard->isNotEmpty())
                    <div id="leaderboardPaginationWrapper" class="border-t border-gray-100 px-6 py-5">
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <p id="leaderboardInfo" class="text-xs text-gray-400"></p>
                            <div class="flex flex-wrap items-center gap-2">
                                <button id="leaderboardPrevious" type="button" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-[10px] font-black uppercase text-gray-600 transition hover:border-red-300 hover:text-red-500 disabled:cursor-not-allowed disabled:opacity-40">Previous</button>
                                <div id="leaderboardPages" class="flex flex-wrap items-center gap-1"></div>
                                <button id="leaderboardNext" type="button" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-[10px] font-black uppercase text-gray-600 transition hover:border-red-300 hover:text-red-500 disabled:cursor-not-allowed disabled:opacity-40">Next</button>
                            </div>
                        </div>
                        <p class="mt-4 text-[10px] text-gray-400">Barangays with no recorded points remain visible but are not assigned a numerical rank.</p>
                    </div>
                @endif
            </section>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pb-10">
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                    <h3 class="text-sm font-black text-gray-800 mb-1">Points System</h3>
                    <p class="text-xs text-gray-400 mb-6">How points are currently earned and deducted</p>
                    <div class="space-y-4">
                        @foreach($pointSystem??[] as $rule)
                            @php
                                $isPositive=($rule['type']??'positive')==='positive';
                                $badgeClass=$isPositive?'bg-green-100 text-green-600':'bg-red-100 text-red-600';
                                $prefix=$rule['points']>0?'+':'';
                            @endphp
                            <div class="flex justify-between items-center text-[11px]">
                                <span class="text-gray-700 font-medium">{{ $rule['label'] }}</span>
                                <span class="{{ $badgeClass }} px-2 py-1 rounded-lg font-black">{{ $prefix }}{{ $rule['points'] }} points</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                    <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-6">Ranking Indicators</h3>
                    <div class="space-y-4">
                        <div class="flex items-center gap-4">
                            <span class="sk-icon-tile sk-icon-tile--sm sk-icon-tile--yellow">@include('partials.ui.icon',['icon'=>'trophy','iconSize'=>17])</span>
                            <div><h4 class="text-xs font-black text-gray-800 uppercase leading-none">Selected Period Rank</h4><p class="text-[10px] text-gray-400 mt-1">Only barangays with recorded points receive a numerical rank.</p></div>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="sk-icon-tile sk-icon-tile--sm sk-icon-tile--blue">@include('partials.ui.icon',['icon'=>'trending-up','iconSize'=>17])</span>
                            <div><h4 class="text-xs font-black text-gray-800 uppercase leading-none">Rank Movement</h4><p class="text-[10px] text-gray-400 mt-1">Compares rank with the immediately previous month.</p></div>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="sk-icon-tile sk-icon-tile--sm sk-icon-tile--green">@include('partials.ui.icon',['icon'=>'chart-column','iconSize'=>17])</span>
                            <div><h4 class="text-xs font-black text-gray-800 uppercase leading-none">Recorded Scores</h4><p class="text-[10px] text-gray-400 mt-1">Submission, document, and meeting scores use actual recorded ranking points.</p></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',function(){
    const search=document.getElementById('leaderboardSearch'),clear=document.getElementById('clearLeaderboardSearch'),rows=[...document.querySelectorAll('.leaderboard-row')],empty=document.getElementById('leaderboardEmpty'),pagination=document.getElementById('leaderboardPaginationWrapper'),info=document.getElementById('leaderboardInfo'),previous=document.getElementById('leaderboardPrevious'),next=document.getElementById('leaderboardNext'),pages=document.getElementById('leaderboardPages');
    const perPage=10;
    let currentPage=1,filteredRows=rows;

    function filterRows(){
        const term=search?search.value.trim().toLowerCase():'';
        filteredRows=rows.filter(row=>(row.dataset.barangay||'').toLowerCase().includes(term));
        if(clear)clear.classList.toggle('hidden',term.length===0);
    }

    function pageButton(page){
        const button=document.createElement('button'),active=page===currentPage;
        button.type='button';
        button.textContent=page;
        button.className=active?'min-w-[34px] rounded-lg bg-red-600 px-3 py-2 text-[10px] font-black text-white':'min-w-[34px] rounded-lg border border-gray-200 bg-white px-3 py-2 text-[10px] font-black text-gray-600 transition hover:border-red-300 hover:text-red-500';
        button.addEventListener('click',()=>{currentPage=page;render();});
        return button;
    }

    function renderPages(totalPages){
        if(!pages)return;
        pages.innerHTML='';
        if(totalPages<=1)return;
        const maxVisible=5;
        let start=Math.max(1,currentPage-Math.floor(maxVisible/2)),end=Math.min(totalPages,start+maxVisible-1);
        if(end-start+1<maxVisible)start=Math.max(1,end-maxVisible+1);
        for(let page=start;page<=end;page++)pages.appendChild(pageButton(page));
    }

    function render(){
        filterRows();
        const total=filteredRows.length,totalPages=Math.max(1,Math.ceil(total/perPage));
        if(currentPage>totalPages)currentPage=totalPages;

        rows.forEach(row=>row.classList.add('hidden'));

        if(total===0){
            if(empty)empty.classList.remove('hidden');
            if(pagination)pagination.classList.add('hidden');
            return;
        }

        if(empty)empty.classList.add('hidden');
        if(pagination)pagination.classList.remove('hidden');

        const start=(currentPage-1)*perPage,end=Math.min(start+perPage,total);
        filteredRows.slice(start,end).forEach(row=>row.classList.remove('hidden'));

        if(info)info.textContent=`Showing ${start+1}-${end} of ${total} barangays`;
        if(previous)previous.disabled=currentPage<=1;
        if(next)next.disabled=currentPage>=totalPages;
        renderPages(totalPages);
    }

    if(search)search.addEventListener('input',()=>{currentPage=1;render();});
    if(clear)clear.addEventListener('click',()=>{if(!search)return;search.value='';currentPage=1;search.focus();render();});
    if(previous)previous.addEventListener('click',()=>{if(currentPage>1){currentPage--;render();}});
    if(next)next.addEventListener('click',()=>{const totalPages=Math.max(1,Math.ceil(filteredRows.length/perPage));if(currentPage<totalPages){currentPage++;render();}});
    render();
});
</script>
@endpush