@extends('layouts.public')

@section('title', $playlist->title . ' - المناجاة')

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
                <a href="{{ route('public.playlists') }}" class="sidebar-item {{ request()->routeIs('public.playlists') || request()->routeIs('public.playlist.show') ? 'active' : '' }}">
                    <i class="bi bi-music-note-list"></i>
                    <span class="sidebar-item-text">قوائم التشغيل</span>
                </a>
                <a href="{{ route('public.scholars') }}" class="sidebar-item {{ request()->routeIs('public.scholars') || request()->routeIs('public.scholar.show') ? 'active' : '' }}">
                    <i class="bi bi-person-badge"></i>
                    <span class="sidebar-item-text">الشيوخ</span>
                </a>
                <a href="{{ route('live') }}" class="sidebar-item {{ request()->routeIs('live') ? 'active' : '' }}">
                    <i class="bi bi-broadcast"></i>
                    <span class="sidebar-item-text">بث مباشر</span>
                </a>
                
                <!-- Divider -->
                @if(isset($categories) && $categories->count() > 0)
                <div class="sidebar-divider"></div>
                
                <!-- Categories Section -->
                <div class="sidebar-section-header">
                    <h3 class="sidebar-section-title">استكشاف</h3>
                </div>
                @foreach($categories as $category)
                <a href="{{ route('home', ['content_category' => $category->name]) }}" 
                   class="sidebar-item {{ request('content_category') == $category->name ? 'active' : '' }}">
                    @if($category->image_path)
                        <img src="{{ asset('storage/' . $category->image_path) }}" 
                             alt="{{ $category->name }}" 
                             class="sidebar-category-image"
                             style="width: 24px; height: 24px; object-fit: cover; border-radius: 4px; margin-left: 8px;">
                    @else
                        <i class="bi bi-tag"></i>
                    @endif
                    <span class="sidebar-item-text">{{ $category->name }}</span>
                    <span class="sidebar-category-count">({{ $category->assets_count ?? 0 }})</span>
                </a>
                @endforeach
                @endif
                
                <!-- User Section -->
                <div class="sidebar-divider"></div>
                <div class="sidebar-section-header">
                    <h3 class="sidebar-section-title">حسابي</h3>
                </div>
                @guest
                <a href="#" class="sidebar-item" data-bs-toggle="modal" data-bs-target="#authModal" data-bs-mode="login">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span class="sidebar-item-text">تسجيل الدخول</span>
                </a>
                @endguest
                @auth
                <a href="{{ route('profile') }}" class="sidebar-item {{ request()->routeIs('profile') ? 'active' : '' }}">
                    <i class="bi bi-person-circle"></i>
                    <span class="sidebar-item-text">ملف الشخصي</span>
                </a>
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
            <!-- Playlist Header -->
            <div class="playlist-header mb-4" style="margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                    <a href="{{ route('public.playlists') }}" style="color: var(--text-secondary); text-decoration: none; font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="bi bi-arrow-right"></i> رجوع إلى قوائم التشغيل
                    </a>
                </div>
                <div style="display: flex; gap: 1.5rem; align-items: flex-start;">
                    <div class="playlist-thumbnail-large" style="flex-shrink: 0; width: 200px; height: 112px; border-radius: var(--radius-md); overflow: hidden; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        @if($playlist->image_path)
                            <img src="{{ asset('storage/' . $playlist->image_path) }}" 
                                 alt="{{ $playlist->title }}" 
                                 style="width: 100%; height: 100%; object-fit: cover;">
                        @else
                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-music-note-list text-white" style="font-size: 3rem;"></i>
                            </div>
                        @endif
                    </div>
                    <div class="playlist-info-header" style="flex: 1;">
                        <h1 class="playlist-title-header" style="font-size: 1.75rem; font-weight: 700; margin: 0 0 0.5rem 0; color: var(--text-primary);">
                            {{ $playlist->title }}
                        </h1>
                        @if($playlist->description)
                            <p class="playlist-description-header" style="color: var(--text-secondary); margin: 0 0 1rem 0; line-height: 1.6;">
                                {{ $playlist->description }}
                            </p>
                        @endif
                        <div class="playlist-meta" style="display: flex; align-items: center; gap: 1rem; color: var(--text-secondary); font-size: 0.875rem;">
                            <span><i class="bi bi-play-circle me-1"></i>{{ $assets->total() }} فيديو</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Videos Grid -->
            @if($assets->count() > 0)
                <div class="video-grid" id="playlistVideoGrid">
                    @include('partials.home-video-cards', ['assets' => $assets])
                </div>

                <!-- Pagination -->
                @if($assets->hasMorePages())
                <div class="pagination-wrapper" style="text-align: center; margin: 2rem 0;">
                    {{ $assets->links('pagination::bootstrap-5') }}
                </div>
                @else
                <p class="text-muted small text-center mt-2">عرض {{ $assets->total() }} من {{ $assets->total() }} فيديو</p>
                @endif
            @else
                <div class="empty-state">
                    <p>لا توجد فيديوهات في هذه القائمة</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* نفس الـ styles من home.blade.php */
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
            width: 240px;
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

.sidebar-category-image {
    width: 24px;
    height: 24px;
    object-fit: cover;
    border-radius: 4px;
    flex-shrink: 0;
}

.sidebar-item-text {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar-category-count {
    font-size: 0.8rem;
    color: var(--text-secondary, #6b7280);
    margin-right: 4px;
    flex-shrink: 0;
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

/* Video Grid */
.video-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: var(--spacing-lg);
    padding: var(--spacing-md) 0;
}

@media (max-width: 1200px) {
    .video-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    }
}

@media (max-width: 768px) {
    .video-grid {
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: var(--spacing-md);
    }
}

.video-card {
    display: flex;
    flex-direction: column;
    background-color: var(--bg-primary);
    border-radius: var(--radius-md);
    overflow: hidden;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    box-shadow: var(--shadow-sm);
}

.video-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-md);
}

.video-thumbnail {
    position: relative;
    width: 100%;
    aspect-ratio: 16 / 9;
    background-color: var(--bg-tertiary);
    overflow: hidden;
}

.video-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.video-duration {
    position: absolute;
    bottom: 0.5rem;
    right: 0.5rem;
    background-color: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: var(--radius-sm);
    font-size: 0.75rem;
    font-weight: 600;
}

.video-info {
    padding: var(--spacing-sm);
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

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--text-secondary);
}

.empty-state p {
    font-size: 1.1rem;
    margin: 0;
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
                width: 240px;
                opacity: 1;
    }
    
.sidebar-menu.collapsed {
                transform: translateX(100%);
                width: 240px;
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
    
    .playlist-header {
        flex-direction: column;
    }
    
    .playlist-thumbnail-large {
        width: 100% !important;
        height: auto !important;
        aspect-ratio: 16 / 9;
    }
}
</style>
@endpush

@push('scripts')
<script>
// Optimize image loading
document.querySelectorAll('img[loading="lazy"]').forEach((img, index) => {
    // Add fetchpriority for first few images
    if (index < 3) {
        img.setAttribute('fetchpriority', 'high');
    } else {
        img.setAttribute('fetchpriority', 'low');
    }
    
    // Handle image load
    if (img.complete && img.naturalHeight !== 0) {
        img.style.opacity = '1';
    } else {
        img.addEventListener('load', function() {
            this.style.opacity = '1';
        });
    }
});
</script>
@endpush
