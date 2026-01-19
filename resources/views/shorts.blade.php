@extends('layouts.public')

@section('title', 'فيديوهات قصيرة - المناجاة')

@section('content')
<div class="shorts-fullscreen-container" id="shortsContainer">
    <!-- Close Button -->
    <button class="shorts-close-btn" onclick="closeShorts()" title="إغلاق">
        <i class="bi bi-x-lg"></i>
    </button>
    
    @if($shorts->count() > 0)
        <div class="shorts-viewer" id="shortsViewer">
            @foreach($shorts as $index => $short)
            @php
                $fileUrl = null;
                if ($short->relative_path && strpos($short->relative_path, 'assets/') === 0) {
                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($short->relative_path)) {
                        $fileUrl = asset('storage/' . $short->relative_path);
                    }
                }
            @endphp
            
            @if($fileUrl && in_array(strtolower($short->extension), ['mp4', 'mov', 'mkv', 'm4v', 'webm', 'avi']))
            <div class="short-video-container" data-index="{{ $index }}">
                @php
                    $hlsMasterPlaylist = null;
                    if ($short->hlsVersions && $short->hlsVersions->count() > 0) {
                        $masterPlaylist = $short->hlsVersions->firstWhere('master_playlist_path', '!=', null);
                        if ($masterPlaylist && $masterPlaylist->master_playlist_path) {
                            $hlsMasterPlaylist = asset('storage/' . $masterPlaylist->master_playlist_path);
                        }
                    }
                @endphp
                
                <video 
                    class="short-video" 
                    loop 
                    playsinline
                    data-src="{{ $fileUrl }}"
                    data-hls="{{ $hlsMasterPlaylist }}"
                    data-id="{{ $short->id }}"
                    @if($index === 0) autoplay @endif>
                </video>
                
                @if($hlsMasterPlaylist && $short->hlsVersions->count() > 1)
                <!-- Quality Selector -->
                <div class="short-quality-selector">
                    <button class="quality-btn" onclick="toggleQualityMenu(this)">
                        <i class="bi bi-gear"></i>
                        <span>الجودة</span>
                    </button>
                    <div class="quality-menu">
                        @foreach($short->hlsVersions->sortByDesc('height') as $hlsVersion)
                        <button class="quality-option" 
                                data-playlist="{{ asset('storage/' . $hlsVersion->playlist_path) }}"
                                onclick="changeQuality(this, '{{ $hlsVersion->resolution }}')">
                            {{ $hlsVersion->resolution }}
                        </button>
                        @endforeach
                    </div>
                </div>
                @endif
                
                <!-- Video Info Overlay -->
                <div class="short-video-info">
                    <div class="short-video-info-left">
                        <div class="short-channel-info">
                            @if($short->speaker_name)
                            <div class="short-channel-avatar">
                                {{ mb_substr($short->speaker_name, 0, 1) }}
                            </div>
                            <div class="short-channel-name">{{ $short->speaker_name }}</div>
                            @endif
                        </div>
                        <div class="short-video-title">{{ $short->title ?: $short->file_name }}</div>
                    </div>
                    
                    <div class="short-video-actions">
                        <button class="short-action-btn" onclick="toggleLike(this)">
                            <i class="bi bi-heart"></i>
                            <span>0</span>
                        </button>
                        <button class="short-action-btn" onclick="toggleComment(this)">
                            <i class="bi bi-chat"></i>
                            <span>0</span>
                        </button>
                        <button class="short-action-btn" onclick="shareShort(this)">
                            <i class="bi bi-share"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Play/Pause Overlay -->
                <div class="short-play-overlay" onclick="togglePlayPause(this)">
                    <i class="bi bi-play-circle-fill"></i>
                </div>
            </div>
            @endif
            @endforeach
        </div>
        
        <!-- Navigation Dots -->
        <div class="shorts-navigation">
            @foreach($shorts as $index => $short)
            <div class="nav-dot @if($index === 0) active @endif" data-index="{{ $index }}" onclick="scrollToShort({{ $index }})"></div>
            @endforeach
        </div>
    @else
        <div class="empty-state">
            <p>لا توجد فيديوهات قصيرة متاحة</p>
        </div>
    @endif
</div>
@endsection

