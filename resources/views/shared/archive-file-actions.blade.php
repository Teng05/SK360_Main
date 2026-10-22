<div class="shrink-0 flex flex-col items-end gap-2">
    @if(isset($document->proof_links))
        @forelse($document->proof_links as $proof)
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500 max-w-[160px] truncate" title="{{ $proof->label }}">{{ $proof->label }}</span>
                <a class="sk-icon-btn" href="{{ $proof->view_url }}" target="_blank" rel="noopener noreferrer" title="View proof" aria-label="View proof: {{ $proof->label }}">@include('partials.ui.icon',['icon'=>'eye','iconSize'=>18])</a>
                <a class="sk-icon-btn" href="{{ $proof->download_url }}" title="Download proof" aria-label="Download proof: {{ $proof->label }}">@include('partials.ui.icon',['icon'=>'download','iconSize'=>18])</a>
            </div>
        @empty
            <div class="flex items-center gap-2">
                <span class="sk-icon-btn text-gray-300 cursor-not-allowed" title="No proof file available" aria-label="Proof preview unavailable" aria-disabled="true">@include('partials.ui.icon',['icon'=>'eye','iconSize'=>18])</span>
                <span class="sk-icon-btn text-gray-300 cursor-not-allowed" title="No proof file available" aria-label="Proof download unavailable" aria-disabled="true">@include('partials.ui.icon',['icon'=>'download','iconSize'=>18])</span>
            </div>
        @endforelse
        <a class="text-xs font-semibold text-red-600" href="{{ $document->view_url }}">Record details</a>
    @else
        <div class="flex items-center gap-2">
            @if($document->viewable ?? false)
                <a class="sk-icon-btn" href="{{ route($archivePrefix.'.archive.view',[$document->source_type,$document->source_id]) }}" target="_blank" rel="noopener noreferrer" title="View file" aria-label="View file: {{ $document->title }}">@include('partials.ui.icon',['icon'=>'eye','iconSize'=>18])</a>
            @else
                <span class="sk-icon-btn text-gray-300 cursor-not-allowed" title="{{ $document->downloadable ? 'This format requires downloading to open.' : 'Source file is missing from storage.' }}" aria-label="File preview unavailable" aria-disabled="true">@include('partials.ui.icon',['icon'=>'eye','iconSize'=>18])</span>
            @endif
            @if($document->downloadable)
                <a class="sk-icon-btn" href="{{ route($archivePrefix.'.archive.download',[$document->source_type,$document->source_id]) }}" title="Download file" aria-label="Download file: {{ $document->title }}">@include('partials.ui.icon',['icon'=>'download','iconSize'=>18])</a>
            @else
                <span class="sk-icon-btn text-gray-300 cursor-not-allowed" title="Source file is missing from storage." aria-label="File download unavailable" aria-disabled="true">@include('partials.ui.icon',['icon'=>'download','iconSize'=>18])</span>
            @endif
        </div>
    @endif
</div>
