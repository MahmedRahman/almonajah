@extends('layouts.public')

@section('title', $asset->title ?: $asset->file_name)

@section('content')
<div class="container-main">
    <div class="video-player-section">
        <!-- Main Video Section -->
        <div>
            <!-- Video Player -->
            @php
                $fileUrl = null;
                if ($asset->relative_path && strpos($asset->relative_path, 'assets/') === 0) {
                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($asset->relative_path)) {
                        $fileUrl = asset('storage/' . $asset->relative_path);
                    }
                }
            @endphp

            @if($fileUrl)
            <div class="video-player-container">
                @if(in_array(strtolower($asset->extension), ['mp4', 'mov', 'mkv', 'm4v', 'webm', 'avi']))
                    @php
                        $hlsMasterPlaylist = null;
                        if ($asset->hlsVersions && $asset->hlsVersions->count() > 0) {
                            $masterPlaylist = $asset->hlsVersions->firstWhere('master_playlist_path', '!=', null);
                            if ($masterPlaylist && $masterPlaylist->master_playlist_path) {
                                $hlsMasterPlaylist = asset('storage/' . $masterPlaylist->master_playlist_path);
                            }
                        }
                    @endphp
                    
                    <div class="video-wrapper">
                        <video 
                            id="mainVideoPlayer"
                            class="main-video-player"
                            controls 
                            playsinline
                            data-src="{{ $fileUrl }}"
                            data-hls="{{ $hlsMasterPlaylist }}"
                            style="width: 100%;">
                            متصفحك لا يدعم تشغيل الفيديو.
                        </video>
                        
                        @if($hlsMasterPlaylist && $asset->hlsVersions->count() > 1)
                        <!-- Quality Selector -->
                        <div class="video-quality-selector">
                            <button class="quality-btn-main" onclick="toggleQualityMenuMain(this)">
                                <i class="bi bi-gear"></i>
                                <span>الجودة</span>
                            </button>
                            <div class="quality-menu-main">
                                @foreach($asset->hlsVersions->sortByDesc('height') as $hlsVersion)
                                <button class="quality-option-main" 
                                        data-playlist="{{ asset('storage/' . $hlsVersion->playlist_path) }}"
                                        data-resolution="{{ $hlsVersion->resolution }}"
                                        onclick="changeQualityMain(this, '{{ $hlsVersion->resolution }}')">
                                    {{ $hlsVersion->resolution }}
                                    <small>({{ $hlsVersion->bitrate }})</small>
                                </button>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                @elseif(in_array(strtolower($asset->extension), ['mp3', 'wav', 'ogg', 'm4a', 'aac']))
                    <div style="padding: 3rem; text-align: center; background-color: var(--bg-tertiary);">
                        <audio controls style="width: 100%; max-width: 500px;">
                            <source src="{{ $fileUrl }}" type="audio/{{ $asset->extension }}">
                            متصفحك لا يدعم تشغيل الصوت.
                        </audio>
                    </div>
                @endif
            </div>
            @endif

            <!-- Video Details -->
            <div class="video-details">
                <h1 class="video-details-title">{{ $asset->title ?: $asset->file_name }}</h1>
                
                <div class="video-details-meta">
                    @if($asset->speaker_name)
                        <span>
                            <i class="bi bi-person"></i>
                            {{ $asset->speaker_name }}
                        </span>
                    @endif
                    @if($asset->category)
                        <span>
                            <i class="bi bi-tag"></i>
                            {{ $asset->category }}
                        </span>
                    @endif
                    @if($asset->year)
                        <span>
                            <i class="bi bi-calendar"></i>
                            {{ $asset->year }}
                        </span>
                    @endif
                    @if($asset->duration_seconds)
                        <span>
                            <i class="bi bi-clock"></i>
                            {{ $asset->duration_formatted }}
                        </span>
                    @endif
                </div>

                @if($asset->topics || $asset->emotions || $asset->intent || $asset->audience)
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: var(--spacing-md);">
                    @if($asset->topics)
                        @foreach(explode("\n", $asset->topics) as $topic)
                            @if(trim($topic))
                                <span class="badge badge-primary">{{ trim($topic) }}</span>
                            @endif
                        @endforeach
                    @endif
                </div>
                @endif

                @if($asset->transcription)
                <div class="video-description">
                    <div class="video-description-title">الوصف</div>
                    <div class="video-description-text">{{ $asset->transcription }}</div>
                </div>
                @endif
            </div>
        </div>

        <!-- Sidebar - Related Videos -->
        <div>
            <div class="sidebar">
                <h6 class="sidebar-title">فيديوهات مقترحة</h6>
                
                @if(isset($relatedAssets) && $relatedAssets->count() > 0)
                    @foreach($relatedAssets as $relatedAsset)
                    <a href="{{ route('assets.show.public', $relatedAsset) }}" class="related-video">
                        <div class="related-video-thumb">
                            @php
                                $relatedFileUrl = null;
                                if ($relatedAsset->relative_path && strpos($relatedAsset->relative_path, 'assets/') === 0) {
                                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($relatedAsset->relative_path)) {
                                        $relatedFileUrl = asset('storage/' . $relatedAsset->relative_path);
                                    }
                                }
                            @endphp
                            
                            @if($relatedFileUrl && in_array(strtolower($relatedAsset->extension), ['mp4', 'mov', 'mkv', 'm4v', 'webm', 'avi']))
                                <video muted preload="metadata">
                                    <source src="{{ $relatedFileUrl }}#t=1" type="video/{{ $relatedAsset->extension }}">
                                </video>
                            @else
                                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                    <i class="bi bi-play-circle" style="font-size: 2rem; color: white;"></i>
                                </div>
                            @endif
                            
                            @if($relatedAsset->duration_seconds)
                                <span class="video-duration" style="font-size: 0.7rem; padding: 1px 4px;">{{ $relatedAsset->duration_formatted }}</span>
                            @endif
                        </div>
                        <div class="related-video-info">
                            <div class="related-video-title">{{ \Illuminate\Support\Str::limit($relatedAsset->title ?: $relatedAsset->file_name, 60) }}</div>
                            @if($relatedAsset->speaker_name)
                            <div class="related-video-meta">{{ $relatedAsset->speaker_name }}</div>
                            @endif
                        </div>
                    </a>
                    @endforeach
                @else
                    <div class="empty-state" style="padding: 2rem 0;">
                        <p style="font-size: 0.875rem;">لا توجد فيديوهات مقترحة</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.video-wrapper {
    position: relative;
    width: 100%;
    background-color: #000;
    border-radius: var(--radius-md);
    overflow: hidden;
}

