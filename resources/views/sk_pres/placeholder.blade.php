{{-- File guide: Blade view template for resources/views/sk_pres/placeholder.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK 360')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
@endsection

@section('content')
<div class="min-h-screen bg-gray-100 flex items-center justify-center px-6">
    <div class="max-w-xl w-full sk-card p-10 text-center">
        <div class="w-16 h-16 mx-auto mb-6 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center">
            @include('partials.ui.icon', ['icon' => 'settings', 'iconSize' => 28])
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-3">{{ $title }}</h1>
        <p class="text-gray-600 mb-8">{{ $message }}</p>
        <a href="{{ route('sk_pres.home') }}" class="sk-btn sk-btn--primary sk-btn--lg">
            Back to Home
        </a>
    </div>
</div>
@endsection
