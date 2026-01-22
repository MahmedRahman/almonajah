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
                            @if(isset($transcriptionSegments) && $transcriptionSegments && $asset->transcription)
                            <track id="captionsTrack" kind="captions" label="العربية" srclang="ar" default>
                            @endif
                            متصفحك لا يدعم تشغيل الفيديو.
                        </video>
                        
                        @if(isset($transcriptionSegments) && $transcriptionSegments && $asset->transcription)
                        <!-- Custom Captions Overlay -->
                        <div class="custom-captions-overlay" id="customCaptionsOverlay" style="display: none;">
                            <div class="captions-text-container">
                                <span id="captionsText" class="captions-text"></span>
                            </div>
                        </div>
                        
                        <!-- Captions Toggle Button -->
                        <div class="video-captions-selector">
                            <button class="captions-btn-main" id="captionsToggleBtn" onclick="toggleCaptions()" title="تفعيل/إلغاء الترجمة">
                                <i class="bi bi-subtitles"></i>
                                <span id="captionsToggleText">الترجمة</span>
                            </button>
                        </div>
                        @endif
                        
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
                
                <!-- Like and Favorite Buttons -->
                <div class="video-actions-section">
                    @php
                        $likesCount = $asset->likes()->count();
                        $favoritesCount = $asset->favorites()->count();
                    @endphp
                    @auth
                        <button class="action-btn like-btn {{ isset($userLiked) && $userLiked ? 'active' : '' }}" onclick="toggleLike({{ $asset->id }})" id="likeBtn">
                            <i class="bi {{ isset($userLiked) && $userLiked ? 'bi-heart-fill' : 'bi-heart' }}"></i>
                            <span id="likeCount">{{ $likesCount }}</span>
                        </button>
                        <button class="action-btn favorite-btn {{ isset($userFavorited) && $userFavorited ? 'active' : '' }}" onclick="toggleFavorite({{ $asset->id }})" id="favoriteBtn">
                            <i class="bi {{ isset($userFavorited) && $userFavorited ? 'bi-bookmark-fill' : 'bi-bookmark' }}"></i>
                            <span id="favoriteCount">{{ $favoritesCount }}</span>
                        </button>
                    @else
                        <button class="action-btn like-btn" onclick="showLoginModal()">
                            <i class="bi bi-heart"></i>
                            <span>{{ $likesCount }}</span>
                        </button>
                        <button class="action-btn favorite-btn" onclick="showLoginModal()">
                            <i class="bi bi-bookmark"></i>
                            <span>{{ $favoritesCount }}</span>
                        </button>
                    @endauth
                </div>
                
                <div class="video-details-meta">
                    @if($asset->speaker_name)
                        <span>
                            <i class="bi bi-person"></i>
                            {{ $asset->speaker_name }}
                        </span>
                    @endif
                    @if($asset->content_category)
                        <span>
                            <i class="bi bi-tag"></i>
                            {{ $asset->content_category }}
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

                <!-- Share Buttons -->
                <div class="video-share-section">
                    <div class="share-title">مشاركة الفيديو</div>
                    <div class="share-buttons">
                        <button class="share-btn share-facebook" onclick="shareOnFacebook()" title="مشاركة على Facebook">
                            <i class="bi bi-facebook"></i>
                            <span>Facebook</span>
                        </button>
                        <button class="share-btn share-twitter" onclick="shareOnTwitter()" title="مشاركة على X">
                            <i class="bi bi-twitter"></i>
                            <span>X</span>
                        </button>
                        <button class="share-btn share-whatsapp" onclick="shareOnWhatsApp()" title="مشاركة على WhatsApp">
                            <i class="bi bi-whatsapp"></i>
                            <span>WhatsApp</span>
                        </button>
                        <button class="share-btn share-telegram" onclick="shareOnTelegram()" title="مشاركة على Telegram">
                            <i class="bi bi-telegram"></i>
                            <span>Telegram</span>
                        </button>
                        <button class="share-btn share-copy" onclick="copyVideoLink()" title="نسخ الرابط">
                            <i class="bi bi-link-45deg"></i>
                            <span>نسخ الرابط</span>
                        </button>
                    </div>
                </div>

                @if($asset->site_description)
                <div class="video-description">
                    <div class="video-description-title">وصف الموقع</div>
                    <div class="video-description-text">{{ $asset->site_description }}</div>
                </div>
                @endif

                <!-- Comments Section -->
                <div class="comments-section">
                    <div class="comments-header">
                        <h3 class="comments-title">
                            <i class="bi bi-chat-dots"></i>
                            التعليقات
                            <span class="comments-count" id="commentsCount">0</span>
                        </h3>
                    </div>

                    @auth
                    <div class="comment-form-container">
                        <form id="commentForm" class="comment-form">
                            @csrf
                            <div class="comment-input-wrapper">
                                <textarea 
                                    id="commentContent" 
                                    name="content" 
                                    class="comment-input" 
                                    placeholder="اكتب تعليقك هنا..." 
                                    rows="3"
                                    required></textarea>
                            </div>
                            <div class="comment-form-actions">
                                <button type="submit" class="btn btn-primary comment-submit-btn">
                                    <i class="bi bi-send"></i>
                                    إرسال
                                </button>
                            </div>
                        </form>
                    </div>
                    @else
                    <div class="comment-login-prompt">
                        <p>يجب <button class="login-link-btn" onclick="showLoginModal()">تسجيل الدخول</button> لإضافة تعليق</p>
                    </div>
                    @endauth

                    <div class="comments-list" id="commentsList">
                        <!-- Comments will be loaded here -->
                    </div>
                </div>
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
/* Video Actions Section */
.video-actions-section {
    display: flex;
    gap: var(--spacing-sm);
    margin: var(--spacing-md) 0;
    padding: var(--spacing-sm) 0;
    border-bottom: 1px solid var(--border-color);
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    border: 2px solid var(--border-color);
    border-radius: var(--radius-sm);
    background-color: var(--bg-primary);
    color: var(--text-primary);
    font-size: 0.9375rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.action-btn i {
    font-size: 1.25rem;
    transition: transform 0.2s ease;
}

.action-btn span {
    font-weight: 600;
    transition: transform 0.2s ease;
}

.action-btn.like-btn:hover {
    border-color: #e91e63;
    color: #e91e63;
    background-color: rgba(233, 30, 99, 0.1);
}

.action-btn.like-btn.active {
    background-color: #e91e63;
    border-color: #e91e63;
    color: white;
    animation: likePulse 0.3s ease;
}

@keyframes likePulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
    100% {
        transform: scale(1);
    }
}