.main-video-player {
    width: 100%;
    display: block;
    max-height: 80vh;
}

.video-quality-selector {
    position: absolute;
    top: 1rem;
    right: 1rem;
    z-index: 100;
}

.quality-btn-main {
    background: rgba(0, 0, 0, 0.7);
    border: none;
    border-radius: 20px;
    color: white;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    backdrop-filter: blur(10px);
    transition: all 0.3s;
}

.quality-btn-main:hover {
    background: rgba(0, 0, 0, 0.9);
    transform: scale(1.05);
}

.quality-menu-main {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 0.5rem;
    background: rgba(0, 0, 0, 0.95);
    border-radius: 8px;
    padding: 0.5rem;
    min-width: 150px;
    display: none;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
}

.quality-option-main {
    display: block;
    width: 100%;
    background: transparent;
    border: none;
    color: white;
    padding: 0.75rem 1rem;
    text-align: right;
    cursor: pointer;
    border-radius: 4px;
    transition: all 0.2s;
    font-size: 0.875rem;
}

.quality-option-main:hover {
    background: rgba(255, 255, 255, 0.1);
}

.quality-option-main.active {
    background: rgba(76, 175, 80, 0.3);
    color: #4CAF50;
    font-weight: 600;
}

.quality-option-main small {
    display: block;
    font-size: 0.75rem;
    opacity: 0.7;
    margin-top: 0.25rem;
}
</style>
@endpush

@push('scripts')
<!-- HLS.js Library -->
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>

<script>
let hlsInstance = null;
let currentVideo = null;

