<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leadership | SK360 Public Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 text-gray-800">

{{-- HEADER --}}
<header class="sticky top-0 z-40 bg-red-600 text-white shadow">
    <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">

        <a href="{{ route('public.home') }}"
            class="flex items-center gap-3">

            <img src="{{ asset('images/logo.png') }}"
                class="w-10 h-10 rounded-full object-cover"
                alt="SK360 Logo">

            <div>
                <h1 class="text-xl font-black">
                    SK 360°
                </h1>

                <p class="text-[10px] opacity-80 uppercase tracking-widest">
                    Public Information Portal
                </p>
            </div>

        </a>

        <a href="{{ route('public.home') }}"
            class="text-xs font-bold hover:text-yellow-300 transition">
            ← Public Portal
        </a>

    </div>
</header>

<main>

{{-- PAGE HEADER --}}
<section class="bg-gradient-to-br from-red-600 to-red-700 text-white">

    <div class="max-w-4xl mx-auto px-6 py-12">

        <p class="text-xs font-black uppercase tracking-[0.2em] text-red-100">
            Public Directory
        </p>

        <h2 class="text-4xl font-black mt-2">
            Barangay SK Leadership
        </h2>

        <p class="text-red-100 text-sm mt-3 max-w-2xl leading-relaxed">
            View the current Sangguniang Kabataan officials serving each
            barangay in the City of Lipa.
        </p>

        @if($currentTerm)
            <div class="inline-flex items-center gap-2 mt-5 bg-white/10 border border-white/20 rounded-full px-4 py-2">

                <span class="w-2 h-2 rounded-full bg-green-300"></span>

                <span class="text-xs font-bold">
                    Current Administration:
                    {{ $currentTerm->start_year }}–{{ $currentTerm->end_year }}
                </span>

            </div>
        @endif

    </div>

</section>

{{-- BARANGAY SELECTOR --}}
<section class="max-w-4xl mx-auto px-6 pt-8">

    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5">

        <div class="mb-4">

            <p class="text-xs font-black uppercase tracking-widest text-red-600">
                Select Barangay
            </p>

            <h3 class="text-xl font-black text-gray-800 mt-1">
                Find Your SK Officials
            </h3>

            <p class="text-xs text-gray-400 mt-2">
                Choose a barangay to view its current SK leadership.
            </p>

        </div>

        <form id="barangayForm"
            method="GET"
            action="{{ route('public.leadership') }}">

            <label for="barangaySelect"
                class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">
                Barangay
            </label>

            <div class="flex flex-col sm:flex-row gap-3">

                <select id="barangaySelect"
                    name="barangay"
                    class="flex-1 rounded-xl border border-gray-200 px-4 py-3 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-300">

                    <option value="">
                        Select a barangay...
                    </option>

                    @foreach($barangays as $barangay)

                        <option value="{{ $barangay->barangay_id }}"
                            {{ $selectedBarangayId==$barangay->barangay_id ? 'selected' : '' }}>

                            Barangay {{ $barangay->barangay_name }}

                        </option>

                    @endforeach

                </select>

                <button type="submit"
                    class="bg-red-600 text-white px-6 py-3 rounded-xl text-sm font-black hover:bg-red-700 transition">
                    View Leadership
                </button>

            </div>

        </form>

    </div>

</section>

{{-- NO CURRENT TERM --}}
@if(!$currentTerm)

    <section class="max-w-4xl mx-auto px-6 py-8">

        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm px-6 py-14 text-center">

            <div class="text-4xl mb-4">
                🏛️
            </div>

            <h3 class="font-black text-gray-700 text-lg">
                No Current Administration
            </h3>

            <p class="text-sm text-gray-400 mt-2">
                Current SK leadership information is not available at this time.
            </p>

        </div>

    </section>