.action-btn.like-btn.active i {
    animation: heartBeat 0.5s ease;
}

@keyframes heartBeat {
    0%, 100% {
        transform: scale(1);
    }
    25% {
        transform: scale(1.2);
    }
    50% {
        transform: scale(1);
    }
    75% {
        transform: scale(1.15);
    }
}

.action-btn.favorite-btn:hover {
    border-color: #ffc107;
    color: #ffc107;
}

.action-btn.favorite-btn.active {
    background-color: #ffc107;
    border-color: #ffc107;
    color: white;
}

/* Comments Section */
.comments-section {
    margin-top: var(--spacing-xl);
    padding-top: var(--spacing-lg);
    border-top: 2px solid var(--border-color);
}

.comments-header {
    margin-bottom: var(--spacing-md);
}

.comments-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
}

.comments-title i {
    color: var(--primary-color);
}

.comments-count {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-secondary);
    background-color: var(--bg-tertiary);
    padding: 0.25rem 0.75rem;
    border-radius: var(--radius-lg);
    margin-right: 0.5rem;
}

.comment-form-container {
    margin-bottom: var(--spacing-lg);
    padding: var(--spacing-md);
    background-color: var(--bg-secondary);
    border-radius: var(--radius-md);
    border: 1px solid var(--border-color);
}

.comment-form {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-sm);
}

.comment-input-wrapper {
    width: 100%;
}

.comment-input {
    width: 100%;
    padding: var(--spacing-sm);
    border: 2px solid var(--border-color);
    border-radius: var(--radius-sm);
    font-size: 0.9375rem;
    font-family: 'Cairo', sans-serif;
    resize: vertical;
    transition: all 0.2s ease;
}

.comment-input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(24, 135, 129, 0.1);
}

