@foreach($assets as $asset)
<a href="{{ route('assets.show.public', $asset) }}" class="video-card">
    <div class="video-thumbnail">
        @if($asset->thumbnail_path)
            <img src="{{ asset('storage/' . $asset->thumbnail_path) }}"
                 alt="{{ $asset->title ?: $asset->file_name }}"
                 loading="lazy"
                 width="320"
                 height="180"
                 decoding="async"
                 fetchpriority="low"
                 style="opacity: 0; transition: opacity 0.3s;"
                 onload="this.style.opacity='1'"
                 onerror="this.onerror=null; this.src='{{ asset('images/logo_min.png') }}';">
        @else
            <img src="{{ asset('images/logo_min.png') }}"
                 alt="{{ $asset->title ?: $asset->file_name }}"
                 loading="lazy"
                 width="320"
                 height="180"
                 decoding="async"
                 fetchpriority="low"
                 style="opacity: 0; transition: opacity 0.3s; object-fit: cover;"
                 onload="this.style.opacity='1'">
        @endif

        @if($asset->computed_duration)
            <span class="video-duration">{{ $asset->computed_duration }}</span>
        @endif
    </div>

    <div class="video-info">
        <div class="video-info-header">
            <div class="video-channel-avatar">
                <img src="{{ asset('images/logo_min.png') }}" alt="المناجاة" class="avatar-logo">
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
