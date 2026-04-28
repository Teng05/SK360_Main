@extends('layouts.app')

@section('title', 'SK 360')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
@endsection

@section('content')
<div class="min-h-screen bg-gray-100 flex items-center justify-center px-6">
    <div class="max-w-xl w-full bg-white rounded-3xl shadow-lg border border-gray-200 p-10 text-center">
        <div class="w-16 h-16 mx-auto mb-6 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center text-3xl">
            🚧
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-3">{{ $title }}</h1>
        <p class="text-gray-600 mb-8">{{ $message }}</p>
        <a href="{{ route('sk_pres.home') }}" class="inline-flex items-center justify-center rounded-xl bg-red-600 px-5 py-3 text-white font-semibold hover:bg-red-700 transition">
            Back to Home
        </a>
    </div>
</div>
@endsection