@push('styles')
<style>
.shorts-fullscreen-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100vh;
    background-color: #000;
    overflow: hidden;
    z-index: 1000;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.shorts-close-btn {
    position: fixed;
    top: calc(1rem + env(safe-area-inset-top, 0));
    left: calc(1rem + env(safe-area-inset-left, 0));
    width: 48px;
    height: 48px;
    background: rgba(0, 0, 0, 0.75);
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    z-index: 2000;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
    backdrop-filter: blur(10px);
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    min-width: 48px;
    min-height: 48px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    padding: 0;
    margin: 0;
}

.shorts-close-btn:hover {
    background: rgba(0, 0, 0, 0.9);
    border-color: rgba(255, 255, 255, 0.5);
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
}

.shorts-close-btn:active {
    transform: scale(0.95);
}

.shorts-viewer {
    width: 100%;
    height: 100%;
    overflow-y: scroll;
    scroll-snap-type: y mandatory;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;
    touch-action: pan-y;
    position: relative;
    scroll-padding: 0;
}

.shorts-viewer::-webkit-scrollbar {
    display: none;
}

.shorts-viewer {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.short-video-container {
    width: 100%;
    height: 100vh;
    height: 100dvh;
    position: relative;
    scroll-snap-align: start;
    scroll-snap-stop: always;
    scroll-margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #000;
    flex-shrink: 0;
    box-sizing: border-box;
}

.short-video {
    width: 100%;
    height: 100%;
    object-fit: contain;
    background-color: #000;
    touch-action: none;
}

.short-video-info {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 1.75rem;
    padding-bottom: calc(2.25rem + env(safe-area-inset-bottom, 0));
    background: linear-gradient(to top, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.6) 40%, rgba(0,0,0,0.3) 70%, transparent 100%);
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    z-index: 10;
    min-height: 140px;
    backdrop-filter: blur(2px);
}

.short-video-info-left {
    flex: 1;
    color: white;
}

.short-channel-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.short-channel-avatar {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.375rem;
    border: 3px solid rgba(255, 255, 255, 0.4);
    flex-shrink: 0;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.short-channel-avatar:hover {
    transform: scale(1.1);
    border-color: rgba(255, 255, 255, 0.6);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
}

.short-channel-name {
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s;
    text-shadow: 0 2px 4px rgba(0,0,0,0.7);
    color: white;
    letter-spacing: 0.3px;
}

.short-channel-name:hover {
    opacity: 0.9;
    text-shadow: 0 2px 6px rgba(0,0,0,0.9);
}

.short-video-title {
    font-size: 0.9375rem;
    color: rgba(255, 255, 255, 0.98);
    margin-top: 0.75rem;
    line-height: 1.6;
    max-width: 85%;
    text-shadow: 0 2px 4px rgba(0,0,0,0.7);
    font-weight: 500;
    letter-spacing: 0.2px;
}

.short-video-actions {
    display: flex;
    flex-direction: column;
    gap: 1.75rem;
    align-items: center;
    margin-left: 1rem;
}

.short-action-btn {
    background: rgba(255, 255, 255, 0.1);
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    color: white;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    font-size: 1.75rem;
    transition: all 0.3s ease;
    padding: 0.5rem;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    width: 56px;
    height: 56px;
    backdrop-filter: blur(10px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.short-action-btn:hover {
    transform: scale(1.15);
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.4);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
}

.short-action-btn:active {
    transform: scale(0.95);
}

.short-action-btn.liked {
    background: rgba(255, 59, 92, 0.3);
    border-color: rgba(255, 59, 92, 0.5);
    color: #ff3b5c;
}

.short-action-btn.liked i {
    color: #ff3b5c;
}

.short-action-btn i {
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.4));
    transition: all 0.3s ease;
}

.short-action-btn span {
    font-size: 0.75rem;
    font-weight: 700;
    text-shadow: 0 2px 4px rgba(0,0,0,0.6);
    color: white;
    min-height: 14px;
}

.short-play-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 100px;
    height: 100px;
    background: rgba(0, 0, 0, 0.75);
    border-radius: 50%;
    display: none;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 20;
    transition: all 0.3s ease;
    backdrop-filter: blur(15px);
    border: 3px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.5);
}

.short-video-container.paused .short-play-overlay {
    display: flex;
}