// Initialize video player with HLS support
document.addEventListener('DOMContentLoaded', function() {
    currentVideo = document.getElementById('mainVideoPlayer');
    if (!currentVideo) return;
    
    const hlsUrl = currentVideo.getAttribute('data-hls');
    const regularSrc = currentVideo.getAttribute('data-src');
    
    // Use HLS if available, otherwise use regular video
    if (hlsUrl && Hls.isSupported()) {
        // Use HLS.js for adaptive streaming
        hlsInstance = new Hls({
            enableWorker: true,
            lowLatencyMode: false,
            backBufferLength: 90,
            maxBufferLength: 30,
            maxMaxBufferLength: 60,
            startLevel: -1, // Auto quality
            capLevelToPlayerSize: true
        });
        
        hlsInstance.loadSource(hlsUrl);
        hlsInstance.attachMedia(currentVideo);
        
        hlsInstance.on(Hls.Events.MANIFEST_PARSED, function() {
            currentVideo.play().catch(() => {});
        });
        
        // Handle errors
        hlsInstance.on(Hls.Events.ERROR, function(event, data) {
            if (data.fatal) {
                switch(data.type) {
                    case Hls.ErrorTypes.NETWORK_ERROR:
                        console.log('HLS Network Error, trying to recover...');
                        hlsInstance.startLoad();
                        break;
                    case Hls.ErrorTypes.MEDIA_ERROR:
                        console.log('HLS Media Error, trying to recover...');
                        hlsInstance.recoverMediaError();
                        break;
                    default:
                        console.log('HLS Fatal Error, falling back to regular video');
                        if (hlsInstance) {
                            hlsInstance.destroy();
                            hlsInstance = null;
                        }
                        // Fallback to regular video
                        if (regularSrc) {
                            currentVideo.src = regularSrc;
                            currentVideo.load();
                        }
                        break;
                }
            }
        });
    } else if (hlsUrl && currentVideo.canPlayType('application/vnd.apple.mpegurl')) {
        // Native HLS support (Safari)
        currentVideo.src = hlsUrl;
    } else if (regularSrc) {
        // Fallback to regular video
        currentVideo.src = regularSrc;
        currentVideo.load();
    }
});

// Change quality function
function changeQualityMain(btn, resolution) {
    if (!currentVideo) return;
    
    const playlistUrl = btn.getAttribute('data-playlist');
    
    if (hlsInstance && Hls.isSupported()) {
        // Try to find matching level
        const targetHeight = parseInt(resolution.replace('p', ''));
        const levels = hlsInstance.levels;
        
        if (levels && levels.length > 0) {
            const targetLevel = levels.findIndex(level => {
                return level.height === targetHeight || 
                       Math.abs(level.height - targetHeight) < 50;
            });
            
            if (targetLevel !== -1) {
                hlsInstance.currentLevel = targetLevel;
            } else if (playlistUrl) {
                // If level not found, reload with specific playlist
                hlsInstance.destroy();
                hlsInstance = new Hls({
                    enableWorker: true,
                    lowLatencyMode: false,
                    backBufferLength: 90
                });
                hlsInstance.loadSource(playlistUrl);
                hlsInstance.attachMedia(currentVideo);
                hlsInstance.on(Hls.Events.MANIFEST_PARSED, function() {
                    currentVideo.play().catch(() => {});
                });
            }
        }
    } else if (playlistUrl && currentVideo.canPlayType('application/vnd.apple.mpegurl')) {
        // Native HLS support (Safari) - load specific playlist
        currentVideo.src = playlistUrl;
        currentVideo.load();
    }
    
    // Update quality menu
    document.querySelectorAll('.quality-option-main').forEach(opt => {
        opt.classList.remove('active');
    });
    btn.classList.add('active');
    
    // Close quality menu
    const menu = btn.closest('.quality-menu-main');
    if (menu) {
        menu.style.display = 'none';
    }
}

// Toggle quality menu
function toggleQualityMenuMain(btn) {
    const menu = btn.nextElementSibling;
    if (menu) {
        const isOpen = menu.style.display === 'block';
        menu.style.display = isOpen ? 'none' : 'block';
    }
}

// Close quality menu when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.video-quality-selector')) {
        document.querySelectorAll('.quality-menu-main').forEach(menu => {
            menu.style.display = 'none';
        });
    }
});

// Auto-play related video thumbnails on hover
document.querySelectorAll('.related-video-thumb video').forEach(video => {
    const relatedVideo = video.closest('.related-video');
    let hoverTimeout;
    
    relatedVideo.addEventListener('mouseenter', () => {
        hoverTimeout = setTimeout(() => {
            video.play().catch(() => {});
        }, 500);
    });
    
    relatedVideo.addEventListener('mouseleave', () => {
        clearTimeout(hoverTimeout);
        video.pause();
        video.currentTime = 0;
    });
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (hlsInstance) {
        hlsInstance.destroy();
        hlsInstance = null;
    }
});
</script>
@endpush
