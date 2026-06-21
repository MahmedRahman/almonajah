@extends('layouts.public')

@section('title', 'فيديوهات طولية - المناجاة')
@section('meta_description', 'تصفح جميع الفيديوهات الطولية على منصة المناجاة — محتوى إسلامي موثوق بجودة عالية.')

@section('content')
<div class="home-layout">
    @include('partials.public-sidebar')

    <div class="main-content-wrapper">
        <div class="container-main">
            <div class="page-header">
                <a href="{{ route('home') }}" class="page-back-link">
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    الرئيسية
                </a>
                <h1 class="page-title">فيديوهات طولية</h1>
                <p class="page-subtitle">جميع الفيديوهات العمودية المنشورة على المنصة</p>
            </div>

            @if($assets->count() > 0)
                <div class="video-grid portrait-videos-grid" id="portraitVideosGrid">
                    @include('partials.home-video-cards', [
                        'assets' => $assets,
                        'forceLandscape' => false,
                        'useThumbnail' => true,
                    ])
                </div>

                @if($assets->hasMorePages())
                    <div class="portrait-load-more"
                         id="portraitLoadMoreWrapper"
                         data-next-url="{{ $assets->appends(request()->query())->nextPageUrl() }}"
                         data-total="{{ $totalPortraitVideos ?? $assets->total() }}">
                        <p class="portrait-load-more__count text-muted small mb-2">
                            عرض <span id="portraitShownCount">{{ $assets->count() }}</span>
                            من {{ $totalPortraitVideos ?? $assets->total() }} فيديو
                        </p>
                        <div id="portraitLoadMoreSentinel" aria-hidden="true"></div>
                        <div id="portraitLoadMoreSpinner" class="portrait-load-more__spinner d-none">
                            <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                            <span class="ms-2 text-muted small">جاري تحميل المزيد...</span>
                        </div>
                    </div>
                @else
                    <p class="portrait-load-more__count text-muted small text-center mt-3 mb-0">
                        عرض {{ $assets->count() }} من {{ $totalPortraitVideos ?? $assets->total() }} فيديو
                    </p>
                @endif
            @else
                <div class="empty-state">
                    <p>لا توجد فيديوهات طولية متاحة</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.home-layout {
    display: flex;
    position: relative;
    min-height: calc(100vh - 200px);
    margin-top: 0;
}

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

.sidebar-content { padding: var(--spacing-md); }

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

.sidebar-item-icon-img {
    width: 24px;
    height: 24px;
    object-fit: contain;
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

.main-content-wrapper {
    flex: 1;
    width: 100%;
    min-width: 0;
}

.page-header {
    margin-bottom: var(--spacing-lg);
    padding-bottom: var(--spacing-md);
    border-bottom: 2px solid var(--border-color);
}

.page-back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    margin-bottom: 0.65rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-secondary);
    text-decoration: none;
    transition: color 0.2s ease;
}

.page-back-link:hover {
    color: var(--primary-color);
}

.page-title {
    font-size: clamp(1.4rem, 3.5vw, 1.75rem);
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 0.35rem;
}

.page-subtitle {
    font-size: 0.95rem;
    color: var(--text-secondary);
    margin: 0;
}

.portrait-videos-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: var(--spacing-md);
}

.portrait-videos-grid .video-card {
    min-width: 0;
}

.portrait-videos-grid .video-card--portrait .video-thumbnail {
    padding-bottom: 177.78%;
}

.portrait-videos-grid .video-info-header {
    display: flex;
    gap: 0.5rem;
    align-items: flex-start;
}

.portrait-videos-grid .video-channel-avatar {
    flex-shrink: 0;
}

.portrait-videos-grid .avatar-logo {
    width: 24px;
    height: 24px;
    object-fit: contain;
    border-radius: 50%;
    background-color: var(--bg-primary);
    padding: 2px;
    display: block;
}

.portrait-videos-grid .video-title {
    font-size: 0.8125rem;
    line-height: 1.35;
}

.portrait-videos-grid .video-meta {
    font-size: 0.75rem;
}

.portrait-videos-grid .video-thumbnail img[src*="logo_min"] {
    object-fit: contain;
    padding: 18%;
    background-color: var(--bg-tertiary);
}

.portrait-load-more {
    text-align: center;
    margin: 2rem 0 1rem;
    min-height: 48px;
}

.portrait-load-more__spinner {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem;
}

#portraitLoadMoreSentinel {
    height: 1px;
    visibility: hidden;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--text-secondary);
}

@media (max-width: 1400px) {
    .portrait-videos-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}

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

    .portrait-videos-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 768px) {
    .portrait-videos-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: var(--spacing-sm);
    }

    .sidebar-menu {
        top: 56px;
        height: calc(100vh - 56px);
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
</style>
@endpush

@push('scripts')
<script>
(function() {
    var grid = document.getElementById('portraitVideosGrid');
    var wrapper = document.getElementById('portraitLoadMoreWrapper');
    var sentinel = document.getElementById('portraitLoadMoreSentinel');
    var spinner = document.getElementById('portraitLoadMoreSpinner');
    var shownSpan = document.getElementById('portraitShownCount');
    if (!grid || !wrapper || !sentinel) return;

    var loading = false;

    function updateShownCount() {
        if (shownSpan) {
            shownSpan.textContent = grid.querySelectorAll('.video-card').length;
        }
    }

    function loadMore() {
        var url = wrapper.getAttribute('data-next-url');
        if (!url || loading) return;
        loading = true;
        if (spinner) spinner.classList.remove('d-none');

        fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.html) {
                var wrap = document.createElement('div');
                wrap.innerHTML = data.html.trim();
                while (wrap.firstChild) {
                    grid.appendChild(wrap.firstChild);
                }
            }
            updateShownCount();
            if (data.has_more && data.next_page_url) {
                wrapper.setAttribute('data-next-url', data.next_page_url);
            } else {
                wrapper.setAttribute('data-next-url', '');
                sentinel.style.display = 'none';
                observer.disconnect();
                if (spinner) spinner.classList.add('d-none');
            }
        })
        .catch(function() {})
        .finally(function() {
            loading = false;
            if (spinner) spinner.classList.add('d-none');
        });
    }

    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (!entry.isIntersecting) return;
            if (wrapper.getAttribute('data-next-url')) loadMore();
        });
    }, { root: null, rootMargin: '240px 0px', threshold: 0 });

    observer.observe(sentinel);
})();
</script>
@endpush