.comment-form-actions {
    display: flex;
    justify-content: flex-end;
}

.comment-submit-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.5rem;
}

.comment-login-prompt {
    text-align: center;
    padding: var(--spacing-md);
    background-color: var(--bg-secondary);
    border-radius: var(--radius-md);
    margin-bottom: var(--spacing-lg);
    color: var(--text-secondary);
}

.login-link-btn {
    background: none;
    border: none;
    color: var(--primary-color);
    font-weight: 600;
    cursor: pointer;
    text-decoration: underline;
    padding: 0;
    font-size: inherit;
}

.login-link-btn:hover {
    color: var(--primary-hover);
}

.comments-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-md);
}

.comment-item {
    padding: var(--spacing-md);
    background-color: var(--bg-primary);
    border-radius: var(--radius-md);
    border: 1px solid var(--border-color);
}

.comment-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--spacing-sm);
}

.comment-user {
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
    font-weight: 600;
    color: var(--text-primary);
}

.comment-user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
}

.comment-time {
    font-size: 0.8125rem;
    color: var(--text-secondary);
}

.comment-content {
    color: var(--text-primary);
    line-height: 1.6;
    margin-bottom: var(--spacing-sm);
    white-space: pre-wrap;
}

.comment-actions {
    display: flex;
    gap: var(--spacing-sm);
}

.comment-action-btn {
    background: none;
    border: none;
    color: var(--text-secondary);
    font-size: 0.875rem;
    cursor: pointer;
    padding: 0.25rem 0.5rem;
    border-radius: var(--radius-sm);
    transition: all 0.2s ease;
}

.comment-action-btn:hover {
    background-color: var(--bg-tertiary);
    color: var(--primary-color);
}

.comment-replies {
    margin-top: var(--spacing-md);
    padding-right: var(--spacing-lg);
    border-right: 2px solid var(--border-color);
    display: flex;
    flex-direction: column;
    gap: var(--spacing-sm);
}

.comment-reply-form {
    margin-top: var(--spacing-sm);
    padding: var(--spacing-sm);
    background-color: var(--bg-secondary);
    border-radius: var(--radius-sm);
    display: none;
}

.comment-reply-form.active {
    display: block;
}

.comment-reply-input {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-sm);
    font-size: 0.875rem;
    font-family: 'Cairo', sans-serif;
    resize: vertical;
    margin-bottom: var(--spacing-xs);
}

.comment-reply-actions {
    display: flex;
    gap: var(--spacing-xs);
    justify-content: flex-end;
}

.comment-reply-btn {
    padding: 0.375rem 1rem;
    font-size: 0.875rem;
}

.empty-comments {
    text-align: center;
    padding: var(--spacing-xl);
    color: var(--text-secondary);
}

.empty-comments i {
    font-size: 3rem;
    margin-bottom: var(--spacing-sm);
    opacity: 0.5;
}

@media (max-width: 768px) {
    .video-actions-section {
        flex-wrap: wrap;
    }

    .action-btn {
        flex: 1;
        min-width: 120px;
        justify-content: center;
    }

    .comment-replies {
        padding-right: var(--spacing-sm);
    }
}

/* Share Section */
.video-share-section {
    margin: var(--spacing-md) 0;
    padding: var(--spacing-md);
    background-color: var(--bg-secondary);
    border-radius: var(--radius-md);
    border: 1px solid var(--border-color);
}

.share-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: var(--spacing-sm);
}

.share-buttons {
    display: flex;
    gap: var(--spacing-xs);
    flex-wrap: wrap;
}

.share-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-sm);
    background-color: var(--bg-primary);
    color: var(--text-primary);
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
}

.share-btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.share-btn i {
    font-size: 1.125rem;
}

.share-facebook:hover {
    background-color: #1877f2;
    border-color: #1877f2;
    color: white;
}

.share-twitter:hover {
    background-color: #1da1f2;
    border-color: #1da1f2;
    color: white;
}

.share-whatsapp:hover {
    background-color: #25d366;
    border-color: #25d366;
    color: white;
}

.share-telegram:hover {
    background-color: #0088cc;
    border-color: #0088cc;
    color: white;
}

.share-copy:hover {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
}

