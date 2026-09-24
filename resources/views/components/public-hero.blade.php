{{-- Soft page hero for public portal pages: eyebrow, title, description and optional extras. --}}
@props(['eyebrow' => null, 'title', 'centered' => false, 'width' => 'max-w-4xl'])

{{-- $width matches the page's content column so the hero text lines up with it. --}}
<section class="sk-phero">
    <div class="{{ $width }} mx-auto px-6 {{ $centered ? 'text-center' : '' }}">
        @if ($eyebrow)
            <span class="sk-eyebrow">
                <span class="sk-dot"></span>
                {{ $eyebrow }}
            </span>
        @endif

        <h2 class="sk-phero__title {{ $centered ? 'mx-auto' : '' }}">{{ $title }}</h2>

        @if (trim($slot) !== '')
            <p class="sk-phero__lead {{ $centered ? 'mx-auto' : '' }}">{{ $slot }}</p>
        @endif

        {{ $extra ?? '' }}
    </div>
</section>
