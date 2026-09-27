{{-- Public portal pages are standalone documents: load the isolated web design system after Tailwind CDN. --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script>
    if(window.tailwind){
        window.tailwind.config={theme:{extend:{fontFamily:{sans:['Manrope','ui-sans-serif','system-ui','sans-serif']}}}};
    }
</script>
<link rel="stylesheet" href="{{ asset('css/sk360-public.css') }}?v={{ @filemtime(public_path('css/sk360-public.css')) }}">