.share-btn.copied {
    background-color: #4CAF50;
    border-color: #4CAF50;
    color: white;
}

.share-btn.copied i::before {
    content: "\f26b"; /* bi-check-circle */
}

@media (max-width: 768px) {
    .share-buttons {
        flex-direction: column;
    }
    
    .share-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>
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

.video-captions-selector {
    position: absolute;
    top: 1rem;
    left: 1rem;
    z-index: 100;
}

.captions-btn-main {
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

.captions-btn-main:hover {
    background: rgba(0, 0, 0, 0.9);
    transform: scale(1.05);
}

.captions-btn-main.active {
    background: rgba(24, 135, 129, 0.9);
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.captions-btn-main i {
    font-size: 1.125rem;
}

/* Custom Captions Overlay */
.custom-captions-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 1.5rem;
    background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.6) 50%, transparent 100%);
    z-index: 10;
    pointer-events: none;
}

.captions-text-container {
    max-width: 90%;
    margin: 0 auto;
    text-align: center;
}

.captions-text {
    font-size: 1.25rem;
    font-weight: 600;
    color: white;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.8);
    line-height: 1.6;
    display: inline-block;
    font-family: 'Cairo', sans-serif;
}

.captions-text .word {
    display: inline-block;
    margin: 0 0.25rem;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.captions-text .word.active {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    color: white;
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(24, 135, 129, 0.5);
    font-weight: 700;
    text-shadow: 0 2px 4px rgba(0,0,0,0.5);
}

.captions-text .word.inactive {
    color: rgba(255, 255, 255, 0.8);
}

@media (max-width: 768px) {
    .captions-text {
        font-size: 1rem;
    }
    
    .custom-captions-overlay {
        padding: 1rem;
    }
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
    
    // Initialize captions if available
    @if(isset($transcriptionSegments) && $transcriptionSegments && $asset->transcription)
    initializeCaptions();
    @endif
    
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

// Share Functions
const videoUrl = window.location.href;
const videoTitle = document.querySelector('.video-details-title')?.textContent || '{{ $asset->title ?: $asset->file_name }}';
const videoDescription = '{{ $asset->site_description ? addslashes($asset->site_description) : "" }}';

function shareOnFacebook() {
    const url = encodeURIComponent(videoUrl);
    const title = encodeURIComponent(videoTitle);
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}&quote=${title}`, '_blank', 'width=600,height=400');
}

function shareOnTwitter() {
    const url = encodeURIComponent(videoUrl);
    const text = encodeURIComponent(videoTitle);
    window.open(`https://twitter.com/intent/tweet?url=${url}&text=${text}`, '_blank', 'width=600,height=400');
}

function shareOnWhatsApp() {
    const url = encodeURIComponent(videoUrl);
    const text = encodeURIComponent(`${videoTitle}\n${videoUrl}`);
    window.open(`https://wa.me/?text=${text}`, '_blank');
}

function shareOnTelegram() {
    const url = encodeURIComponent(videoUrl);
    const text = encodeURIComponent(`${videoTitle}\n${videoUrl}`);
    window.open(`https://t.me/share/url?url=${url}&text=${text}`, '_blank');
}

function copyVideoLink() {
    const copyBtn = document.querySelector('.share-copy');
    const originalHTML = copyBtn.innerHTML;
    
    navigator.clipboard.writeText(videoUrl).then(() => {
        copyBtn.classList.add('copied');
        copyBtn.innerHTML = '<i class="bi bi-check-circle"></i><span>تم النسخ!</span>';
        
        setTimeout(() => {
            copyBtn.classList.remove('copied');
            copyBtn.innerHTML = originalHTML;
        }, 2000);
    }).catch(() => {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = videoUrl;
        textArea.style.position = 'fixed';
        textArea.style.opacity = '0';
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            copyBtn.classList.add('copied');
            copyBtn.innerHTML = '<i class="bi bi-check-circle"></i><span>تم النسخ!</span>';
            
            setTimeout(() => {
                copyBtn.classList.remove('copied');
                copyBtn.innerHTML = originalHTML;
            }, 2000);
        } catch (err) {
            alert('فشل نسخ الرابط. يرجى نسخه يدوياً:\n' + videoUrl);
        }
        document.body.removeChild(textArea);
    });
}

