@foreach($assets as $asset)
@php
    $isPortrait = ($asset->orientation ?? '') === 'portrait';
    // عمودي: الغلاف أولاً (مناسب للملصق العمودي)، ثم المصغّر. معالجة cover_path الفارغ نصاً
    $posterPath = ! empty($asset->cover_path) ? $asset->cover_path : (! empty($asset->thumbnail_path) ? $asset->thumbnail_path : null);
    $cardImage = $posterPath ? asset('storage/' . $posterPath) : asset('images/logo_min.png');
@endphp
<a href="{{ route('audio.show', $asset) }}" class="video-card {{ $isPortrait ? 'video-card--audio-portrait' : '' }}">
    <div class="video-thumbnail audio-card-thumb">
        <div class="shimmer-placeholder"></div>
        <img src="{{ $cardImage }}"
             alt="{{ $asset->title ?: $asset->file_name }}"
             loading="lazy"
             width="320"
             height="180"
             decoding="async"
             fetchpriority="low"
             style="opacity: 0; transition: opacity 0.3s;"
             onload="this.style.opacity='1'; var p=this.closest('.video-thumbnail'); if(p) p.classList.add('img-loaded');"
             onerror="this.onerror=null; this.src='{{ asset('images/logo_min.png') }}';">
        <span class="audio-card-badge" title="صوت"><i class="bi bi-mic-fill" aria-hidden="true"></i></span>
        @if($asset->computed_duration)
            <span class="video-duration">{{ $asset->computed_duration }}</span>
        @endif
    </div>

    <div class="video-info">
        <div class="video-info-content">
            <h3 class="video-title">{{ \Illuminate\Support\Str::limit($asset->title ?: $asset->file_name, 60) }}</h3>
            <div class="video-meta">
                @if($asset->speaker_name)
                    <span class="video-channel-name">{{ $asset->speaker_name }}</span>
                @endif
                @if($asset->categories && $asset->categories->count() > 0)
                    @foreach($asset->categories as $cat)
                        <span class="video-category">{{ $cat->name }}</span>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</a>
@endforeach