{{-- NO BARANGAY SELECTED --}}
@elseif(!$selectedBarangay)

    <section class="max-w-4xl mx-auto px-6 py-8">

        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm px-6 py-14 text-center">

            <div class="w-16 h-16 mx-auto bg-red-50 text-red-600 rounded-full flex items-center justify-center text-3xl mb-4">
                👥
            </div>

            <h3 class="font-black text-gray-700 text-lg">
                Select Your Barangay
            </h3>

            <p class="text-sm text-gray-400 mt-2 max-w-md mx-auto">
                Choose a barangay above to view its current SK Chairman,
                Secretary, Treasurer, and Councilors.
            </p>

        </div>

    </section>

{{-- LEADERSHIP RESULTS --}}
@else

    <section class="max-w-4xl mx-auto px-6 py-8">

        {{-- RESULT HEADER --}}
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-6">

            <div>

                <p class="text-xs font-black uppercase tracking-widest text-red-600">
                    Current Leadership
                </p>

                <h3 class="text-3xl font-black text-gray-800 mt-1">
                    Barangay {{ $selectedBarangay->barangay_name }}
                </h3>

                <p class="text-xs text-gray-400 mt-2">
                    Administration {{ $currentTerm->start_year }}–{{ $currentTerm->end_year }}
                </p>

            </div>

            <div class="inline-flex self-start sm:self-auto items-center gap-2 bg-green-50 text-green-700 rounded-full px-3 py-2">

                <span class="w-2 h-2 bg-green-500 rounded-full"></span>

                <span class="text-[10px] font-black uppercase">
                    Current
                </span>

            </div>

        </div>

        {{-- PRIMARY OFFICIALS --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            {{-- CHAIRMAN --}}
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6">

                <div class="w-14 h-14 bg-red-100 text-red-600 rounded-full flex items-center justify-center text-xl font-black mb-5">

                    @if($chairman)
                        {{ strtoupper(substr($chairman->first_name,0,1)) }}{{ strtoupper(substr($chairman->last_name,0,1)) }}
                    @else
                        ?
                    @endif

                </div>

                <p class="text-[9px] font-black uppercase tracking-widest text-red-600">
                    SK Chairman
                </p>

                @if($chairman)

                    <h4 class="text-lg font-black text-gray-800 mt-2">
                        {{ $chairman->first_name }} {{ $chairman->last_name }}
                    </h4>

                    <p class="text-xs text-gray-400 mt-2">
                        Sangguniang Kabataan Chairman
                    </p>

                @else

                    <h4 class="text-sm font-bold text-gray-400 mt-2">
                        No current chairman listed
                    </h4>

                @endif

            </div>

            {{-- SECRETARY --}}
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6">

                <div class="w-14 h-14 bg-red-100 text-red-600 rounded-full flex items-center justify-center text-xl font-black mb-5">

                    @if($secretary)
                        {{ strtoupper(substr($secretary->first_name,0,1)) }}{{ strtoupper(substr($secretary->last_name,0,1)) }}
                    @else
                        ?
                    @endif

                </div>

                <p class="text-[9px] font-black uppercase tracking-widest text-red-600">
                    SK Secretary
                </p>

                @if($secretary)

                    <h4 class="text-lg font-black text-gray-800 mt-2">
                        {{ $secretary->first_name }} {{ $secretary->last_name }}
                    </h4>

                    <p class="text-xs text-gray-400 mt-2">
                        Sangguniang Kabataan Secretary
                    </p>

                @else

                    <h4 class="text-sm font-bold text-gray-400 mt-2">
                        No current secretary listed
                    </h4>

                @endif

            </div>

            {{-- TREASURER --}}
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6">

                <div class="w-14 h-14 bg-red-100 text-red-600 rounded-full flex items-center justify-center text-xl font-black mb-5">

                    @if($treasurer)
                        {{ strtoupper(substr(trim($treasurer->name),0,1)) }}
                    @else
                        ?
                    @endif

                </div>

                <p class="text-[9px] font-black uppercase tracking-widest text-red-600">
                    SK Treasurer
                </p>

                @if($treasurer)

                    <h4 class="text-lg font-black text-gray-800 mt-2">
                        {{ $treasurer->name }}
                    </h4>

                    <p class="text-xs text-gray-400 mt-2">
                        Sangguniang Kabataan Treasurer
                    </p>

                @else

                    <h4 class="text-sm font-bold text-gray-400 mt-2">
                        No current treasurer listed
                    </h4>

                @endif

            </div>

        </div>

        {{-- COUNCILORS --}}
        <div class="mt-8">

            <div class="mb-4">

                <p class="text-xs font-black uppercase tracking-widest text-red-600">
                    Council Members
                </p>

                <h3 class="text-2xl font-black text-gray-800 mt-1">
                    SK Councilors
                </h3>

            </div>

            @if($councilors->isNotEmpty())

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    @foreach($councilors as $councilor)

                        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5 flex items-center gap-4">

                            <div class="w-12 h-12 bg-gray-100 text-gray-600 rounded-full shrink-0 flex items-center justify-center font-black">
                                {{ strtoupper(substr(trim($councilor->name),0,1)) }}
                            </div>

                            <div class="min-w-0">

                                <h4 class="font-black text-gray-800 truncate">
                                    {{ $councilor->name }}
                                </h4>

                                <p class="text-[10px] font-bold text-red-600 uppercase mt-1">
                                    {{ $councilor->position ?: 'SK Councilor' }}
                                </p>

                            </div>

                        </div>

                    @endforeach

                </div>

            @else

                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm px-6 py-10 text-center">

                    <div class="text-3xl mb-3">
                        👥
                    </div>

                    <h4 class="font-black text-gray-700">
                        No councilors listed
                    </h4>

                    <p class="text-xs text-gray-400 mt-2">
                        There are currently no council members available for this barangay.
                    </p>

                </div>

            @endif

        </div>

        {{-- PRIVACY NOTE --}}
        <div class="mt-8 bg-gray-100 rounded-2xl px-5 py-4 flex items-start gap-3">

            <div class="text-lg">
                ℹ️
            </div>

            <p class="text-xs text-gray-500 leading-relaxed">
                This public directory displays official names and positions only.
                Private contact information such as email addresses and phone numbers
                is not displayed.
            </p>

        </div>

    </section>

@endif

</main>

{{-- BACK TO TOP --}}
<button id="backToTopBtn"
    type="button"
    title="Back to top"
    class="hidden fixed bottom-6 right-6 z-50 w-12 h-12 rounded-full bg-red-600 text-white shadow-xl hover:bg-red-700 transition items-center justify-center text-xl">
    ↑
</button>

{{-- FOOTER --}}
<footer class="bg-gray-900 text-gray-400">

    <div class="max-w-6xl mx-auto px-6 py-6 text-center text-xs">
        &copy; {{ date('Y') }} SK360 • Sangguniang Kabataan Federation of Lipa City
    </div>

</footer>

<script>
/*
|--------------------------------------------------------------------------
| AUTO LOAD BARANGAY
|--------------------------------------------------------------------------
*/
const barangaySelect=document.getElementById('barangaySelect');
const barangayForm=document.getElementById('barangayForm');

barangaySelect.addEventListener('change',()=>{
    if(barangaySelect.value!==''){
        barangayForm.submit();
    }
});

/*
|--------------------------------------------------------------------------
| BACK TO TOP
|--------------------------------------------------------------------------
*/
const backToTopBtn=document.getElementById('backToTopBtn');

window.addEventListener('scroll',()=>{
    if(window.scrollY>400){
        backToTopBtn.classList.remove('hidden');
        backToTopBtn.classList.add('flex');
    }else{
        backToTopBtn.classList.add('hidden');
        backToTopBtn.classList.remove('flex');
    }
});

backToTopBtn.addEventListener('click',()=>{
    window.scrollTo({
        top:0,
        behavior:'smooth'
    });
});
</script>

</body>
</html>