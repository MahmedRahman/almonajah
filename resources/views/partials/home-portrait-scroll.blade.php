@if(isset($assets) && $assets->count() > 0)
<section class="home-section home-section--portrait">
    <h2 class="home-section-title">
        <a href="{{ route('public.portrait-videos') }}" class="home-section-title-link">فيديوهات طولية</a>
    </h2>
    <div class="portrait-scroll-wrap">
        <button type="button"
                class="portrait-scroll-btn portrait-scroll-btn--prev"
                aria-label="عرض الفيديوهات السابقة"
                hidden>
            <i class="bi bi-chevron-left" aria-hidden="true"></i>
        </button>
        <button type="button"
                class="portrait-scroll-btn portrait-scroll-btn--next"
                aria-label="عرض المزيد من الفيديوهات الطولية"
                hidden>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
        </button>
        <div class="portrait-scroll-track">
            @include('partials.home-video-cards', [
                'assets' => $assets,
                'forceLandscape' => false,
                'useThumbnail' => true,
            ])
        </div>
    </div>
</section>
@endif
