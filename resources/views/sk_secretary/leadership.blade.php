{{-- File guide: Blade view template for resources/views/sk_secretary/leadership.blade.php. --}}
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

        <div class="p-8 overflow-y-auto">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800 uppercase">SK Council Leadership</h1>
                <p class="text-gray-500">Barangay {{ $barangayName }} leadership and council directory</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="text-3xl">👥</div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Executive Officers</h2>
                            <p class="text-sm text-gray-500">SK Chairman, SK Secretary, and key elected officers</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        @forelse ($executives as $member)
                            <div class="bg-gray-50 rounded-3xl p-4 border border-gray-100 hover:shadow-sm transition">
                                <div class="flex items-start gap-4">
                                    <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-black text-xl">
                                        {{ strtoupper(substr($member['name'] ?? 'NA', 0, 2)) }}
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                            <div>
                                                <h3 class="text-lg font-bold text-gray-900 uppercase">{{ $member['name'] }}</h3>
                                                <p class="text-xs text-gray-400 uppercase tracking-wider mt-1">{{ $member['position'] }}</p>
                                            </div>
                                            <span class="inline-flex items-center rounded-full bg-red-100 text-red-600 text-[10px] font-black uppercase px-3 py-1">
                                                {{ $member['term'] ?? 'Term not set' }}
                                            </span>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-3 text-xs text-gray-500">
                                            <div>&#128231; {{ $member['email'] ?: 'No email' }}</div>
                                            <div>&#128222; {{ $member['phone'] ?: 'No phone' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-400 italic">No executive officers found.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="text-3xl">📜</div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Council Members</h2>
                            <p class="text-sm text-gray-500">Full barangay council roster for the SK Secretariat</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        @forelse ($kagawads as $member)
                            <div class="bg-gray-50 rounded-3xl p-4 border border-gray-100 hover:shadow-sm transition">
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 rounded-full bg-yellow-400 text-white font-black flex items-center justify-center text-lg">
                                        {{ strtoupper(substr($member['name'] ?? 'NA', 0, 2)) }}
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-sm font-bold text-gray-900 uppercase">{{ $member['name'] }}</h3>
                                        <p class="text-[11px] text-gray-500 uppercase tracking-widest mt-1">{{ $member['position'] }}</p>
                                        <div class="mt-3 text-xs text-gray-500 grid grid-cols-1 gap-2">
                                            <div>&#128231; {{ $member['email'] ?: 'No email' }}</div>
                                            <div>&#128222; {{ $member['phone'] ?: 'No phone' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-400 italic">No council members found.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('sk_secretary.partials.dropdown-scripts')
@endpush
