{{-- File guide: Blade view template for resources/views/sk_secretary/reports.blade.php. --}}
@extends('layouts.app')
@section('title','Reports | SK 360')
@section('page_css')
<script src="https://cdn.tailwindcss.com"></script>
@endsection
@section('content')
@include('shared.submission-slots-page', ['chairmanReportUi' => true])
@endsection
@push('scripts')
<script>
function openSlotSubmission(slotId,title){
    document.getElementById('slotIdField').value=slotId;
    document.getElementById('slotSubmissionTitle').textContent=title;
    if(typeof window.clearSlotFormWarning==='function'){
        window.clearSlotFormWarning();
    }
    document.getElementById('slotSubmissionModal').classList.remove('hidden');
}
function closeSlotSubmission(){
    document.getElementById('slotSubmissionModal').classList.add('hidden');
}
function toggleSlotFile(show){
    document.getElementById('slotFileSection')?.classList.toggle('hidden',!show);
}
document.addEventListener('DOMContentLoaded',function(){
    const form=document.getElementById('slotSubmissionForm');
    const fileInput=document.querySelector('input[name="report_file"]');
    const fileName=document.getElementById('slotFileName');
    if(fileInput && fileName){
        fileInput.addEventListener('change',function(){
            fileName.textContent=this.files.length ? this.files[0].name : '';
        });
    }
    form?.addEventListener('submit',function(event){
        const messages=[];
        const file=fileInput?.files?.[0] ?? null;
        if(!file){
            messages.push('Report file is required. Please select a PDF file before submitting.');
        }else{
            const hasPdfExtension=file.name.toLowerCase().endsWith('.pdf');
            const hasPdfMime=file.type==='' || file.type==='application/pdf';
            if(!hasPdfExtension || !hasPdfMime){
                messages.push('The report file must be a PDF.');
            }
        }
        if(messages.length){
            event.preventDefault();
            if(typeof showSlotFormWarning==='function'){
                showSlotFormWarning(messages);
            }else{
                alert(messages.join('\n'));
            }
        }
    });
    @if(($errors->any() || session('report_error')) && old('slot_id'))
        @php
            $failedReportSlot=collect($slots)->first(
                fn($slot)=>(int)$slot->slot_id===(int)old('slot_id')
            );
        @endphp
        @if($failedReportSlot)
            openSlotSubmission(
                {{ (int)$failedReportSlot->slot_id }},
                @js($failedReportSlot->title)
            );
            @if($errors->any())
                if(typeof showSlotFormWarning==='function'){
                    showSlotFormWarning(@json($errors->all()));
                }
            @elseif(session('report_error'))
                if(typeof showSlotFormWarning==='function'){
                    showSlotFormWarning([@json(session('report_error'))]);
                }
            @endif
        @endif
    @endif
});
</script>
@endpush