// Use native share API if available (mobile)
if (navigator.share) {
    document.querySelectorAll('.share-btn').forEach(btn => {
        if (!btn.classList.contains('share-copy')) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const shareType = this.classList[1].replace('share-', '');
                
                if (shareType === 'facebook') {
                    shareOnFacebook();
                } else if (shareType === 'twitter') {
                    shareOnTwitter();
                } else if (shareType === 'whatsapp') {
                    shareOnWhatsApp();
                } else if (shareType === 'telegram') {
                    shareOnTelegram();
                } else {
                    navigator.share({
                        title: videoTitle,
                        text: videoDescription || videoTitle,
                        url: videoUrl
                    }).catch(() => {});
                }
            });
        }
    });
}

// Captions Functions
@if(isset($transcriptionSegments) && $transcriptionSegments && $asset->transcription)
let captionsEnabled = false;
let captionsTrack = null;
let transcriptionSegments = @json($transcriptionSegments);
let currentSegmentIndex = -1;
let captionsUpdateInterval = null;
let wordsData = [];

function initializeCaptions() {
    const video = document.getElementById('mainVideoPlayer');
    if (!video) return;
    
    // Process segments to extract words with timestamps
    processSegmentsForWords();
    
    // Create VTT content from transcription segments
    const vttContent = generateVTT(transcriptionSegments);
    
    // Create blob URL for VTT
    const blob = new Blob([vttContent], { type: 'text/vtt' });
    const vttUrl = URL.createObjectURL(blob);
    
    // Get or create track element
    captionsTrack = document.getElementById('captionsTrack');
    if (!captionsTrack) {
        captionsTrack = document.createElement('track');
        captionsTrack.id = 'captionsTrack';
        captionsTrack.kind = 'captions';
        captionsTrack.label = 'العربية';
        captionsTrack.srclang = 'ar';
        video.appendChild(captionsTrack);
    }
    
    captionsTrack.src = vttUrl;
    
    // Wait for video to load
    video.addEventListener('loadedmetadata', function() {
        if (video.textTracks && video.textTracks.length > 0) {
            const track = video.textTracks[0];
            track.mode = 'hidden'; // Start hidden
            captionsEnabled = false;
            updateCaptionsButton();
        }
    });
    
    // Listen to time updates
    video.addEventListener('timeupdate', updateCaptionsDisplay);
}

function processSegmentsForWords() {
    wordsData = [];
    
    transcriptionSegments.forEach((segment, segmentIndex) => {
        if (!segment.text || !segment.words) return;
        
        const segmentStart = segment.start;
        const segmentEnd = segment.end;
        const segmentDuration = segmentEnd - segmentStart;
        
        // If words array exists, use it
        if (segment.words && Array.isArray(segment.words) && segment.words.length > 0) {
            segment.words.forEach(word => {
                wordsData.push({
                    text: word.word || word.text || '',
                    start: word.start !== undefined ? word.start : segmentStart,
                    end: word.end !== undefined ? word.end : segmentEnd,
                    segmentIndex: segmentIndex
                });
            });
        } else {
            // Fallback: split text into words and distribute time evenly
            const words = segment.text.trim().split(/\s+/);
            const wordDuration = segmentDuration / words.length;
            
            words.forEach((word, wordIndex) => {
                wordsData.push({
                    text: word,
                    start: segmentStart + (wordIndex * wordDuration),
                    end: segmentStart + ((wordIndex + 1) * wordDuration),
                    segmentIndex: segmentIndex
                });
            });
        }
    });
}

function generateVTT(segments) {
    let vtt = 'WEBVTT\n\n';
    
    segments.forEach((segment, index) => {
        const start = formatTime(segment.start);
        const end = formatTime(segment.end);
        const text = segment.text || '';
        
        vtt += `${index + 1}\n`;
        vtt += `${start} --> ${end}\n`;
        vtt += `${text}\n\n`;
    });
    
    return vtt;
}

