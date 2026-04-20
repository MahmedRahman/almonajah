@php
    $cat = $section['category'];
    $assets = $section['assets'];
    $variant = $section['variant'] ?? 'scroll';
    $accent = $cat->color && preg_match('/^#[0-9A-Fa-f]{6}$/', $cat->color) ? $cat->color : null;
@endphp
<section class="audio-home-section audio-cat-section audio-cat-section--{{ $variant }}" @if($accent) style="--audio-cat-accent: {{ $accent }};" @endif>
    <div class="audio-home-section__head audio-cat-section__head">
        <div class="audio-cat-section__title-wrap">
            @if($cat->image_path)
                <img src="{{ asset('storage/' . $cat->image_path) }}" alt="" class="audio-cat-section__badge-img" width="36" height="36" loading="lazy" decoding="async">
            @endif
            <h2 class="audio-home-section__title audio-cat-section__title">{{ $cat->name }}</h2>
        </div>
        <a href="{{ route('audio.home', ['content_category' => $cat->name]) }}" class="audio-cat-section__see-all">
            عرض الكل
            <i class="bi bi-chevron-left" aria-hidden="true"></i>
        </a>
    </div>

    @if($variant === 'scroll')
        <div class="audio-home-row">
            @include('partials.home-audio-cards', ['assets' => $assets])
        </div>
    @elseif($variant === 'panel')
        <div class="audio-cat-section__panel-inner">
            <div class="audio-home-row">
                @include('partials.home-audio-cards', ['assets' => $assets])
            </div>
        </div>
    @elseif($variant === 'grid')
        <div class="video-grid video-grid--4col audio-cat-section__grid">
            @include('partials.home-audio-cards', ['assets' => $assets])
        </div>
    @endif
</section>