.short-play-overlay:hover {
    background: rgba(0, 0, 0, 0.85);
    transform: translate(-50%, -50%) scale(1.1);
    border-color: rgba(255, 255, 255, 0.5);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.7);
}

.short-play-overlay:active {
    transform: translate(-50%, -50%) scale(0.95);
}

.short-play-overlay i {
    font-size: 4rem;
    color: white;
    margin-left: 6px; /* RTL adjustment */
    filter: drop-shadow(0 2px 8px rgba(0,0,0,0.6));
    transition: all 0.3s ease;
}

.shorts-navigation {
    position: fixed;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
    z-index: 100;
    padding: 0.75rem 0.5rem;
    background: rgba(0, 0, 0, 0.5);
    border-radius: 24px;
    backdrop-filter: blur(15px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    max-height: 80vh;
    overflow-y: auto;
    overflow-x: hidden;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
}

.shorts-navigation::-webkit-scrollbar {
    width: 2px;
}

.shorts-navigation::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 1px;
}

.nav-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    transition: all 0.3s ease;
    border: 1.5px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

.nav-dot:hover {
    background: rgba(255, 255, 255, 0.8);
    transform: scale(1.3);
    border-color: rgba(255, 255, 255, 0.5);
}

.nav-dot.active {
    background: var(--primary-color);
    width: 8px;
    height: 8px;
    border-color: rgba(255, 255, 255, 0.6);
    box-shadow: 0 0 12px rgba(24, 135, 129, 0.8), 0 0 6px rgba(255, 255, 255, 0.4);
    transform: scale(1.2);
}

.short-quality-selector {
    position: absolute;
    top: 1rem;
    right: 4rem;
    z-index: 150;
}

.quality-btn {
    background: rgba(0, 0, 0, 0.75);
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 24px;
    color: white;
    padding: 0.625rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.quality-btn:hover {
    background: rgba(0, 0, 0, 0.9);
    border-color: rgba(255, 255, 255, 0.4);
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
}

.quality-menu {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 0.5rem;
    background: rgba(0, 0, 0, 0.95);
    border-radius: 12px;
    padding: 0.5rem;
    min-width: 140px;
    display: none;
    backdrop-filter: blur(15px);
    border: 2px solid rgba(255, 255, 255, 0.15);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.5);
}

