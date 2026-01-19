@extends('layouts.public')

@section('title', 'المناجاة - منصة المحتوى الرقمي')

@section('content')
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
                        @if($asset->speaker_name)
                        <div class="video-channel-avatar">
                            <div class="avatar-circle">
                                {{ mb_substr($asset->speaker_name, 0, 1) }}
                            </div>
                        </div>
                        @endif
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
@endsection

@push('styles')
<style>
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

.avatar-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
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
    
    .avatar-circle {
        width: 32px;
        height: 32px;
        font-size: 0.75rem;
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
