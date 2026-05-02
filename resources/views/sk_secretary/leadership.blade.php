@extends('layouts.app')

@section('title', 'SK Secretary Leadership')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
@endsection

@section('content')
<div class="flex h-screen bg-gray-100 overflow-hidden">
    @include('sk_secretary.partials.sidebar')

    <div class="flex-1 flex flex-col overflow-hidden">
        @include('sk_secretary.partials.topbar')

        <main class="p-8 overflow-y-auto h-full bg-gray-50">
            <div class="flex justify-between items-end mb-8">
                <div>
                    <h1 class="text-3xl font-black text-gray-800 uppercase tracking-tight">Council Leadership</h1>
                    <p class="text-gray-500 font-medium italic">Official Directory for Barangay {{ $barangayName }}</p>
                </div>
            </div>

            <div class="bg-red-600 rounded-2xl p-6 text-white mb-8 shadow-md flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <div class="bg-white/20 p-3 rounded-xl text-2xl">&#128205;</div>
                    <div>
                        <h2 class="text-xl font-black uppercase tracking-tight">Barangay {{ $barangayName }}</h2>
                        <p class="text-xs opacity-80 font-medium">Current SK Administration</p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="bg-white/10 px-4 py-2 rounded-full text-[10px] font-bold border border-white/20 uppercase tracking-widest">
                        {{ $councilMembers->count() }} Total Members
                    </span>
                </div>
            </div>

            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-8 mb-8">
                <div class="flex items-center gap-2 mb-8 border-b border-gray-50 pb-4">
                    <span class="text-red-500 font-bold">&#128737;</span>
                    <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Executive Officers</h3>
                </div>
                <div class="grid grid-cols-1 gap-4">
                    @forelse ($executives as $member)
                        <div class="flex items-center gap-6 p-4 rounded-2xl border border-transparent hover:border-gray-100 hover:bg-gray-50 transition group">
                            <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-black text-xl border-4 border-white shadow-sm">
                                {{ strtoupper(substr($member['name'] ?? 'U', 0, 2)) }}
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-3">
                                    <h4 class="text-lg font-black text-gray-800 uppercase leading-none">{{ $member['name'] }}</h4>
                                    <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase bg-red-600 text-white">
                                        {{ $member['position'] }}
                                    </span>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 mt-3 text-[11px] text-gray-500 font-medium gap-2">
                                    <div class="flex items-center gap-2"><span>&#128231;</span> {{ $member['email'] ?: 'No email provided' }}</div>
                                    <div class="flex items-center gap-2"><span>&#128222;</span> {{ $member['phone'] ?: 'No phone provided' }}</div>
                                    <div class="flex items-center gap-2 uppercase tracking-tighter"><span>&#128197;</span> {{ $member['term'] ?: 'N/A' }}</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-400 italic">No executive officers found.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-8">
                <div class="flex items-center gap-2 mb-8 border-b border-gray-50 pb-4">
                    <span class="text-yellow-500 font-bold">&#127775;</span>
                    <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">SK Councilors</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @forelse ($kagawads as $member)
                        <div class="bg-gray-50/50 p-5 rounded-2xl border border-gray-100 flex items-center gap-4 hover:shadow-md hover:bg-white transition group relative">
                            <div class="w-12 h-12 rounded-full bg-yellow-400 flex items-center justify-center text-white font-black shadow-sm border-2 border-white">
                                {{ strtoupper(substr($member['name'] ?? 'U', 0, 2)) }}
                            </div>
                            <div class="flex-1">
                                <h4 class="text-xs font-black text-gray-800 uppercase leading-none">{{ $member['name'] }}</h4>
                                <p class="text-[9px] text-yellow-600 font-black uppercase mt-1">{{ $member['position'] }}</p>
                                <p class="text-[10px] text-gray-400 mt-2 font-medium">&#128222; {{ $member['phone'] ?: 'No phone provided' }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-400 italic">No councilors found.</p>
                    @endforelse
                </div>
            </div>
        </main>
    </div>
</div>
@endsection

@push('scripts')
@include('sk_secretary.partials.dropdown-scripts')
@endpush
