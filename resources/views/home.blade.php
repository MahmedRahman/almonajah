@extends('layouts.public')

@section('title', 'المناجاة - منصة المحتوى الرقمي')

@section('content')
<div class="home-layout">
    <!-- Sidebar -->
    <aside class="sidebar-menu" id="sidebarMenu">
        <div class="sidebar-content">
            <nav class="sidebar-nav">
                <!-- Main Navigation -->
                <a href="{{ route('home') }}" class="sidebar-item {{ request()->routeIs('home') && !request('content_category') ? 'active' : '' }}">
                    <i class="bi bi-house-door"></i>
                    <span class="sidebar-item-text">الرئيسية</span>
                </a>
                <a href="{{ route('shorts') }}" class="sidebar-item {{ request()->routeIs('shorts') ? 'active' : '' }}">
                    <i class="bi bi-play-circle"></i>
                    <span class="sidebar-item-text">فيديوهات قصيرة</span>
                </a>
                
                <!-- Divider -->
                @if(isset($contentCategories) && $contentCategories->count() > 0)
                <div class="sidebar-divider"></div>
                
                <!-- Categories Section -->
                <div class="sidebar-section-header">
                    <h3 class="sidebar-section-title">استكشاف</h3>
                </div>
                @foreach($contentCategories as $category)
                <a href="{{ route('home', ['content_category' => $category]) }}" 
                   class="sidebar-item {{ request('content_category') == $category ? 'active' : '' }}">
                    <i class="bi bi-tag"></i>
                    <span class="sidebar-item-text">{{ $category }}</span>
                </a>
                @endforeach
                @endif
                
                <!-- User Section (if authenticated) -->
                @auth
                <div class="sidebar-divider"></div>
                
                <div class="sidebar-section-header">
                    <h3 class="sidebar-section-title">حسابي</h3>
                </div>
                <a href="{{ route('profile') }}" class="sidebar-item {{ request()->routeIs('profile') ? 'active' : '' }}">
                    <i class="bi bi-person-circle"></i>
                    <span class="sidebar-item-text">ملف الشخصي</span>
                </a>
                @if(auth()->user()->isAdmin())
                <a href="{{ route('dashboard') }}" class="sidebar-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i>
                    <span class="sidebar-item-text">لوحة التحكم</span>
                </a>
                @endif
                <a href="{{ route('favorites') }}" class="sidebar-item {{ request()->routeIs('favorites') ? 'active' : '' }}">
                    <i class="bi bi-bookmark-heart"></i>
                    <span class="sidebar-item-text">المفضلة</span>
                </a>
                <a href="#" class="sidebar-item" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="bi bi-box-arrow-left"></i>
                    <span class="sidebar-item-text">تسجيل الخروج</span>
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
                @endauth
            </nav>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content-wrapper">
        <div class="container-main">
            <!-- Shorts Section -->
    @if(isset($shortsQuery) && $shortsQuery->count() > 0)
    <div class="shorts-section">
        <div class="shorts-header">
            <h2 class="shorts-title">
                فيديوهات قصيرة
            </h2>
        </div>
        <div class="shorts-container">
            <div class="shorts-scroll">
                @foreach($shortsQuery as $short)
                <a href="{{ route('assets.show.public', $short) }}" class="short-card">
                    <div class="short-thumbnail">
                        @if($short->thumbnail_path)
                            <img src="{{ asset('storage/' . $short->thumbnail_path) }}" 
                                 alt="{{ $short->title ?: $short->file_name }}" 
                                 loading="lazy"
                                 width="180"
                                 height="320"
                                 decoding="async">
                        @elseif($short->relative_path && strpos($short->relative_path, 'assets/') === 0 && in_array(strtolower($short->extension), ['mp4', 'mov', 'mkv', 'm4v', 'webm', 'avi']))
                            <video muted preload="none" data-src="{{ asset('storage/' . $short->relative_path) }}#t=1" width="180" height="320">
                            </video>
                        @else
                            <div class="short-thumbnail-placeholder">
                            </div>
                        @endif
                        
                        @if($short->computed_duration)
                            <span class="short-duration">{{ $short->computed_duration }}</span>
                        @endif
                    </div>
                    <div class="short-info">
                        <h3 class="short-title">{{ \Illuminate\Support\Str::limit($short->title ?: $short->file_name, 50) }}</h3>
                        @if($short->speaker_name)
                            <span class="short-channel">{{ $short->speaker_name }}</span>
                        @endif
                    </div>
                </a>
                @endforeach
            </div>
        </div>
    </div>
    @endif
    
    <!-- Videos Grid -->
    @if($assets->count() > 0)
        <div class="video-grid">
            @foreach($assets as $asset)
            <a href="{{ route('assets.show.public', $asset) }}" class="video-card">
                <div class="video-thumbnail">
                    @if($asset->thumbnail_path)
                        <img src="{{ asset('storage/' . $asset->thumbnail_path) }}" 
                             alt="{{ $asset->title ?: $asset->file_name }}" 
                             loading="lazy"
                             width="320"
                             height="180"
                             decoding="async">
                    @elseif($asset->relative_path && strpos($asset->relative_path, 'assets/') === 0 && in_array(strtolower($asset->extension), ['mp4', 'mov', 'mkv', 'm4v', 'webm', 'avi']))
                        <video muted preload="none" data-src="{{ asset('storage/' . $asset->relative_path) }}#t=1" width="320" height="180">
                        </video>
                    @else
                        <div class="video-thumbnail-placeholder">
                        </div>
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
                                @if($asset->content_category)
                                    <span class="video-category">{{ $asset->content_category }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </a>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="pagination">
            {{ $assets->appends(request()->query())->links('pagination::bootstrap-5') }}
        </div>
    @else
        <div class="empty-state">
            <p>لا توجد فيديوهات متاحة</p>
        </div>
    @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Home Layout */
.home-layout {
    display: flex;
    position: relative;
    min-height: calc(100vh - 200px);
    margin-top: 0;
}

/* Sidebar Menu */
.sidebar-menu {
    position: relative;
    width: 260px;
    min-height: calc(100vh - 60px);
    background-color: var(--bg-primary);
    border-left: 1px solid var(--border-color);
    box-shadow: var(--shadow-sm);
    z-index: 1;
    transition: width 0.3s ease, opacity 0.3s ease;
    overflow-y: auto;
    overflow-x: hidden;
    flex-shrink: 0;
}

.sidebar-menu.collapsed {
    width: 0;
    opacity: 0;
    overflow: hidden;
    border: none;
}

.sidebar-content {
    padding: var(--spacing-md);
}

.sidebar-header {
    padding-bottom: var(--spacing-md);
    border-bottom: 1px solid var(--border-color);
    margin-bottom: var(--spacing-md);
}

.sidebar-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
}

