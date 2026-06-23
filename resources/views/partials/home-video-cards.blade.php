@foreach($assets as $asset)
@php
    $forceLandscape = $forceLandscape ?? false;
    $forceSquare = $forceSquare ?? false;
    $useThumbnail = $useThumbnail ?? false;
    $useCover = $useCover ?? false;
    $isSquareAsset = $forceSquare
        || (!$forceLandscape && (
            ($asset->orientation ?? '') === 'square'
            || ($asset->aspect_ratio ?? '') === '1:1'
            || (
                ! empty($asset->width) && ! empty($asset->height)
                && abs((int) $asset->width - (int) $asset->height) <= max((int) $asset->width, (int) $asset->height) * 0.08
            )
        ));
    $isPortraitCard = ! $forceLandscape && ! $isSquareAsset && ($asset->orientation ?? '') === 'portrait';
    $useThumbnailForImage = ! $useCover && ($useThumbnail || $isPortraitCard);
    if ($useThumbnailForImage) {
        $cardImage = ($asset->thumbnail_path ?? $asset->cover_path)
            ? asset('storage/' . ($asset->thumbnail_path ?? $asset->cover_path))
            : asset('images/logo_min.png');
    } else {
        $cardImage = ($asset->cover_path ?? $asset->thumbnail_path)
            ? asset('storage/' . ($asset->cover_path ?? $asset->thumbnail_path))
            : asset('images/logo_min.png');
    }
    $cardClass = 'video-card';
    if ($isSquareAsset) {
        $cardClass .= ' video-card--square';
    } elseif ($isPortraitCard) {
        $cardClass .= ' video-card--portrait';
    }
@endphp
<a href="{{ route('assets.show.public', $asset) }}" class="{{ $cardClass }}">
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
             onload="(function(img){img.style.opacity='1';var box=img.closest('.video-thumbnail');if(!box)return;box.classList.add('img-loaded');var w=img.naturalWidth,h=img.naturalHeight;if(w>0&&h>0){var ratio=w/h;if(ratio>0.82&&ratio<1.22)box.classList.add('video-thumbnail--fit-contain');}})(this)"
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