.quality-option {
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

.quality-option:hover {
    background: rgba(255, 255, 255, 0.1);
}

.quality-option.active {
    background: rgba(24, 135, 129, 0.3);
    color: var(--primary-color);
    font-weight: 700;
    border-left: 3px solid var(--primary-color);
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100vh;
    color: white;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state p {
    font-size: 1.25rem;
    opacity: 0.7;
}

@media (max-width: 768px) {
    .shorts-fullscreen-container {
        height: 100vh;
        height: 100dvh;
        overflow: hidden;
        position: fixed;
    }
    
    .shorts-close-btn {
        top: calc(env(safe-area-inset-top, 0.75rem) + 0.5rem);
        left: calc(env(safe-area-inset-left, 0.75rem) + 0.5rem);
        width: 48px;
        height: 48px;
        font-size: 1.5rem;
        background: rgba(0, 0, 0, 0.8);
        border-width: 2px;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.4);
    }
    
    .shorts-close-btn:active {
        transform: scale(0.9);
    }
    
    .shorts-viewer {
        height: 100vh;
        height: 100dvh;
        overflow-y: scroll;
        overflow-x: hidden;
        -webkit-overflow-scrolling: touch;
    }
    
    .short-video-container {
        height: 100vh;
        height: 100dvh;
        min-height: 100vh;
        min-height: 100dvh;
        max-height: 100vh;
        max-height: 100dvh;
        scroll-snap-align: start;
        scroll-snap-stop: always;
    }
    
    .short-video-info {
        padding: 1.25rem;
        padding-bottom: calc(1.5rem + env(safe-area-inset-bottom, 0));
        min-height: 140px;
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .short-video-info-left {
        width: 100%;
        max-width: calc(100% - 80px);
    }
    
    .short-channel-info {
        gap: 0.5rem;
        margin-bottom: 0.75rem;
    }
    
    .short-channel-avatar {
        width: 48px;
        height: 48px;
        font-size: 1.375rem;
        border-width: 3px;
    }
    
    .short-channel-name {
        font-size: 1rem;
    }
    
    .short-video-title {
        font-size: 0.9375rem;
        max-width: 100%;
        line-height: 1.6;
        margin-top: 0.75rem;
    }
    
    .short-video-actions {
        position: absolute;
        bottom: calc(1.75rem + env(safe-area-inset-bottom, 0));
        right: 1rem;
        flex-direction: row;
        gap: 1rem;
        margin-left: 0;
        margin-top: 0;
    }
    
    .short-action-btn {
        font-size: 1.5rem;
        gap: 0.3rem;
        padding: 0.5rem;
        min-width: 52px;
        min-height: 52px;
        width: 52px;
        height: 52px;
        justify-content: center;
    }
    
    .short-action-btn span {
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .shorts-navigation {
        right: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        gap: 0.5rem;
        padding: 0.75rem 0.5rem;
        background: rgba(0, 0, 0, 0.5);
        max-height: 70vh;
    }
    
    .nav-dot {
        width: 6px;
        height: 6px;
        min-width: 6px;
        min-height: 6px;
        flex-shrink: 0;
    }
    
    .nav-dot.active {
        width: 8px;
        height: 8px;
    }
    
    .short-quality-selector {
        top: calc(env(safe-area-inset-top, 0.75rem) + 50px);
        right: 1rem;
    }
    
    .quality-btn {
        padding: 0.625rem 1rem;
        font-size: 0.8125rem;
        min-height: 44px;
    }
    
    .quality-menu {
        min-width: 140px;
        padding: 0.75rem;
    }
    
    .quality-option {
        padding: 0.875rem 1rem;
        font-size: 0.875rem;
        min-height: 44px;
    }
    
    .short-play-overlay {
        width: 100px;
        height: 100px;
    }
    
    .short-play-overlay i {
        font-size: 4rem;
    }
    
    .empty-state {
        padding: 2rem 1rem;
    }
    
    .empty-state p {
        font-size: 1rem;
    }
}

@media (max-width: 480px) {
    .shorts-close-btn {
        top: calc(env(safe-area-inset-top, 0.5rem) + 0.5rem);
        left: calc(env(safe-area-inset-left, 0.5rem) + 0.5rem);
        width: 44px;
        height: 44px;
        font-size: 1.375rem;
        border-width: 2px;
    }
    
    .short-video-info {
        padding: 1rem;
        padding-bottom: calc(1.25rem + env(safe-area-inset-bottom, 0));
        min-height: 120px;
    }
    
    .short-channel-avatar {
        width: 40px;
        height: 40px;
        font-size: 1.125rem;
    }
    
    .short-channel-name {
        font-size: 0.9375rem;
    }
    
    .short-video-title {
        font-size: 0.875rem;
    }
    
    .short-video-actions {
        bottom: calc(1.25rem + env(safe-area-inset-bottom, 0));
        right: 0.75rem;
        gap: 1.25rem;
    }
    
    .short-action-btn {
        font-size: 1.5rem;
        gap: 0.3rem;
        min-width: 44px;
        min-height: 44px;
    }
    
    .short-action-btn span {
        font-size: 0.6875rem;
    }
    
    .shorts-navigation {
        right: 0.5rem;
        gap: 0.4rem;
        padding: 0.5rem 0.375rem;
        max-height: 60vh;
    }
    
    .nav-dot {
        width: 5px;
        height: 5px;
        flex-shrink: 0;
    }
    
    .nav-dot.active {
        width: 7px;
        height: 7px;
    }
    
    .short-quality-selector {
        top: calc(env(safe-area-inset-top, 0.5rem) + 45px);
        right: 0.75rem;
    }
    
    .quality-btn {
        padding: 0.5rem 0.875rem;
        font-size: 0.75rem;
    }
    
    .short-play-overlay {
        width: 90px;
        height: 90px;
    }
    
    .short-play-overlay i {
        font-size: 3.5rem;
    }
}

/* Landscape mobile optimization */
@media (max-width: 768px) and (orientation: landscape) {
    .short-video-info {
        padding: 0.75rem;
        padding-bottom: calc(1rem + env(safe-area-inset-bottom, 0));
        min-height: 100px;
    }
    
    .short-video-actions {
        bottom: calc(1rem + env(safe-area-inset-bottom, 0));
        gap: 1rem;
    }
    
    .short-action-btn {
        font-size: 1.5rem;
    }
    
    .short-video-title {
        font-size: 0.8125rem;
    }
    
    .short-channel-name {
        font-size: 0.875rem;
    }
}
</style>
@endpush

@push('scripts')
<!-- HLS.js Library -->
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>

<script>
// YouTube Shorts / TikTok Style Player with HLS support
let currentVideoIndex = 0;
let videos = [];
let hlsInstances = [];
let isScrolling = false;

// Initialize videos
document.addEventListener('DOMContentLoaded', function() {
    videos = Array.from(document.querySelectorAll('.short-video'));
    
    // Ensure containers have correct height
    const containers = document.querySelectorAll('.short-video-container');
    const viewportHeight = window.innerHeight;
    containers.forEach(container => {
        container.style.height = viewportHeight + 'px';
    });
    
    // Update height on resize
    window.addEventListener('resize', function() {
        const newHeight = window.innerHeight;
        containers.forEach(container => {
            container.style.height = newHeight + 'px';
        });
    });
    
    // Load first video
    if (videos.length > 0) {
        loadVideo(0);
    }
    
    // Setup scroll detection
    const viewer = document.getElementById('shortsViewer');
    if (viewer) {
        // Use throttled scroll handler for better performance
        let scrollTimeout;
        viewer.addEventListener('scroll', function() {
            if (scrollTimeout) {
                clearTimeout(scrollTimeout);
            }
            scrollTimeout = setTimeout(handleScroll, 100);
        }, { passive: true });
        
        viewer.addEventListener('touchstart', handleTouchStart, { passive: true });
        viewer.addEventListener('touchmove', handleTouchMove, { passive: true });
        viewer.addEventListener('touchend', handleTouchEnd, { passive: true });
        
        // Initial scroll check
        setTimeout(handleScroll, 500);
    }
});

// Load video source and play (with HLS support)
function loadVideo(index) {
    if (index < 0 || index >= videos.length) return;
    
    const video = videos[index];
    const hlsUrl = video.getAttribute('data-hls');
    const regularSrc = video.getAttribute('data-src');
    
    // Destroy previous HLS instance if exists
    if (hlsInstances[index]) {
        hlsInstances[index].destroy();
        hlsInstances[index] = null;
    }
    
    // Use HLS if available, otherwise use regular video
    if (hlsUrl && Hls.isSupported()) {
        // Use HLS.js for adaptive streaming
        const hls = new Hls({
            enableWorker: true,
            lowLatencyMode: true,
            backBufferLength: 90,
            maxBufferLength: 30,
            maxMaxBufferLength: 60,
            startLevel: -1, // Auto quality
            capLevelToPlayerSize: true
        });
        
        hls.loadSource(hlsUrl);
        hls.attachMedia(video);
        
        hls.on(Hls.Events.MANIFEST_PARSED, function() {
            video.play().catch(() => {});
        });
        
        // Handle errors
        hls.on(Hls.Events.ERROR, function(event, data) {
            if (data.fatal) {
                switch(data.type) {
                    case Hls.ErrorTypes.NETWORK_ERROR:
                        console.log('HLS Network Error, trying to recover...');
                        hls.startLoad();
                        break;
                    case Hls.ErrorTypes.MEDIA_ERROR:
                        console.log('HLS Media Error, trying to recover...');
                        hls.recoverMediaError();
                        break;
                    default:
                        console.log('HLS Fatal Error, destroying instance');
                        hls.destroy();
                        // Fallback to regular video
                        if (regularSrc) {
                            video.src = regularSrc;
                            video.load();
                            video.play().catch(() => {});
                        }
                        break;
                }
            }
        });
        
        hlsInstances[index] = hls;
    } else if (hlsUrl && video.canPlayType('application/vnd.apple.mpegurl')) {
        // Native HLS support (Safari)
        video.src = hlsUrl;
        video.play().catch(() => {});
    } else if (regularSrc && !video.src) {
        // Fallback to regular video
        video.src = regularSrc;
        video.load();
    }
    
    // Play current video, pause others
    videos.forEach((v, i) => {
        if (i === index) {
            v.play().catch(() => {});
            v.muted = false; // Unmute current video
        } else {
            v.pause();
            v.muted = true; // Mute other videos
            // Destroy HLS instances for other videos
            if (hlsInstances[i]) {
                hlsInstances[i].destroy();
                hlsInstances[i] = null;
            }
        }
    });
    
    // Update navigation dots
    updateNavigationDots(index);
    currentVideoIndex = index;
}

// Handle scroll to detect which video is in view
function handleScroll() {
    if (isScrolling) return;
    
    const viewer = document.getElementById('shortsViewer');
    if (!viewer) return;
    
    const containers = Array.from(document.querySelectorAll('.short-video-container'));
    const viewportHeight = window.innerHeight;
    const scrollTop = viewer.scrollTop;
    
    // Find the container that is most centered in viewport
    let closestIndex = -1;
    let closestDistance = Infinity;
    
    containers.forEach((container, index) => {
        const rect = container.getBoundingClientRect();
        const containerTop = rect.top;
        const containerBottom = rect.bottom;
        const containerCenter = containerTop + (rect.height / 2);
        const viewportCenter = viewportHeight / 2;
        
        // Check if container is in viewport
        if (containerTop < viewportHeight && containerBottom > 0) {
            const distance = Math.abs(containerCenter - viewportCenter);
            if (distance < closestDistance) {
                closestDistance = distance;
                closestIndex = index;
            }
        }
    });
    
    // Only load video if we found a valid container and it's different from current
    if (closestIndex !== -1 && closestIndex !== currentVideoIndex) {
        loadVideo(closestIndex);
    }
}

// Touch handling for mobile swipe
let touchStartY = 0;
let touchEndY = 0;
let touchStartTime = 0;
let isScrollingManually = false;

function handleTouchStart(e) {
    touchStartY = e.touches[0].clientY;
    touchStartTime = Date.now();
    isScrollingManually = false;
}

function handleTouchMove(e) {
    touchEndY = e.touches[0].clientY;
    const diff = Math.abs(touchStartY - touchEndY);
    
    // If user is scrolling significantly, mark as manual scroll
    if (diff > 10) {
        isScrollingManually = true;
    }
}

function handleTouchEnd() {
    const diff = touchStartY - touchEndY;
    const threshold = 50;
    const timeDiff = Date.now() - touchStartTime;
    
    // Only trigger swipe if it was quick and not a manual scroll
    if (!isScrollingManually && timeDiff < 300 && Math.abs(diff) > threshold) {
        if (diff > 0 && currentVideoIndex < videos.length - 1) {
            // Swipe up - next video
            scrollToShort(currentVideoIndex + 1);
        } else if (diff < 0 && currentVideoIndex > 0) {
            // Swipe down - previous video
            scrollToShort(currentVideoIndex - 1);
        }
    }
}

// Scroll to specific short
function scrollToShort(index) {
    if (index < 0 || index >= videos.length) return;
    
    isScrolling = true;
    const container = document.querySelector(`.short-video-container[data-index="${index}"]`);
    const viewer = document.getElementById('shortsViewer');
    
    if (container && viewer) {
        // Use scrollIntoView for better snap behavior
        container.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'start',
            inline: 'nearest'
        });
        loadVideo(index);
    }
    
    setTimeout(() => {
        isScrolling = false;
    }, 800);
}

// Toggle play/pause
function togglePlayPause(overlay) {
    const container = overlay.closest('.short-video-container');
    const video = container.querySelector('.short-video');
    
    if (video.paused) {
        video.play();
        container.classList.remove('paused');
    } else {
        video.pause();
        container.classList.add('paused');
    }
}

// Update navigation dots
function updateNavigationDots(activeIndex) {
    const dots = document.querySelectorAll('.nav-dot');
    const navigation = document.querySelector('.shorts-navigation');
    
    dots.forEach((dot, index) => {
        if (index === activeIndex) {
            dot.classList.add('active');
            // Scroll active dot into view if needed
            if (navigation) {
                const dotRect = dot.getBoundingClientRect();
                const navRect = navigation.getBoundingClientRect();
                if (dotRect.top < navRect.top || dotRect.bottom > navRect.bottom) {
                    dot.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
                }
            }
        } else {
            dot.classList.remove('active');
        }
    });
}

// Action buttons (placeholder functions)
function toggleLike(btn) {
    const icon = btn.querySelector('i');
    const span = btn.querySelector('span');
    const isLiked = icon.classList.contains('bi-heart-fill') || btn.classList.contains('liked');
    
    if (isLiked) {
        icon.classList.remove('bi-heart-fill');
        icon.classList.add('bi-heart');
        btn.classList.remove('liked');
        span.textContent = parseInt(span.textContent) - 1;
    } else {
        icon.classList.remove('bi-heart');
        icon.classList.add('bi-heart-fill');
        btn.classList.add('liked');
        span.textContent = parseInt(span.textContent) + 1;
    }
}

function toggleComment(btn) {
    // Placeholder for comments functionality
    alert('ميزة التعليقات قريباً');
}

function shareShort(btn) {
    const currentVideo = videos[currentVideoIndex];
    const videoId = currentVideo.getAttribute('data-id');
    const url = window.location.origin + '/video/' + videoId;
    
    if (navigator.share) {
        navigator.share({
            title: 'شاهد هذا الفيديو',
            url: url
        });
    } else {
        navigator.clipboard.writeText(url).then(() => {
            alert('تم نسخ الرابط');
        });
    }
}

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowDown' && currentVideoIndex < videos.length - 1) {
        scrollToShort(currentVideoIndex + 1);
    } else if (e.key === 'ArrowUp' && currentVideoIndex > 0) {
        scrollToShort(currentVideoIndex - 1);
    } else if (e.key === ' ') {
        e.preventDefault();
        const currentVideo = videos[currentVideoIndex];
        const container = currentVideo.closest('.short-video-container');
        const overlay = container.querySelector('.short-play-overlay');
        togglePlayPause(overlay);
    } else if (e.key === 'Escape') {
        closeShorts();
    }
});