.sidebar-nav {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.sidebar-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    padding: 0.75rem var(--spacing-sm);
    border-radius: var(--radius-sm);
    text-decoration: none;
    color: var(--text-primary);
    transition: all 0.2s ease;
    font-size: 0.9375rem;
    font-weight: 500;
}

.sidebar-item:hover {
    background-color: var(--bg-tertiary);
    color: var(--primary-color);
}

.sidebar-item.active {
    background-color: rgba(24, 135, 129, 0.1);
    color: var(--primary-color);
    font-weight: 600;
}

.sidebar-item i {
    font-size: 1.25rem;
    width: 24px;
    text-align: center;
    flex-shrink: 0;
}

.sidebar-item-text {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar-divider {
    height: 1px;
    background-color: var(--border-color);
    margin: var(--spacing-sm) 0;
}

.sidebar-section-header {
    padding: var(--spacing-sm) var(--spacing-sm) var(--spacing-xs);
    margin-top: var(--spacing-xs);
}

.sidebar-section-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0;
}

/* Main Content Wrapper */
.main-content-wrapper {
    flex: 1;
    margin-right: 0;
    transition: margin-right 0.3s ease;
    width: 100%;
    min-width: 0;
}

/* Container Main */
.container-main {
    max-width: 1400px;
    margin: 0 auto;
    padding: var(--spacing-lg) var(--spacing-md);
}

/* Responsive Design */
@media (max-width: 1024px) {
    .sidebar-menu {
        position: fixed;
        top: 60px;
        right: 0;
        height: calc(100vh - 60px);
        box-shadow: var(--shadow-lg);
        z-index: 1000;
        transform: translateX(100%);
    }
    
    .sidebar-menu:not(.collapsed) {
        transform: translateX(0);
        width: 260px;
        opacity: 1;
    }
    
    .sidebar-menu.collapsed {
        transform: translateX(100%);
        width: 260px;
    }
}

