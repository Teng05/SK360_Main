{{-- File guide: Blade view template for resources/views/sk_chairman/budget.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK 360° | Budget')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
@endsection

@section('content')
    @include('shared.submission-slots-page', ['chairmanReportUi' => true])
@endsection

@include('shared.budget-submission-scripts')
