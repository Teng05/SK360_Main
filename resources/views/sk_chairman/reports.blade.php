@extends('layouts.app')

@section('title', 'Reports | SK 360')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
@endsection

@section('content')
    @include('shared.submission-slots-page')
@endsection

@push('scripts')
<script>
    function openSlotSubmission(slotId, title) {
        document.getElementById('slotIdField').value = slotId;
        document.getElementById('slotSubmissionTitle').textContent = title;
        document.getElementById('slotSubmissionModal').classList.remove('hidden');
    }

    function closeSlotSubmission() {
        document.getElementById('slotSubmissionModal').classList.add('hidden');
    }

    function toggleSlotFile(show) {
        document.getElementById('slotFileSection').classList.toggle('hidden', !show);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const fileInput = document.querySelector('input[name="report_file"]');
        const fileName = document.getElementById('slotFileName');

        if (fileInput && fileName) {
            fileInput.addEventListener('change', function () {
                fileName.textContent = this.files.length ? this.files[0].name : '';
            });
        }
    });
</script>
@endpush
