@foreach($assets as $asset)
@php
    $forceLandscape = $forceLandscape ?? false;
    $useThumbnail = $useThumbnail ?? false;
    $isPortraitCard = !$forceLandscape && ($asset->orientation ?? '') === 'portrait';
    $useThumbnailForImage = $useThumbnail || $isPortraitCard;
    if ($useThumbnailForImage) {
        $cardImage = ($asset->thumbnail_path ?? $asset->cover_path)
            ? asset('storage/' . ($asset->thumbnail_path ?? $asset->cover_path))
            : asset('images/logo_min.png');
    } else {
        $cardImage = ($asset->cover_path ?? $asset->thumbnail_path)
            ? asset('storage/' . ($asset->cover_path ?? $asset->thumbnail_path))
            : asset('images/logo_min.png');
    }
@endphp
<a href="{{ route('assets.show.public', $asset) }}" class="video-card {{ (!$forceLandscape && ($asset->orientation ?? '') === 'portrait') ? 'video-card--portrait' : '' }}">
    <div class="video-thumbnail">
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

        @if($asset->computed_duration)
            <span class="video-duration">{{ $asset->computed_duration }}</span>
        @endif
    </div>

    <div class="video-info">
        <div class="video-info-header">
            <div class="video-channel-avatar">
                <img src="{{ asset('images/logo_min.png') }}" alt="المناجاة" class="avatar-logo" decoding="async">
            </div>
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
    </div>
</a>
@endforeach
