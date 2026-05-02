{{-- File guide: Blade view template for resources/views/sk_pres/rankings.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK 360 Rankings')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
@endsection

@section('content')
    @include('shared.rankings-page')
@endsection
