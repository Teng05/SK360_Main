@php
    $heroCentered=$centered ?? false;
    $heroWidth=$width ?? 'max-w-4xl';
@endphp
<section class="sk-phero">
    <div class="{{ $heroWidth }} mx-auto px-6 {{ $heroCentered ? 'text-center' : '' }}">
        @if(!empty($eyebrow))
            <span class="sk-eyebrow"><span class="sk-dot"></span>{{ $eyebrow }}</span>
        @endif
        <h2 class="sk-phero__title {{ $heroCentered ? 'mx-auto' : '' }}">{{ $title }}</h2>
        @if(!empty($description))
            <p class="sk-phero__lead {{ $heroCentered ? 'mx-auto' : '' }}">{{ $description }}</p>
        @endif
        @if(!empty($extra))
            {!! $extra !!}
        @endif
    </div>
</section>