@media (max-width: 768px) {
    .sidebar-menu {
        width: 240px;
        top: 56px;
        height: calc(100vh - 56px);
    }
    
    .sidebar-menu:not(.collapsed) {
        width: 240px;
    }
    
    .sidebar-item {
        padding: 0.625rem var(--spacing-xs);
        font-size: 0.875rem;
    }
    
    .sidebar-item i {
        font-size: 1.125rem;
        width: 20px;
    }
}

/* Overlay for mobile when sidebar is open */
.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 999;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.sidebar-overlay.active {
    display: block;
    opacity: 1;
}

@media (max-width: 1024px) {
    .sidebar-overlay.active {
        display: block;
    }
}

/* Shorts Section */
.shorts-section {
    margin-bottom: var(--spacing-xl);
    padding: var(--spacing-md) 0;
}

.shorts-header {
    margin-bottom: var(--spacing-md);
    padding: 0 var(--spacing-sm);
}

.shorts-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    margin: 0;
}

.shorts-title i {
    color: #ff0000;
    font-size: 1.5rem;
}

.shorts-container {
    position: relative;
    overflow: hidden;
}

.shorts-scroll {
    display: flex;
    gap: var(--spacing-md);
    overflow-x: auto;
    overflow-y: hidden;
    padding: var(--spacing-sm);
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    scrollbar-color: var(--border-color) transparent;
}

.shorts-scroll::-webkit-scrollbar {
    height: 8px;
}

.shorts-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.shorts-scroll::-webkit-scrollbar-thumb {
    background-color: var(--border-color);
    border-radius: 4px;
}

.shorts-scroll::-webkit-scrollbar-thumb:hover {
    background-color: var(--text-secondary);
}

.short-card {
    flex: 0 0 180px;
    background-color: var(--bg-primary);
    border-radius: var(--radius-md);
    overflow: hidden;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
    box-shadow: var(--shadow-sm);
}

.short-thumbnail {
    position: relative;
    width: 100%;
    aspect-ratio: 9 / 16; /* عمودي مثل YouTube Shorts */
    background-color: var(--bg-tertiary);
    overflow: hidden;
    border-radius: var(--radius-md) var(--radius-md) 0 0;
}

.short-thumbnail video,
.short-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.short-thumbnail-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.short-thumbnail-placeholder i {
    font-size: 2.5rem;
}

.short-duration {
    position: absolute;
    bottom: 0.5rem;
    left: 0.5rem;
    background-color: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 0.125rem 0.375rem;
    border-radius: var(--radius-sm);
    font-size: 0.6875rem;
    font-weight: 600;
}

.short-info {
    padding: var(--spacing-sm);
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.short-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.4;
}

.short-channel {
    font-size: 0.75rem;
    color: var(--text-secondary);
}

@media (max-width: 768px) {
    .short-card {
        flex: 0 0 150px;
    }
    
    .shorts-title {
        font-size: 1.125rem;
    }
    
    .short-title {
        font-size: 0.8125rem;
    }
    
    .short-channel {
        font-size: 0.6875rem;
    }
}

.video-info-header {
    display: flex;
    gap: 0.75rem;
    margin-top: 0.75rem;
}

.video-channel-avatar {
    flex-shrink: 0;
}

.avatar-logo {
    width: 36px;
    height: 36px;
    object-fit: contain;
    border-radius: 50%;
    background-color: var(--bg-primary);
    padding: 4px;
}

.video-info-content {
    flex: 1;
    min-width: 0;
}

.video-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.4;
}

.video-meta {
    font-size: 0.8125rem;
    color: var(--text-secondary);
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.video-channel-name {
    display: block;
}

.video-category {
    display: block;
    color: var(--text-secondary);
}

@media (max-width: 768px) {
    .video-info-header {
        gap: 0.5rem;
    }
    
    .avatar-logo {
        width: 32px;
        height: 32px;
    }
    
    .video-title {
        font-size: 0.875rem;
    }
    
    .video-meta {
        font-size: 0.75rem;
    }
}
</style>
@endpush

@push('scripts')
<script>
// Lazy load videos when they come into view
const videoObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const video = entry.target;
            const src = video.getAttribute('data-src');
            if (src && !video.src) {
                video.src = src;
                video.load();
            }
            videoObserver.unobserve(video);
        }
    });
}, { rootMargin: '50px' });

// Observe all videos with data-src
document.querySelectorAll('video[data-src]').forEach(video => {
    videoObserver.observe(video);
});

</script>
@endpush
