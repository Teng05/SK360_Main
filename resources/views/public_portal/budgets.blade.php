{{-- File guide: Blade view template for resources/views/public_portal/budgets.blade.php. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Annual Budget & LYDP | SK360 Public Portal
    </title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 text-gray-800">

<header class="sticky top-0 z-40 bg-red-600 text-white shadow">

    <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">

        <a
            href="{{ route('public.home') }}"
            class="flex items-center gap-3"
        >

            <img
                src="{{ asset('images/logo.png') }}"
                class="w-10 h-10 rounded-full object-cover"
                alt="SK360 Logo"
            >

            <div>

                <h1 class="text-xl font-black">
                    SK 360°
                </h1>

                <p class="text-[10px] opacity-80 uppercase tracking-widest">
                    Public Information Portal
                </p>

            </div>

        </a>

        <a
            href="{{ route('public.home') }}"
            class="text-xs font-bold hover:text-yellow-300 transition"
        >
            ← Public Portal
        </a>

    </div>

</header>

<main>

    {{-- HERO --}}
    <section class="bg-gradient-to-br from-red-600 to-red-700 text-white">

        <div class="max-w-4xl mx-auto px-6 py-12">

            <p class="text-xs font-black uppercase tracking-[0.2em] text-red-100">
                Public Transparency
            </p>

            <h2 class="text-4xl font-black mt-2">
                Annual Budget & LYDP
            </h2>

            <p class="text-red-100 text-sm mt-3 max-w-2xl leading-relaxed">
                View published Annual Budget records and the Local Youth Development Plan available through the SK360 Public Information Portal.
            </p>

        </div>

    </section>

    <section class="max-w-6xl mx-auto px-6 py-10">

        {{-- LYDP --}}
        @php
            $lydpRelativePath='uploads/public_documents/lydp.pdf';
            $lydpAvailable=file_exists(public_path($lydpRelativePath));
        @endphp

        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 mb-8">

            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-5">

                <div class="flex items-start gap-4">

                    <div class="w-12 h-12 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center text-xl shrink-0">
                        📘
                    </div>

                    <div>

                        <p class="text-xs font-black uppercase tracking-widest text-red-600">
                            Youth Development Plan
                        </p>

                        <h3 class="text-2xl font-black text-gray-800 mt-1">
                            Local Youth Development Plan (LYDP)
                        </h3>

                        <p class="text-sm text-gray-500 mt-2 max-w-2xl leading-relaxed">
                            View the published LYDP document for public reference and transparency.
                        </p>

                    </div>

                </div>

                @if($lydpAvailable)

                    <a
                        href="{{ asset($lydpRelativePath) }}"
                        target="_blank"
                        rel="noopener"
                        class="shrink-0 inline-flex items-center justify-center gap-2 rounded-xl bg-red-600 px-5 py-3 text-xs font-black uppercase text-white hover:bg-red-700 transition"
                    >
                        <span>📄</span>
                        View LYDP PDF
                    </a>

                @else

                    <span class="shrink-0 inline-flex items-center justify-center rounded-xl bg-gray-100 px-5 py-3 text-xs font-black uppercase text-gray-500">
                        LYDP Not Available
                    </span>

                @endif

            </div>

        </div>

        {{-- FILTERS --}}
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 mb-8">

            <div class="mb-5">

                <p class="text-xs font-black uppercase tracking-widest text-red-600">
                    Search Budget Records
                </p>

                <h3 class="text-2xl font-black text-gray-800 mt-1">
                    Select Fiscal Year and Barangay
                </h3>

                <p class="text-sm text-gray-500 mt-2">
                    Filter the Annual Budget records available in the SK360 Public Portal.
                </p>

            </div>

            <form
                action="{{ route('public.budgets') }}"
                method="GET"
                class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end"
            >

                <div>

                    <label
                        for="year"
                        class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2"
                    >
                        Fiscal Year
                    </label>

                    <select
                        id="year"
                        name="year"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-bold text-gray-700 outline-none focus:border-red-300 focus:ring-2 focus:ring-red-100"
                    >

                        @if($availableYears->isEmpty())

                            <option value="{{ $selectedYear }}">
                                FY {{ $selectedYear }}
                            </option>

                        @else

                            @foreach($availableYears as $year)

                                <option
                                    value="{{ $year }}"
                                    {{ (int) $selectedYear === (int) $year ? 'selected' : '' }}
                                >
                                    FY {{ $year }}
                                </option>

                            @endforeach

                        @endif

                    </select>

                </div>

                <div>

                    <label
                        for="barangay_id"
                        class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2"
                    >
                        Barangay
                    </label>

                    <select
                        id="barangay_id"
                        name="barangay_id"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-bold text-gray-700 outline-none focus:border-red-300 focus:ring-2 focus:ring-red-100"
                    >

                        <option value="">
                            All Barangays
                        </option>

                        @foreach($barangays as $barangay)

                            <option
                                value="{{ $barangay->barangay_id }}"
                                {{ (int) $selectedBarangay === (int) $barangay->barangay_id ? 'selected' : '' }}
                            >
                                {{ $barangay->barangay_name }}
                            </option>

                        @endforeach

                    </select>

                </div>

                <button
                    type="submit"
                    class="w-full rounded-xl bg-red-600 px-6 py-3 text-sm font-black uppercase text-white shadow-sm hover:bg-red-700 transition"
                >
                    View Budget
                </button>

            </form>

        </div>

        {{-- SUMMARY --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">

                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">
                    Fiscal Year
                </p>

                <p class="text-3xl font-black text-gray-800 mt-2">
                    {{ $selectedYear }}
                </p>

            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">

                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">
                    Budgets Available
                </p>

                <p class="text-3xl font-black text-red-600 mt-2">
                    {{ $publishedCount }}
                </p>

            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">

                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">
                    Total Reported Budget
                </p>

                <p class="text-2xl font-black text-gray-800 mt-2">
                    ₱{{ number_format($totalBudget, 2) }}
                </p>

            </div>

        </div>

        {{-- SECTION TITLE --}}
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">

            <div>

                <p class="text-xs font-black uppercase tracking-widest text-red-600">
                    Annual Budget Records
                </p>

                <h3 class="text-3xl font-black mt-1">
                    FY {{ $selectedYear }}
                </h3>

                <p class="text-sm text-gray-500 mt-2">
                    Annual Budget information below is based on records submitted through SK360.
                </p>

                <p class="text-xs text-gray-400 mt-2">
                    Showing
                    <strong>{{ $budgetItems->firstItem() ?? 0 }}</strong>
                    to
                    <strong>{{ $budgetItems->lastItem() ?? 0 }}</strong>
                    of
                    <strong>{{ $budgetItems->total() }}</strong>
                    barangay{{ $budgetItems->total() === 1 ? '' : 's' }}
                </p>

            </div>

            @if($selectedBarangay)

                @php
                    $selectedBarangayName = optional(
                        $barangays->firstWhere(
                            'barangay_id',
                            $selectedBarangay
                        )
                    )->barangay_name;
                @endphp

                @if($selectedBarangayName)

                    <div class="inline-flex items-center rounded-full bg-red-50 px-4 py-2 text-xs font-black uppercase text-red-600">
                        Barangay {{ $selectedBarangayName }}
                    </div>

                @endif

            @endif

        </div>

        {{-- BUDGET CARDS --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">

            @forelse($budgetItems as $item)

                <article class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">

                    <div class="p-6">

                        <div class="flex items-start justify-between gap-4">

                            <div>

                                <p class="text-[10px] font-black uppercase tracking-widest text-red-500">
                                    Sangguniang Kabataan
                                </p>

                                <h4 class="text-xl font-black text-gray-800 mt-1">
                                    Barangay {{ $item->barangay_name }}
                                </h4>

                            </div>

                            @if($item->has_budget)

                                <span class="shrink-0 rounded-full bg-green-50 px-3 py-1 text-[9px] font-black uppercase text-green-600">
                                    Available
                                </span>

                            @else

                                <span class="shrink-0 rounded-full bg-gray-100 px-3 py-1 text-[9px] font-black uppercase text-gray-500">
                                    Not Available
                                </span>

                            @endif

                        </div>

                        @if($item->has_budget)

                            <div class="mt-6">

                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">
                                    Annual Budget
                                </p>

                                @if(
                                    $item->total_amount !== null &&
                                    $item->total_amount > 0
                                )

                                    <p class="text-3xl font-black text-red-600 mt-1 break-words">
                                        ₱{{ number_format($item->total_amount, 2) }}
                                    </p>

                                @else

                                    <p class="text-sm font-bold text-gray-500 mt-2">
                                        Amount not encoded
                                    </p>

                                @endif

                            </div>

                            <div class="mt-5 border-t border-gray-100 pt-4 space-y-3 text-xs text-gray-500">

                                <div class="flex items-center justify-between gap-4">

                                    <span class="font-bold text-gray-400">
                                        Fiscal Year
                                    </span>

                                    <span class="font-black text-gray-700">
                                        FY {{ $item->fiscal_year }}
                                    </span>

                                </div>

                                @if($item->submitted_at)

                                    <div class="flex items-center justify-between gap-4">

                                        <span class="font-bold text-gray-400">
                                            Submitted
                                        </span>

                                        <span class="font-black text-gray-700">
                                            {{ \Carbon\Carbon::parse($item->submitted_at)->format('M d, Y') }}
                                        </span>

                                    </div>

                                @endif

                            </div>

                            @if(!empty($item->uploaded_file_path))

                                <a
                                    href="{{ asset($item->uploaded_file_path) }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="mt-5 flex items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-3 text-xs font-black uppercase text-white hover:bg-red-700 transition"
                                >
                                    <span>📄</span>
                                    View Annual Budget
                                </a>

                            @else

                                <div class="mt-5 rounded-xl bg-gray-100 px-4 py-3 text-center text-xs font-bold text-gray-500">
                                    Annual Budget document unavailable.
                                </div>

                            @endif

                        @else

                            <div class="mt-6 rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-6 text-center">

                                <div class="text-3xl mb-3">
                                    📄
                                </div>

                                <p class="font-black text-gray-600">
                                    No Annual Budget Available
                                </p>

                                <p class="text-xs text-gray-400 mt-2">
                                    No Annual Budget record has been submitted for FY {{ $selectedYear }}.
                                </p>

                            </div>

                        @endif

                    </div>

                </article>

            @empty

                <div class="md:col-span-2 xl:col-span-3">

                    <div class="bg-white rounded-3xl border border-dashed border-gray-200 p-12 text-center">

                        <div class="text-4xl mb-4">
                            📊
                        </div>

                        <h4 class="text-lg font-black text-gray-700">
                            No Budget Records Found
                        </h4>

                        <p class="text-sm text-gray-400 mt-2">
                            No barangay records matched the selected filters.
                        </p>

                    </div>

                </div>

            @endforelse

        </div>

        @if($budgetItems->hasPages())
            <div class="mt-8">
                {{ $budgetItems->links() }}
            </div>
        @endif

    </section>

</main>

<footer class="bg-gray-900 text-gray-400">

    <div class="max-w-6xl mx-auto px-6 py-6 text-center text-xs">
        &copy; {{ date('Y') }} SK360 • Sangguniang Kabataan Federation of Lipa City
    </div>

</footer>

</body>
</html>
