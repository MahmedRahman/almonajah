@foreach($playlists as $playlist)
<a href="{{ route('public.playlist.show', $playlist) }}" class="video-card">
    <div class="video-thumbnail">
        @if($playlist->image_path)
            <img src="{{ asset('storage/' . $playlist->image_path) }}"
                 alt="{{ $playlist->title }}"
                 loading="lazy"
                 width="320"
                 height="180"
                 decoding="async"
                 fetchpriority="low"
                 style="opacity: 0; transition: opacity 0.3s;"
                 onload="this.style.opacity='1'"
                 onerror="this.onerror=null; this.src='{{ asset('images/logo_min.png') }}';">
        @else
            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <i class="bi bi-music-note-list text-white" style="font-size: 3rem;"></i>
            </div>
        @endif
        <span class="video-duration">
            <i class="bi bi-play-circle me-1"></i>{{ $playlist->total_videos_count ?? $playlist->assets_count ?? 0 }} فيديو
        </span>
    </div>
    <div class="video-info">
        <h3 class="video-title">{{ $playlist->title }}</h3>
        @if(!empty($showParent) && $playlist->parent)
            <p class="video-channel text-muted small mb-0">{{ $playlist->parent->title }}</p>
        @elseif($playlist->description)
            <p class="video-channel">{{ \Illuminate\Support\Str::limit($playlist->description, 60) }}</p>
        @endif
    </div>
</a>
@endforeach