function formatTime(seconds) {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = Math.floor(seconds % 60);
    const milliseconds = Math.floor((seconds % 1) * 1000);
    
    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}.${String(milliseconds).padStart(3, '0')}`;
}

function updateCaptionsDisplay() {
    if (!captionsEnabled) return;
    
    const video = document.getElementById('mainVideoPlayer');
    if (!video) return;
    
    const currentTime = video.currentTime;
    const overlay = document.getElementById('customCaptionsOverlay');
    const captionsText = document.getElementById('captionsText');
    
    if (!overlay || !captionsText) return;
    
    // Find current segment
    let currentSegment = null;
    let segmentIndex = -1;
    
    for (let i = 0; i < transcriptionSegments.length; i++) {
        const segment = transcriptionSegments[i];
        if (currentTime >= segment.start && currentTime <= segment.end) {
            currentSegment = segment;
            segmentIndex = i;
            break;
        }
    }
    
    if (!currentSegment) {
        overlay.style.display = 'none';
        return;
    }
    
    // Find current word
    let currentWordIndex = -1;
    for (let i = 0; i < wordsData.length; i++) {
        const word = wordsData[i];
        if (currentTime >= word.start && currentTime <= word.end) {
            currentWordIndex = i;
            break;
        }
    }
    
    // Get all words for current segment
    const segmentWords = wordsData.filter(w => w.segmentIndex === segmentIndex);
    
    if (segmentWords.length === 0) {
        // Fallback: show segment text without word highlighting
        captionsText.innerHTML = `<span class="word inactive">${currentSegment.text}</span>`;
        overlay.style.display = 'block';
        return;
    }
    
    // Build HTML with word highlighting
    let html = '';
    segmentWords.forEach((wordData, index) => {
        const isActive = index === currentWordIndex || 
                        (currentWordIndex === -1 && index === 0 && currentTime >= wordData.start && currentTime <= wordData.end);
        const wordClass = isActive ? 'active' : 'inactive';
        html += `<span class="word ${wordClass}" data-start="${wordData.start}" data-end="${wordData.end}">${wordData.text}</span>`;
    });
    
    captionsText.innerHTML = html;
    overlay.style.display = 'block';
}

function toggleCaptions() {
    const video = document.getElementById('mainVideoPlayer');
    const overlay = document.getElementById('customCaptionsOverlay');
    
    if (!video) {
        alert('الفيديو غير متاح');
        return;
    }
    
    captionsEnabled = !captionsEnabled;
    
    if (captionsEnabled) {
        // Hide default captions, show custom overlay
        if (video.textTracks && video.textTracks.length > 0) {
            video.textTracks[0].mode = 'hidden';
        }
        overlay.style.display = 'block';
        updateCaptionsDisplay();
        
        // Start updating captions
        if (!captionsUpdateInterval) {
            captionsUpdateInterval = setInterval(updateCaptionsDisplay, 100);
        }
    } else {
        overlay.style.display = 'none';
        if (video.textTracks && video.textTracks.length > 0) {
            video.textTracks[0].mode = 'hidden';
        }
        
        // Stop updating captions
        if (captionsUpdateInterval) {
            clearInterval(captionsUpdateInterval);
            captionsUpdateInterval = null;
        }
    }
    
    updateCaptionsButton();
}

function updateCaptionsButton() {
    const btn = document.getElementById('captionsToggleBtn');
    const text = document.getElementById('captionsToggleText');
    
    if (btn && text) {
        if (captionsEnabled) {
            btn.classList.add('active');
            text.textContent = 'إخفاء الترجمة';
        } else {
            btn.classList.remove('active');
            text.textContent = 'الترجمة';
        }
    }
}
@endif

// Like and Favorite Functions
const assetId = {{ $asset->id }};
const currentUserId = {{ auth()->check() ? auth()->id() : 'null' }};

async function toggleLike(assetId) {
    if (!currentUserId) {
        showLoginModal();
        return;
    }

    const likeBtn = document.getElementById('likeBtn');
    const likeCount = document.getElementById('likeCount');
    const likeIcon = likeBtn.querySelector('i');
    
    const likeUrl = '{{ route("assets.toggle-like", ":id") }}'.replace(':id', assetId);
    const likeUrlRelative = likeUrl.replace(/^https?:\/\/[^\/]+/, '');
    
    try {
        const response = await fetch(likeUrlRelative, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        const data = await response.json();
        
        if (data.success) {
            if (data.liked) {
                likeBtn.classList.add('active');
                likeIcon.className = 'bi bi-heart-fill';
                // Add animation effect
                likeBtn.style.animation = 'none';
                setTimeout(() => {
                    likeBtn.style.animation = 'likePulse 0.3s ease';
                }, 10);
            } else {
                likeBtn.classList.remove('active');
                likeIcon.className = 'bi bi-heart';
            }
            likeCount.textContent = data.likes_count;
            
            // Update count with animation
            likeCount.style.transform = 'scale(1.2)';
            setTimeout(() => {
                likeCount.style.transform = 'scale(1)';
            }, 200);
        }
    } catch (error) {
        console.error('Error toggling like:', error);
    }
}

async function toggleFavorite(assetId) {
    if (!currentUserId) {
        showLoginModal();
        return;
    }

    const favoriteBtn = document.getElementById('favoriteBtn');
    const favoriteCount = document.getElementById('favoriteCount');
    const favoriteIcon = favoriteBtn.querySelector('i');
    
    const favoriteUrl = '{{ route("assets.toggle-favorite", ":id") }}'.replace(':id', assetId);
    const favoriteUrlRelative = favoriteUrl.replace(/^https?:\/\/[^\/]+/, '');
    
    try {
        const response = await fetch(favoriteUrlRelative, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        const data = await response.json();
        
        if (data.success) {
            if (data.favorited) {
                favoriteBtn.classList.add('active');
                favoriteIcon.className = 'bi bi-bookmark-fill';
            } else {
                favoriteBtn.classList.remove('active');
                favoriteIcon.className = 'bi bi-bookmark';
            }
            favoriteCount.textContent = data.favorites_count;
        }
    } catch (error) {
        console.error('Error toggling favorite:', error);
    }
}

function showLoginModal() {
    const authModal = new bootstrap.Modal(document.getElementById('authModal'));
    authModal.show();
}

// Comments Functions
let replyingToCommentId = null;

async function loadComments() {
    const commentsUrl = '{{ route("assets.get-comments", $asset) }}';
    const commentsUrlRelative = commentsUrl.replace(/^https?:\/\/[^\/]+/, '');
    
    try {
        const response = await fetch(commentsUrlRelative);
        const data = await response.json();
        
        if (data.success) {
            renderComments(data.comments);
            document.getElementById('commentsCount').textContent = data.comments_count;
        }
    } catch (error) {
        console.error('Error loading comments:', error);
    }
}

function renderComments(comments) {
    const commentsList = document.getElementById('commentsList');
    
    if (comments.length === 0) {
        commentsList.innerHTML = `
            <div class="empty-comments">
                <i class="bi bi-chat-dots"></i>
                <p>لا توجد تعليقات بعد. كن أول من يعلق!</p>
            </div>
        `;
        return;
    }
    
    commentsList.innerHTML = comments.map(comment => renderComment(comment)).join('');
    
    // Attach event listeners
    document.querySelectorAll('.reply-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const commentId = this.getAttribute('data-comment-id');
            toggleReplyForm(commentId);
        });
    });
    
    document.querySelectorAll('.delete-comment-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const commentId = this.getAttribute('data-comment-id');
            deleteComment(commentId);
        });
    });
}

function renderComment(comment) {
    const userAvatar = comment.user_name.charAt(0).toUpperCase();
    const canDelete = currentUserId && (currentUserId === comment.user_id || {{ auth()->check() && auth()->user()->isAdmin() ? 'true' : 'false' }});
    
    let repliesHtml = '';
    if (comment.replies && comment.replies.length > 0) {
        repliesHtml = `
            <div class="comment-replies">
                ${comment.replies.map(reply => renderComment(reply)).join('')}
            </div>
        `;
    }
    
    return `
        <div class="comment-item" id="comment-${comment.id}">
            <div class="comment-header">
                <div class="comment-user">
                    <div class="comment-user-avatar">${userAvatar}</div>
                    <span>${comment.user_name}</span>
                </div>
                <div class="comment-time">${comment.created_at}</div>
            </div>
            <div class="comment-content">${escapeHtml(comment.content)}</div>
            <div class="comment-actions">
                ${currentUserId ? `
                    <button class="comment-action-btn reply-btn" data-comment-id="${comment.id}">
                        <i class="bi bi-reply"></i> رد
                    </button>
                ` : ''}
                ${canDelete ? `
                    <button class="comment-action-btn delete-comment-btn" data-comment-id="${comment.id}">
                        <i class="bi bi-trash"></i> حذف
                    </button>
                ` : ''}
            </div>
            ${currentUserId ? `
                <div class="comment-reply-form" id="reply-form-${comment.id}">
                    <textarea class="comment-reply-input" id="reply-content-${comment.id}" placeholder="اكتب ردك هنا..." rows="2"></textarea>
                    <div class="comment-reply-actions">
                        <button class="btn btn-sm btn-primary comment-reply-btn" onclick="submitReply(${comment.id})">إرسال</button>
                        <button class="btn btn-sm btn-secondary comment-reply-btn" onclick="cancelReply(${comment.id})">إلغاء</button>
                    </div>
                </div>
            ` : ''}
            ${repliesHtml}
        </div>
    `;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function toggleReplyForm(commentId) {
    const replyForm = document.getElementById(`reply-form-${commentId}`);
    if (replyForm) {
        replyForm.classList.toggle('active');
        if (replyForm.classList.contains('active')) {
            replyForm.querySelector('textarea').focus();
        }
    }
}

function cancelReply(commentId) {
    const replyForm = document.getElementById(`reply-form-${commentId}`);
    if (replyForm) {
        replyForm.classList.remove('active');
        replyForm.querySelector('textarea').value = '';
    }
}

async function submitReply(commentId) {
    if (!currentUserId) {
        showLoginModal();
        return;
    }

    const replyContent = document.getElementById(`reply-content-${commentId}`).value.trim();
    if (!replyContent) {
        alert('يرجى إدخال نص الرد');
        return;
    }

    const commentUrl = '{{ route("assets.add-comment", $asset) }}';
    const commentUrlRelative = commentUrl.replace(/^https?:\/\/[^\/]+/, '');
    
    try {
        const response = await fetch(commentUrlRelative, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                content: replyContent,
                parent_id: commentId
            })
        });

        const data = await response.json();
        
        if (data.success) {
            cancelReply(commentId);
            loadComments();
        } else {
            alert(data.error || 'حدث خطأ أثناء إضافة الرد');
        }
    } catch (error) {
        console.error('Error submitting reply:', error);
        alert('حدث خطأ أثناء إضافة الرد');
    }
}

async function deleteComment(commentId) {
    if (!confirm('هل أنت متأكد من حذف هذا التعليق؟')) {
        return;
    }

    const deleteUrl = '{{ route("comments.delete", ":id") }}'.replace(':id', commentId);
    const deleteUrlRelative = deleteUrl.replace(/^https?:\/\/[^\/]+/, '');
    
    try {
        const response = await fetch(deleteUrlRelative, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        const data = await response.json();
        
        if (data.success) {
            loadComments();
        } else {
            alert(data.error || 'حدث خطأ أثناء حذف التعليق');
        }
    } catch (error) {
        console.error('Error deleting comment:', error);
        alert('حدث خطأ أثناء حذف التعليق');
    }
}

// Comment Form Handler
const commentForm = document.getElementById('commentForm');
if (commentForm) {
    commentForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (!currentUserId) {
            showLoginModal();
            return;
        }

        const content = document.getElementById('commentContent').value.trim();
        if (!content) {
            alert('يرجى إدخال نص التعليق');
            return;
        }

        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> جاري الإرسال...';

        const commentUrl = '{{ route("assets.add-comment", $asset) }}';
        const commentUrlRelative = commentUrl.replace(/^https?:\/\/[^\/]+/, '');
        
        try {
            const response = await fetch(commentUrlRelative, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    content: content
                })
            });

            const data = await response.json();
            
            if (data.success) {
                document.getElementById('commentContent').value = '';
                loadComments();
            } else {
                alert(data.error || 'حدث خطأ أثناء إضافة التعليق');
            }
        } catch (error) {
            console.error('Error submitting comment:', error);
            alert('حدث خطأ أثناء إضافة التعليق');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
}

// Load comments on page load
document.addEventListener('DOMContentLoaded', function() {
    loadComments();
});
</script>
@endpush