// Change quality function
function changeQuality(btn, resolution) {
    const playlistUrl = btn.getAttribute('data-playlist');
    const currentVideo = videos[currentVideoIndex];
    const hlsInstance = hlsInstances[currentVideoIndex];
    
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
            } else {
                // If level not found, reload with specific playlist
                hlsInstance.destroy();
                const newHls = new Hls({
                    enableWorker: true,
                    lowLatencyMode: true,
                    backBufferLength: 90
                });
                newHls.loadSource(playlistUrl);
                newHls.attachMedia(currentVideo);
                newHls.on(Hls.Events.MANIFEST_PARSED, function() {
                    currentVideo.play().catch(() => {});
                });
                hlsInstances[currentVideoIndex] = newHls;
            }
        }
    } else if (playlistUrl && currentVideo.canPlayType('application/vnd.apple.mpegurl')) {
        // Native HLS support (Safari) - load specific playlist
        currentVideo.src = playlistUrl;
        currentVideo.load();
        currentVideo.play().catch(() => {});
    }
    
    // Update quality menu
    const container = currentVideo.closest('.short-video-container');
    if (container) {
        container.querySelectorAll('.quality-option').forEach(opt => {
            opt.classList.remove('active');
        });
        btn.classList.add('active');
    }
    
    // Close quality menu
    const menu = btn.closest('.quality-menu');
    if (menu) {
        menu.style.display = 'none';
    }
}

// Toggle quality menu
function toggleQualityMenu(btn) {
    const menu = btn.nextElementSibling;
    if (menu) {
        const isOpen = menu.style.display === 'block';
        // Close all menus first
        document.querySelectorAll('.quality-menu').forEach(m => m.style.display = 'none');
        // Toggle current menu
        menu.style.display = isOpen ? 'none' : 'block';
    }
}

// Close quality menu when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.short-quality-selector')) {
        document.querySelectorAll('.quality-menu').forEach(menu => {
            menu.style.display = 'none';
        });
    }
});

// Close Shorts function
function closeShorts() {
    // Pause all videos and destroy HLS instances
    videos.forEach((video, index) => {
        video.pause();
        if (hlsInstances[index]) {
            hlsInstances[index].destroy();
            hlsInstances[index] = null;
        }
    });
    
    // Fade out animation
    const container = document.getElementById('shortsContainer');
    if (container) {
        container.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => {
            window.location.href = '{{ route("home") }}';
        }, 300);
    } else {
        window.location.href = '{{ route("home") }}';
    }
}

// Add fadeOut animation
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeOut {
        from {
            opacity: 1;
        }
        to {
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
</script>
@endpush
