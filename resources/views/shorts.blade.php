@extends('layouts.public')

@section('title', 'Shorts - المناجاة')

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
            <i class="bi bi-camera-reels"></i>
            <p>لا توجد Shorts متاحة</p>
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
    top: 1rem;
    left: 1rem;
    width: 40px;
    height: 40px;
    background: rgba(0, 0, 0, 0.6);
    border: none;
    border-radius: 50%;
    color: white;
    font-size: 1.25rem;
    cursor: pointer;
    z-index: 2000;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
    backdrop-filter: blur(10px);
}

.shorts-close-btn:hover {
    background: rgba(0, 0, 0, 0.8);
    transform: scale(1.1);
}

.shorts-viewer {
    width: 100%;
    height: 100%;
    overflow-y: scroll;
    scroll-snap-type: y mandatory;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
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
    position: relative;
    scroll-snap-align: start;
    scroll-snap-stop: always;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #000;
}

.short-video {
    width: 100%;
    height: 100%;
    object-fit: contain;
    background-color: #000;
}

.short-video-info {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 1.5rem;
    padding-bottom: 2rem;
    background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.4) 50%, transparent 100%);
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    z-index: 10;
    min-height: 120px;
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
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1.25rem;
    border: 2px solid rgba(255, 255, 255, 0.3);
    flex-shrink: 0;
    cursor: pointer;
    transition: transform 0.2s;
}

.short-channel-avatar:hover {
    transform: scale(1.1);
}

.short-channel-name {
    font-weight: 600;
    font-size: 0.9375rem;
    cursor: pointer;
    transition: opacity 0.2s;
    text-shadow: 0 1px 3px rgba(0,0,0,0.5);
}

.short-channel-name:hover {
    opacity: 0.8;
}

.short-video-title {
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.95);
    margin-top: 0.5rem;
    line-height: 1.5;
    max-width: 85%;
    text-shadow: 0 1px 3px rgba(0,0,0,0.5);
}

.short-video-actions {
    display: flex;
    flex-direction: column;
    gap: 1.75rem;
    align-items: center;
    margin-left: 1rem;
}

.short-action-btn {
    background: transparent;
    border: none;
    color: white;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.35rem;
    font-size: 1.75rem;
    transition: all 0.2s;
    padding: 0.25rem;
}

.short-action-btn:hover {
    transform: scale(1.15);
}

.short-action-btn:active {
    transform: scale(0.95);
}

.short-action-btn i {
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
}

.short-action-btn span {
    font-size: 0.75rem;
    font-weight: 600;
    text-shadow: 0 1px 2px rgba(0,0,0,0.5);
}

.short-play-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 90px;
    height: 90px;
    background: rgba(0, 0, 0, 0.6);
    border-radius: 50%;
    display: none;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 20;
    transition: all 0.3s;
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.2);
}

.short-video-container.paused .short-play-overlay {
    display: flex;
}

.short-play-overlay:hover {
    background: rgba(0, 0, 0, 0.7);
    transform: translate(-50%, -50%) scale(1.1);
}

.short-play-overlay i {
    font-size: 3.5rem;
    color: white;
    margin-left: 5px; /* RTL adjustment */
    filter: drop-shadow(0 2px 8px rgba(0,0,0,0.5));
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
    padding: 0.5rem;
    background: rgba(0, 0, 0, 0.3);
    border-radius: 20px;
    backdrop-filter: blur(10px);
}

.nav-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.4);
    cursor: pointer;
    transition: all 0.3s;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.nav-dot:hover {
    background: rgba(255, 255, 255, 0.7);
    transform: scale(1.2);
}

.nav-dot.active {
    background: white;
    width: 8px;
    height: 8px;
    box-shadow: 0 0 8px rgba(255, 255, 255, 0.6);
}

.short-quality-selector {
    position: absolute;
    top: 1rem;
    right: 4rem;
    z-index: 150;
}

.quality-btn {
    background: rgba(0, 0, 0, 0.6);
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

.quality-btn:hover {
    background: rgba(0, 0, 0, 0.8);
    transform: scale(1.05);
}

.quality-menu {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 0.5rem;
    background: rgba(0, 0, 0, 0.9);
    border-radius: 8px;
    padding: 0.5rem;
    min-width: 120px;
    display: none;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
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
    background: rgba(255, 255, 255, 0.2);
    color: #4CAF50;
    font-weight: 600;
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
    .shorts-close-btn {
        top: 0.75rem;
        left: 0.75rem;
        width: 36px;
        height: 36px;
        font-size: 1.125rem;
    }
    
    .short-video-info {
        padding: 1rem;
        padding-bottom: 1.5rem;
        min-height: 100px;
    }
    
    .short-channel-avatar {
        width: 40px;
        height: 40px;
        font-size: 1.125rem;
    }
    
    .short-action-btn {
        font-size: 1.5rem;
        gap: 0.3rem;
    }
    
    .short-action-btn span {
        font-size: 0.6875rem;
    }
    
    .shorts-navigation {
        right: 0.5rem;
        gap: 0.5rem;
    }
    
    .short-video-title {
        font-size: 0.8125rem;
        max-width: 80%;
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
    
    // Load first video
    if (videos.length > 0) {
        loadVideo(0);
    }
    
    // Setup scroll detection
    const viewer = document.getElementById('shortsViewer');
    if (viewer) {
        viewer.addEventListener('scroll', handleScroll);
        viewer.addEventListener('touchstart', handleTouchStart, { passive: true });
        viewer.addEventListener('touchmove', handleTouchMove, { passive: true });
        viewer.addEventListener('touchend', handleTouchEnd, { passive: true });
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
    const containers = Array.from(document.querySelectorAll('.short-video-container'));
    
    containers.forEach((container, index) => {
        const rect = container.getBoundingClientRect();
        const isVisible = rect.top >= 0 && rect.top < window.innerHeight / 2;
        
        if (isVisible && index !== currentVideoIndex) {
            loadVideo(index);
        }
    });
}

// Touch handling for mobile swipe
let touchStartY = 0;
let touchEndY = 0;

function handleTouchStart(e) {
    touchStartY = e.touches[0].clientY;
}

function handleTouchMove(e) {
    touchEndY = e.touches[0].clientY;
}

function handleTouchEnd() {
    const diff = touchStartY - touchEndY;
    const threshold = 50;
    
    if (Math.abs(diff) > threshold) {
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
    if (container) {
        container.scrollIntoView({ behavior: 'smooth', block: 'start' });
        loadVideo(index);
    }
    
    setTimeout(() => {
        isScrolling = false;
    }, 500);
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
    document.querySelectorAll('.nav-dot').forEach((dot, index) => {
        if (index === activeIndex) {
            dot.classList.add('active');
        } else {
            dot.classList.remove('active');
        }
    });
}

// Action buttons (placeholder functions)
function toggleLike(btn) {
    const icon = btn.querySelector('i');
    const span = btn.querySelector('span');
    const isLiked = icon.classList.contains('bi-heart-fill');
    
    if (isLiked) {
        icon.classList.remove('bi-heart-fill');
        icon.classList.add('bi-heart');
        span.textContent = parseInt(span.textContent) - 1;
    } else {
        icon.classList.remove('bi-heart');
        icon.classList.add('bi-heart-fill');
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
