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
        <div class="video-info-header">
            <div class="video-channel-avatar">
                <i class="bi bi-music-note-list" style="font-size: 1.5rem; color: var(--primary-color);"></i>
            </div>
            <div class="video-info-content">
                <h3 class="video-title">{{ \Illuminate\Support\Str::limit($playlist->title, 60) }}</h3>
                <div class="video-meta">
                    @if($playlist->description)
                        <span class="video-channel-name">{{ \Illuminate\Support\Str::limit($playlist->description, 50) }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</a>
@endforeach
