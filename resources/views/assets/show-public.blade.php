@extends('layouts.public')

@section('title', $asset->title ?: $asset->file_name)

@php
    $videoTitle = $asset->title ?: $asset->file_name;
    $videoDescription = $asset->site_description ?: ($asset->title ?: 'شاهد هذا الفيديو على المناجاة');
    $videoUrl = route('assets.show.public', $asset);
    
    // Get video file URL (النسخة المحددة للعرض على الويب أو الأصلي)
    $fileUrl = null;
    $pathForUrl = $effectiveVideoPath ?? $asset->relative_path;
    if ($pathForUrl && strpos($pathForUrl, 'assets/') === 0) {
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($pathForUrl)) {
            $fileUrl = url('storage/' . $pathForUrl);
        }
    }
    
    // Ensure videoUrl is absolute
    $videoUrl = url(route('assets.show.public', $asset));
    
    // Get thumbnail image (use absolute URL for social media)
    $thumbnailUrl = null;
    if ($asset->thumbnail_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($asset->thumbnail_path)) {
        $thumbnailUrl = url('storage/' . $asset->thumbnail_path);
    } elseif ($asset->relative_path && strpos($asset->relative_path, 'assets/') === 0) {
        // Try to get a frame from video as fallback
        $thumbnailUrl = url('images/logo.png'); // Fallback to logo
    } else {
        $thumbnailUrl = url('images/logo.png');
    }
    
    // Get site URL
    $siteUrl = config('app.url');
    $siteName = 'المناجاة';
@endphp

@section('meta')
    <!-- Primary Meta Tags -->
    <meta name="title" content="{{ $videoTitle }}">
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags($videoDescription), 160) }}">
    <meta name="keywords" content="{{ $asset->speaker_name ? $asset->speaker_name . ', ' : '' }}{{ $asset->categories && $asset->categories->count() > 0 ? $asset->categories->pluck('name')->implode(', ') . ', ' : '' }}فيديو, محاضرة, خطبة, المناجاة">
    <meta name="author" content="{{ $siteName }}">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="video.other">
    <meta property="og:url" content="{{ $videoUrl }}">
    <meta property="og:title" content="{{ $videoTitle }}">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags($videoDescription), 200) }}">
    <meta property="og:image" content="{{ $thumbnailUrl }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ $videoTitle }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:locale" content="ar_AR">
    
    <!-- Video specific Open Graph tags -->
    @if($asset->duration_seconds)
    <meta property="video:duration" content="{{ $asset->duration_seconds }}">
    @endif
    @if($asset->speaker_name)
    <meta property="video:actor" content="{{ $asset->speaker_name }}">
    @endif
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ $videoUrl }}">
    <meta name="twitter:title" content="{{ $videoTitle }}">
    <meta name="twitter:description" content="{{ \Illuminate\Support\Str::limit(strip_tags($videoDescription), 200) }}">
    <meta name="twitter:image" content="{{ $thumbnailUrl }}">
    <meta name="twitter:image:alt" content="{{ $videoTitle }}">
    
    <!-- Additional Meta Tags -->
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ $videoUrl }}">
    
    <!-- Structured Data (JSON-LD) -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "VideoObject",
        "name": "{{ addslashes($videoTitle) }}",
        "description": "{{ addslashes(\Illuminate\Support\Str::limit(strip_tags($videoDescription), 300)) }}",
        "thumbnailUrl": "{{ $thumbnailUrl }}",
        @if($asset->created_at)
        "uploadDate": "{{ $asset->created_at->toIso8601String() }}",
        @endif
        @if($asset->duration_seconds)
        "duration": "PT{{ gmdate('H', $asset->duration_seconds) }}H{{ gmdate('i', $asset->duration_seconds % 3600) }}M{{ gmdate('s', $asset->duration_seconds % 60) }}S",
        @endif
        @if($fileUrl)
        "contentUrl": "{{ $fileUrl }}",
        @endif
        "embedUrl": "{{ $videoUrl }}",
        @if($asset->speaker_name)
        "author": {
            "@type": "Person",
            "name": "{{ $asset->speaker_name }}"
        },
        @endif
        "publisher": {
            "@type": "Organization",
            "name": "{{ $siteName }}",
            "logo": {
                "@type": "ImageObject",
                "url": "{{ asset('images/logo.png') }}"
            }
        }
    }
    </script>
@endsection

@section('content')
<div class="home-layout">
    <!-- Sidebar (نفس الصفحة الرئيسية) -->
    <aside class="sidebar-menu" id="sidebarMenu">
        <div class="sidebar-content">
            <nav class="sidebar-nav">
                <!-- Main Navigation -->
                <a href="{{ route('home') }}" class="sidebar-item {{ request()->routeIs('home') && !request('content_category') && !request('scholar_id') ? 'active' : '' }}">
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
            <div class="video-player-section">
                <!-- Main Video Section -->
                <div>
            {{-- بنرات الإعلانات فوق الفيديو --}}
            @if(isset($banners) && $banners->count() > 0)
            <div class="video-page-banners video-page-banners--above-video">
                @foreach($banners as $banner)
                    @include('partials.banner', ['banner' => $banner])
                @endforeach
            </div>
            @endif

            <!-- Video Player -->
            @php
                $fileUrl = null;
                $streamUrl = null;
                $pathForPlayer = $effectiveVideoPath ?? $asset->relative_path;
                if ($pathForPlayer && strpos($pathForPlayer, 'assets/') === 0) {
                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($pathForPlayer)) {
                        $fileUrl = asset('storage/' . $pathForPlayer);
                        $streamUrl = url(route('assets.stream.public', $asset));
                    }
                }
                
                // Get poster image (thumbnail or default)
                $posterUrl = null;
                if ($asset->thumbnail_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($asset->thumbnail_path)) {
                    $posterUrl = asset('storage/' . $asset->thumbnail_path);
                } else {
                    $posterUrl = asset('images/logo_min.png');
                }
            @endphp

            @if($fileUrl)
            <div class="video-player-container">
                @if(in_array(strtolower($asset->extension), ['mp4', 'mov', 'mkv', 'm4v', 'webm', 'avi']))
                    @php
                        // إذا كان المستخدم حدد نسخة للعرض على الويب (غير الأصلي)، نعرضها عبر البث المباشر ولا نستخدم HLS
                        $useSelectedWebVersion = $effectiveVideoPath && $effectiveVideoPath !== $asset->relative_path;
                        $hlsMasterPlaylist = null;
                        if (!$useSelectedWebVersion && $asset->hlsVersions && $asset->hlsVersions->count() > 0) {
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
                            autoplay
                            preload="metadata"
                            poster="{{ $posterUrl }}"
                            data-src="{{ $streamUrl ?? $fileUrl }}"
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
                            <source src="{{ $streamUrl ?? $fileUrl }}" type="audio/{{ $asset->extension }}">
                            متصفحك لا يدعم تشغيل الصوت.
                        </audio>
                    </div>
                @endif
            </div>
            @endif

            <!-- Video Details -->
            <div class="video-details">
                <div class="video-title-actions">
                    <h1 class="video-details-title">{{ $asset->title ?: $asset->file_name }}</h1>
                    
                    <!-- Like, Favorite and Share Buttons -->
                    <div class="video-actions-inline">
                        @php
                            $likesCount = $asset->likes()->count();
                            $favoritesCount = $asset->favorites()->count();
                        @endphp
                        @auth
                            <button class="action-btn-inline like-btn-inline {{ isset($userLiked) && $userLiked ? 'active' : '' }}" onclick="toggleLike({{ $asset->id }})" id="likeBtn">
                                <i class="bi {{ isset($userLiked) && $userLiked ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-up' }}"></i>
                                <span id="likeCount">{{ $likesCount > 0 ? number_format($likesCount) : '' }}</span>
                            </button>
                            <button class="action-btn-inline favorite-btn-inline {{ isset($userFavorited) && $userFavorited ? 'active' : '' }}" onclick="toggleFavorite({{ $asset->id }})" id="favoriteBtn">
                                <i class="bi {{ isset($userFavorited) && $userFavorited ? 'bi-bookmark-fill' : 'bi-bookmark' }}"></i>
                                <span id="favoriteCount">{{ $favoritesCount > 0 ? number_format($favoritesCount) : '' }}</span>
                            </button>
                        @else
                            <button class="action-btn-inline like-btn-inline" onclick="showLoginModal()">
                                <i class="bi bi-hand-thumbs-up"></i>
                                <span>{{ $likesCount > 0 ? number_format($likesCount) : '' }}</span>
                            </button>
                            <button class="action-btn-inline favorite-btn-inline" onclick="showLoginModal()">
                                <i class="bi bi-bookmark"></i>
                                <span>{{ $favoritesCount > 0 ? number_format($favoritesCount) : '' }}</span>
                            </button>
                        @endauth
                        <button class="action-btn-inline share-btn-inline" onclick="shareVideo()" id="shareBtn" title="مشاركة الفيديو">
                            <i class="bi bi-share"></i>
                        </button>
                    </div>
                </div>
                
                <div class="video-details-meta">
                    @if($asset->speaker_name)
                        <a href="{{ route('home', ['search' => $asset->speaker_name]) }}" class="video-meta-link">
                            <i class="bi bi-person"></i>
                            {{ $asset->speaker_name }}
                        </a>
                    @endif
                    @if($asset->categories && $asset->categories->count() > 0)
                        @foreach($asset->categories as $cat)
                            <a href="{{ route('home', ['content_category' => $cat->name]) }}" class="video-meta-link">
                                <i class="bi bi-tag"></i>
                                {{ $cat->name }}
                            </a>
                        @endforeach
                    @endif
                    @if($asset->gregorian_year)
                        <span>
                            <i class="bi bi-calendar"></i>
                            {{ $asset->gregorian_year }}
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
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: var(--spacing-sm);">
                    @if($asset->topics)
                        @foreach(explode("\n", $asset->topics) as $topic)
                            @if(trim($topic))
                                <a href="{{ route('home', ['search' => trim($topic)]) }}" class="badge badge-primary video-meta-link">{{ trim($topic) }}</a>
                            @endif
                        @endforeach
                    @endif
                </div>
                @endif

                @if($asset->site_description)
                <div class="video-description">
                    <div class="video-description-text">{{ $asset->site_description }}</div>
                </div>
                @endif

                @if(($asset->transcription || (isset($transcriptionSegments) && $transcriptionSegments)) && isset($translationLanguages))
                <!-- قسم المحتوى النصي والترجمة -->
                <div class="transcription-section" id="transcriptionSection" style="margin-top: 1.5rem;">
                    <div class="transcription-section-header" style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                        <h2 class="transcription-section-title" style="margin: 0; font-size: 1.1rem;">
                            <i class="bi bi-card-text"></i>
                            المحتوى النصي
                        </h2>
                        <div class="transcription-lang-tabs" style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                            <button type="button" class="transcription-lang-btn active" data-lang="ar" onclick="setTranscriptionLang('ar')" style="padding: 0.35rem 0.6rem; border: 1px solid var(--border-color, #ddd); border-radius: 6px; background: var(--bg-secondary); cursor: pointer; font-size: 0.9rem;">العربية</button>
                            @foreach($translationLanguages as $code => $name)
                            @if(!empty(($asset->translation_segments ?? [])[$code]))
                            <button type="button" class="transcription-lang-btn" data-lang="{{ $code }}" onclick="setTranscriptionLang('{{ $code }}')" style="padding: 0.35rem 0.6rem; border: 1px solid var(--border-color, #ddd); border-radius: 6px; background: var(--bg-secondary); cursor: pointer; font-size: 0.9rem;">{{ $name }}</button>
                            @endif
                            @endforeach
                        </div>
                        <div class="transcription-actions" style="margin-right: auto; display: flex; flex-wrap: wrap; gap: 0.5rem;">
                            @foreach($translationLanguages as $code => $name)
                            @if(empty(($asset->translation_segments ?? [])[$code]))
                            <button type="button" class="translation-trigger-btn" data-lang="{{ $code }}" data-name="{{ $name }}" onclick="translateTranscription('{{ $code }}', '{{ $name }}')" style="padding: 0.35rem 0.6rem; border: 1px solid #0d6efd; border-radius: 6px; background: transparent; color: #0d6efd; cursor: pointer; font-size: 0.85rem;">
                                ترجمة إلى {{ $name }}
                            </button>
                            @endif
                            @endforeach
                            <button type="button" class="btn-translate-all-languages" onclick="translateAllLanguages()" style="padding: 0.35rem 0.6rem; border: 1px solid #0d6efd; border-radius: 6px; background: #0d6efd; color: #fff; cursor: pointer; font-size: 0.85rem;"><i class="bi bi-translate me-1"></i>ترجمة جميع اللغات</button>
                            <a href="{{ route('assets.download-transcription-all', $asset) }}" class="btn-download-all" download style="padding: 0.35rem 0.6rem; border: 1px solid #6f42c1; border-radius: 6px; background: transparent; color: #6f42c1; text-decoration: none; font-size: 0.85rem;"><i class="bi bi-file-zip me-1"></i>تحميل كل اللغات (ZIP)</a>
                        </div>
                    </div>
                    <div id="transcriptionContentAr" class="transcription-content" data-lang="ar" style="max-height: 400px; overflow-y: auto; padding: 0.75rem; background: var(--bg-tertiary, #f5f5f5); border-radius: 8px; text-align: right; direction: rtl;">
                        @if(isset($transcriptionSegments) && $transcriptionSegments && count($transcriptionSegments) > 0)
                        <table class="transcription-segments-table" style="width: 100%; border-collapse: collapse;">
                            <tbody>
                                @foreach($transcriptionSegments as $seg)
                                @php
                                    $start = $seg['start'] ?? 0;
                                    $end = $seg['end'] ?? 0;
                                    $text = trim($seg['text'] ?? '');
                                    $startFmt = sprintf('%d:%02d:%02d', floor($start/3600), floor(fmod($start,3600)/60), floor($start%60));
                                    $endFmt = sprintf('%d:%02d:%02d', floor($end/3600), floor(fmod($end,3600)/60), floor($end%60));
                                @endphp
                                <tr style="border-bottom: 1px solid rgba(0,0,0,0.06);">
                                    <td class="text-nowrap text-muted" style="padding: 0.35rem 0.5rem 0.35rem 0; font-size: 0.8rem; vertical-align: top;">{{ $startFmt }} – {{ $endFmt }}</td>
                                    <td style="padding: 0.35rem 0;">{{ $text }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @else
                        <p class="mb-0" style="white-space: pre-wrap;">{{ $asset->transcription }}</p>
                        @endif
                    </div>
                    @foreach($translationLanguages as $code => $name)
                    @php $langSegs = ($asset->translation_segments ?? [])[$code] ?? []; @endphp
                    @if(!empty($langSegs))
                    <div id="transcriptionContent{{ $code }}" class="transcription-content d-none" data-lang="{{ $code }}" style="max-height: 400px; overflow-y: auto; padding: 0.75rem; background: var(--bg-tertiary, #f5f5f5); border-radius: 8px; text-align: left; direction: ltr;">
                        <table class="transcription-segments-table" style="width: 100%; border-collapse: collapse;">
                            <tbody>
                                @foreach($langSegs as $seg)
                                @php
                                    $start = $seg['start'] ?? 0;
                                    $end = $seg['end'] ?? 0;
                                    $text = trim($seg['text'] ?? '');
                                    $startFmt = sprintf('%d:%02d:%02d', floor($start/3600), floor(fmod($start,3600)/60), floor($start%60));
                                    $endFmt = sprintf('%d:%02d:%02d', floor($end/3600), floor(fmod($end,3600)/60), floor($end%60));
                                @endphp
                                <tr style="border-bottom: 1px solid rgba(0,0,0,0.06);">
                                    <td class="text-nowrap text-muted" style="padding: 0.35rem 0.5rem 0.35rem 0; font-size: 0.8rem; vertical-align: top;">{{ $startFmt }} – {{ $endFmt }}</td>
                                    <td style="padding: 0.35rem 0;">{{ $text }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                    @endforeach
                    <div id="transcriptionTranslateLoading" class="d-none" style="padding: 1rem; text-align: center; color: var(--text-muted);">جاري الترجمة...</div>
                    <div id="translateLoadingModal" class="translate-loading-modal" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(0,0,0,0.5); align-items: center; justify-content: center;">
                        <div style="background: var(--bg-secondary, #fff); padding: 2rem; border-radius: 12px; text-align: center; min-width: 260px; box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
                            <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                                <span class="visually-hidden">جاري التحميل...</span>
                            </div>
                            <p class="mb-0 fw-medium" id="translateLoadingModalTitle">جاري الترجمة...</p>
                            <p class="small text-muted mt-1 mb-0" id="translateLoadingModalSubtitle">قد يستغرق ذلك دقيقة</p>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Comments Section -->
            <div class="comments-section" id="commentsSection">
                <div class="comments-header">
                    <h2 class="comments-title">
                        <i class="bi bi-chat-left-text"></i>
                        التعليقات
                        <span class="comments-count" id="commentsCount">0</span>
                    </h2>
                </div>
                @auth
                <div class="comment-form-container">
                    <form class="comment-form" id="commentForm">
                        @csrf
                        <div class="comment-input-wrapper">
                            <textarea class="comment-input" id="commentInput" name="content" rows="3" placeholder="أضف تعليقاً..." maxlength="2000" required></textarea>
                            <small class="text-muted d-block mt-1"><span id="commentCharCount">0</span> / 2000</small>
                        </div>
                        <div class="comment-form-actions">
                            <button type="submit" class="btn btn-primary comment-submit-btn" id="commentSubmitBtn">
                                <i class="bi bi-send me-1"></i>إرسال
                            </button>
                        </div>
                    </form>
                </div>
                @else
                <div class="comment-login-prompt">
                    <a href="#" onclick="event.preventDefault(); showLoginModal();">سجّل الدخول</a> لكتابة تعليق.
                </div>
                @endauth
                <div class="comments-list" id="commentsList">
                    <!-- تُحمّل التعليقات عبر JavaScript -->
                </div>
                <div class="empty-comments d-none" id="emptyComments">
                    <i class="bi bi-chat-dots"></i>
                    <p>لا توجد تعليقات بعد. كن أول من يعلق.</p>
                </div>
            </div>
        </div>

        <!-- Sidebar - Related Videos -->
        <div>
            <div class="sidebar">
                <h6 class="sidebar-title">فيديوهات مقترحة</h6>
                
                @if(isset($relatedAssets) && $relatedAssets->count() > 0)
                    @foreach($relatedAssets as $relatedAsset)
                    <a href="{{ route('assets.show.public', $relatedAsset) }}" class="related-video {{ ($relatedAsset->orientation ?? '') === 'portrait' ? 'related-video--portrait' : '' }}">
                        <div class="related-video-thumb">
                            @if($relatedAsset->thumbnail_path)
                                <img src="{{ asset('storage/' . $relatedAsset->thumbnail_path) }}" 
                                     alt="{{ $relatedAsset->title ?: $relatedAsset->file_name }}" 
                                     loading="lazy"
                                     decoding="async"
                                     fetchpriority="low"
                                     style="width: 100%; height: 100%; object-fit: cover; opacity: 0; transition: opacity 0.3s;"
                                     onload="this.style.opacity='1'"
                                     onerror="this.onerror=null; this.src='{{ asset('images/logo_min.png') }}';">
                            @else
                                <img src="{{ asset('images/logo_min.png') }}" 
                                     alt="{{ $relatedAsset->title ?: $relatedAsset->file_name }}" 
                                     loading="lazy"
                                     decoding="async"
                                     fetchpriority="low"
                                     style="width: 100%; height: 100%; object-fit: cover; opacity: 0; transition: opacity 0.3s;"
                                     onload="this.style.opacity='1'">
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
</div>
@endsection

@push('styles')
<style>
/* بنرات الإعلانات في صفحة الفيديو */
.video-page-banners {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: flex-start;
}
.video-page-banners--above-video {
    margin-bottom: 1rem;
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.video-page-banners--above-video .banner-link.banner-wrap {
    width: 100%;
    max-width: 100%;
}
.video-page-banners--above-video .banner-wrap.banner-size--rectangle {
    max-width: none;
}
.video-page-banners--above-video .banner-wrap.banner-size--landscape {
    width: 100%;
    max-width: 100%;
}
.video-page-banners--above-video .banner-wrap.banner-size--vertical {
    width: 100%;
    max-width: 280px;
}
.video-page-banners .banner-link.banner-wrap {
    display: block;
    overflow: hidden;
    border-radius: 0.75rem;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
    text-decoration: none;
    flex-shrink: 0;
}
.video-page-banners .banner-link .banner-img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.video-page-banners .banner-wrap.banner-size--vertical {
    width: 140px;
    aspect-ratio: 9 / 16;
}
.video-page-banners .banner-wrap.banner-size--landscape {
    width: 260px;
    max-width: 100%;
    aspect-ratio: 16 / 9;
}
.video-page-banners .banner-wrap.banner-size--rectangle {
    width: 100%;
    aspect-ratio: 4 / 1;
}

/* Home Layout */
.home-layout {
    display: flex;
    position: relative;
    min-height: calc(100vh - 200px);
    margin-top: 0;
}

/* Sidebar Menu - z-index أعلى من منطقة الفيديو لتبقى القائمة قابلة للنقر أثناء التشغيل */
.sidebar-menu {
    position: relative;
    width: 240px;
    min-height: calc(100vh - 60px);
    background-color: var(--bg-primary);
    border-left: 1px solid var(--border-color);
    box-shadow: var(--shadow-sm);
    z-index: 200;
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

.sidebar-category-image {
    width: 24px;
    height: 24px;
    object-fit: cover;
    border-radius: 4px;
    flex-shrink: 0;
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

/* Video Title and Actions */
.video-title-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--spacing-md);
    margin-bottom: 0;
    flex-wrap: wrap;
}

.video-details-title {
    flex: 1;
    min-width: 0;
    margin: 0;
}

.video-details-meta .video-meta-link {
    color: var(--text-secondary, #666);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}
.video-details-meta .video-meta-link:hover {
    color: var(--primary, #0d6efd);
    text-decoration: underline;
}
.video-details-meta .video-meta-link.badge {
    text-decoration: none;
}
.video-details-meta .video-meta-link.badge:hover {
    opacity: 0.9;
    text-decoration: none;
}

.video-actions-inline {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
}

.action-btn-inline {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 50px;
    background-color: var(--bg-tertiary);
    color: var(--text-primary);
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.action-btn-inline:hover {
    background-color: var(--bg-secondary);
}

.action-btn-inline i {
    font-size: 1.125rem;
}

.action-btn-inline.like-btn-inline.active {
    background-color: rgba(233, 30, 99, 0.1);
    color: #e91e63;
}

.action-btn-inline.like-btn-inline.active i {
    color: #e91e63;
}

.action-btn-inline.favorite-btn-inline.active {
    background-color: rgba(255, 193, 7, 0.1);
    color: #ffc107;
}

.action-btn-inline.favorite-btn-inline.active i {
    color: #ffc107;
}


.action-btn-inline.share-btn-inline:hover {
    background-color: var(--bg-secondary);
}

/* Video Actions Section (Old - to be removed) */
.video-actions-section {
    display: none;
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

.action-btn.share-btn {
    position: relative;
}

.video-actions-section {
    position: relative;
}

.action-btn.share-btn:hover {
    border-color: var(--primary-color);
    color: var(--primary-color);
    background-color: rgba(24, 135, 129, 0.1);
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
    font-family: 'Alexandria', sans-serif;
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
    font-family: 'Alexandria', sans-serif;
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
    font-family: 'Alexandria', sans-serif;
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
    
    // Load video source (HLS or regular) — call on page load so stream starts early and play is faster
    function loadVideo() {
        if (currentVideo.src || hlsInstance) return; // Already loaded
        
        if (hlsUrl && Hls.isSupported()) {
            hlsInstance = new Hls({
                enableWorker: true,
                lowLatencyMode: false,
                backBufferLength: 90,
                maxBufferLength: 30,
                maxMaxBufferLength: 60,
                startLevel: -1,
                capLevelToPlayerSize: true
            });
            hlsInstance.loadSource(hlsUrl);
            hlsInstance.attachMedia(currentVideo);
            hlsInstance.on(Hls.Events.MANIFEST_PARSED, function() {
                currentVideo.play().catch(() => {});
            });
            hlsInstance.on(Hls.Events.ERROR, function(event, data) {
                if (data.fatal) {
                    switch(data.type) {
                        case Hls.ErrorTypes.NETWORK_ERROR:
                            hlsInstance.startLoad();
                            break;
                        case Hls.ErrorTypes.MEDIA_ERROR:
                            hlsInstance.recoverMediaError();
                            break;
                        default:
                            if (hlsInstance) {
                                hlsInstance.destroy();
                                hlsInstance = null;
                            }
                            if (regularSrc) {
                                currentVideo.src = regularSrc;
                                currentVideo.load();
                            }
                            break;
                    }
                }
            });
        } else if (hlsUrl && currentVideo.canPlayType('application/vnd.apple.mpegurl')) {
            currentVideo.src = hlsUrl;
            currentVideo.play().catch(() => {});
        } else if (regularSrc) {
            currentVideo.src = regularSrc;
            currentVideo.load();
            currentVideo.play().catch(() => {});
        }
    }
    
    // Start loading as soon as the page is ready (reduces loading time when user opens another video)
    loadVideo();
    
    // Ensure load on first play/click if something delayed (e.g. Hls not ready)
    currentVideo.addEventListener('play', function() {
        loadVideo();
    }, { once: true });
    currentVideo.addEventListener('click', function() {
        if (!currentVideo.src && !hlsInstance) loadVideo();
    }, { once: true });
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

// No video auto-play needed - using thumbnails only

// عند النقر على أي رابط للانتقال (فيديو مقترح، القائمة، إلخ): إيقاف الفيديو وتحرير المصدر فوراً
// حتى لا ينتظر المتصفح وقتاً طويلاً عند تفريغ الصفحة
document.addEventListener('click', function(e) {
    const link = e.target.closest('a[href]');
    if (!link || !currentVideo) return;
    if (link.target === '_blank') return; // لا نمسح إذا الرابط يفتح في تاب جديد
    const href = (link.getAttribute('href') || '').trim();
    if (!href || href === '#' || href.startsWith('javascript:')) return;
    // رابط ينتقل في نفس التاب — إيقاف الفيديو فوراً لتسريع الانتقال
    currentVideo.pause();
    currentVideo.removeAttribute('src');
    currentVideo.load();
    if (hlsInstance) {
        try { hlsInstance.detachMedia(); } catch (_) {}
        var _h = hlsInstance;
        hlsInstance = null;
        setTimeout(function() { try { _h.destroy(); } catch (_) {} }, 0);
    }
}, true);

// Simple Share Function
async function shareVideo() {
    const videoUrl = window.location.href;
    const videoTitle = document.querySelector('.video-details-title')?.textContent || '{{ $asset->title ?: $asset->file_name }}';
    const shareBtn = document.getElementById('shareBtn');
    
    // Try to use Web Share API (mobile devices)
    if (navigator.share) {
        try {
            await navigator.share({
                title: videoTitle,
                text: videoTitle,
                url: videoUrl
            });
            return;
        } catch (err) {
            // User cancelled or error occurred, fall back to copy
            if (err.name === 'AbortError') {
                return;
            }
        }
    }
    
    // Fallback: Copy link to clipboard
    try {
        await navigator.clipboard.writeText(videoUrl);
        
        // Show success feedback
        const originalHTML = shareBtn.innerHTML;
        shareBtn.innerHTML = '<i class="bi bi-check-circle"></i>';
        shareBtn.style.color = '#4CAF50';
        
        setTimeout(() => {
            shareBtn.innerHTML = originalHTML;
            shareBtn.style.color = '';
        }, 2000);
    } catch (err) {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = videoUrl;
        textArea.style.position = 'fixed';
        textArea.style.opacity = '0';
        document.body.appendChild(textArea);
        textArea.select();
        
        try {
            document.execCommand('copy');
            document.body.removeChild(textArea);
            
            // Show success feedback
            const originalHTML = shareBtn.innerHTML;
            shareBtn.innerHTML = '<i class="bi bi-check-circle"></i>';
            shareBtn.style.color = '#4CAF50';
            
            setTimeout(() => {
                shareBtn.innerHTML = originalHTML;
                shareBtn.style.color = '';
            }, 2000);
        } catch (copyErr) {
            document.body.removeChild(textArea);
            alert('تم نسخ الرابط: ' + videoUrl);
        }
    }
}

// Captions Functions
@if(isset($transcriptionSegments) && $transcriptionSegments && $asset->transcription)
let captionsEnabled = false;
let captionsTrack = null;
let transcriptionSegments = @json($transcriptionSegments);
let translationSegmentsByLang = @json($asset->translation_segments ?? []);
window.captionsLang = 'ar';
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
    
    var lang = (typeof window.captionsLang !== 'undefined' && window.captionsLang) ? window.captionsLang : 'ar';
    var segmentsToUse = (lang === 'ar' || !translationSegmentsByLang || !translationSegmentsByLang[lang])
        ? transcriptionSegments
        : translationSegmentsByLang[lang];
    
    if (!segmentsToUse || segmentsToUse.length === 0) {
        segmentsToUse = transcriptionSegments;
    }
    
    let currentSegment = null;
    let segmentIndex = -1;
    
    for (let i = 0; i < transcriptionSegments.length; i++) {
        const segment = transcriptionSegments[i];
        if (currentTime >= segment.start && currentTime <= segment.end) {
            segmentIndex = i;
            currentSegment = (segmentsToUse[i] && segmentsToUse[i].text !== undefined) ? segmentsToUse[i] : segment;
            break;
        }
    }
    
    if (!currentSegment) {
        overlay.style.display = 'none';
        return;
    }
    
    if (lang !== 'ar' || !wordsData || wordsData.length === 0) {
        captionsText.innerHTML = `<span class="word inactive">${(currentSegment.text || '').replace(/</g, '&lt;')}</span>`;
        overlay.style.display = 'block';
        if (lang === 'ar' || lang === 'ur') {
            overlay.style.direction = 'rtl';
            overlay.style.textAlign = 'center';
        } else {
            overlay.style.direction = 'ltr';
            overlay.style.textAlign = 'center';
        }
        return;
    }
    
    let currentWordIndex = -1;
    for (let i = 0; i < wordsData.length; i++) {
        const word = wordsData[i];
        if (currentTime >= word.start && currentTime <= word.end) {
            currentWordIndex = i;
            break;
        }
    }
    
    const segmentWords = wordsData.filter(w => w.segmentIndex === segmentIndex);
    
    if (segmentWords.length === 0) {
        captionsText.innerHTML = `<span class="word inactive">${(currentSegment.text || '').replace(/</g, '&lt;')}</span>`;
        overlay.style.display = 'block';
        return;
    }
    
    let html = '';
    segmentWords.forEach((wordData, index) => {
        const isActive = index === currentWordIndex || 
                        (currentWordIndex === -1 && index === 0 && currentTime >= wordData.start && currentTime <= wordData.end);
        const wordClass = isActive ? 'active' : 'inactive';
        html += `<span class="word ${wordClass}" data-start="${wordData.start}" data-end="${wordData.end}">${wordData.text.replace(/</g, '&lt;')}</span>`;
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

// المحتوى النصي والترجمة
@if(($asset->transcription || (isset($transcriptionSegments) && $transcriptionSegments)) && isset($translationLanguages))
let currentTranscriptionLang = 'ar';
const assetIdForTranscription = {{ $asset->id }};
const translateUrl = '{{ route("assets.translate-transcription", $asset) }}';
const downloadBaseUrl = '{{ route("assets.download-transcription", $asset) }}';
var translationLanguagesToTranslate = [
    @foreach($translationLanguages as $code => $name)
    @if(empty(($asset->translation_segments ?? [])[$code]))
    { code: '{{ $code }}', name: '{{ addslashes($name) }}' },
    @endif
    @endforeach
];

function setTranscriptionLang(lang) {
    currentTranscriptionLang = lang;
    window.captionsLang = lang;
    document.querySelectorAll('.transcription-content').forEach(function(el) {
        el.classList.add('d-none');
        if (el.getAttribute('data-lang') === lang) {
            el.classList.remove('d-none');
            el.style.direction = lang === 'ar' || lang === 'ur' ? 'rtl' : 'ltr';
            el.style.textAlign = lang === 'ar' || lang === 'ur' ? 'right' : 'left';
        }
    });
    document.querySelectorAll('.transcription-lang-btn').forEach(function(btn) {
        btn.classList.toggle('active', btn.getAttribute('data-lang') === lang);
    });
    var downloadLink = document.querySelector('.btn-download-transcription');
    if (downloadLink) {
        downloadLink.href = downloadBaseUrl + '?lang=' + encodeURIComponent(lang);
    }
}

function applyTranslationUI(lang, name, segments) {
    if (typeof translationSegmentsByLang !== 'undefined') {
        translationSegmentsByLang[lang] = segments;
    }
    var tabs = document.querySelector('.transcription-lang-tabs');
    if (tabs) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'transcription-lang-btn';
        btn.setAttribute('data-lang', lang);
        btn.textContent = name;
        btn.onclick = function() { setTranscriptionLang(lang); };
        btn.style.cssText = 'padding: 0.35rem 0.6rem; border: 1px solid var(--border-color, #ddd); border-radius: 6px; background: var(--bg-secondary); cursor: pointer; font-size: 0.9rem;';
        tabs.appendChild(btn);
    }
    var container = document.getElementById('transcriptionSection');
    if (container) {
        var div = document.createElement('div');
        div.id = 'transcriptionContent' + lang;
        div.className = 'transcription-content';
        div.setAttribute('data-lang', lang);
        div.style.cssText = 'max-height: 400px; overflow-y: auto; padding: 0.75rem; background: var(--bg-tertiary, #f5f5f5); border-radius: 8px; text-align: left; direction: ltr;';
        var table = document.createElement('table');
        table.className = 'transcription-segments-table';
        table.style.cssText = 'width: 100%; border-collapse: collapse;';
        var tbody = document.createElement('tbody');
        segments.forEach(function(seg) {
            var start = seg.start || 0, end = seg.end || 0, text = (seg.text || '').trim();
            var startFmt = Math.floor(start/3600) + ':' + String(Math.floor((start%3600)/60)).padStart(2,'0') + ':' + String(Math.floor(start%60)).padStart(2,'0');
            var endFmt = Math.floor(end/3600) + ':' + String(Math.floor((end%3600)/60)).padStart(2,'0') + ':' + String(Math.floor(end%60)).padStart(2,'0');
            var tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid rgba(0,0,0,0.06)';
            tr.innerHTML = '<td class="text-nowrap text-muted" style="padding: 0.35rem 0.5rem 0.35rem 0; font-size: 0.8rem; vertical-align: top;">' + startFmt + ' – ' + endFmt + '</td><td style="padding: 0.35rem 0;">' + text + '</td>';
            tbody.appendChild(tr);
        });
        table.appendChild(tbody);
        div.appendChild(table);
        container.appendChild(div);
    }
    setTranscriptionLang(lang);
    var triggerBtn = document.querySelector('.translation-trigger-btn[data-lang="' + lang + '"]');
    if (triggerBtn) triggerBtn.remove();
}

function translateOne(lang, name) {
    var formData = new FormData();
    formData.append('lang', lang);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '');
    return fetch(translateUrl, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success && data.segments) {
            applyTranslationUI(lang, name, data.segments);
            return;
        }
        throw new Error(data.error || 'فشل في الترجمة');
    });
}

function translateTranscription(lang, name) {
    var loadingEl = document.getElementById('transcriptionTranslateLoading');
    var modalEl = document.getElementById('translateLoadingModal');
    var modalTitle = document.getElementById('translateLoadingModalTitle');
    var modalSub = document.getElementById('translateLoadingModalSubtitle');
    var btns = document.querySelectorAll('.translation-trigger-btn');
    var btnAll = document.querySelector('.btn-translate-all-languages');
    if (loadingEl) loadingEl.classList.remove('d-none');
    if (modalEl) modalEl.style.display = 'flex';
    if (modalTitle) modalTitle.textContent = 'جاري الترجمة...';
    if (modalSub) modalSub.textContent = 'قد يستغرق ذلك دقيقة';
    btns.forEach(function(b) { b.disabled = true; });
    if (btnAll) btnAll.disabled = true;
    translateOne(lang, name)
    .then(function() {
        if (loadingEl) loadingEl.classList.add('d-none');
        if (modalEl) modalEl.style.display = 'none';
        btns.forEach(function(b) { b.disabled = false; });
        if (btnAll) btnAll.disabled = false;
    })
    .catch(function(err) {
        if (loadingEl) loadingEl.classList.add('d-none');
        if (modalEl) modalEl.style.display = 'none';
        btns.forEach(function(b) { b.disabled = false; });
        if (btnAll) btnAll.disabled = false;
        alert(err.message || 'فشل في الترجمة');
    });
}

async function translateAllLanguages() {
    var list = translationLanguagesToTranslate || [];
    if (list.length === 0) {
        alert('جميع اللغات مترجمة بالفعل');
        return;
    }
    var modalEl = document.getElementById('translateLoadingModal');
    var modalTitle = document.getElementById('translateLoadingModalTitle');
    var modalSub = document.getElementById('translateLoadingModalSubtitle');
    var btns = document.querySelectorAll('.translation-trigger-btn');
    var btnAll = document.querySelector('.btn-translate-all-languages');
    btns.forEach(function(b) { b.disabled = true; });
    if (btnAll) btnAll.disabled = true;
    if (modalEl) modalEl.style.display = 'flex';
    var total = list.length;
    for (var i = 0; i < list.length; i++) {
        var item = list[i];
        if (modalTitle) modalTitle.textContent = 'جاري الترجمة...';
        if (modalSub) modalSub.textContent = 'جاري الترجمة إلى ' + item.name + ' (' + (i + 1) + '/' + total + ')';
        try {
            await translateOne(item.code, item.name);
            translationLanguagesToTranslate = translationLanguagesToTranslate.filter(function(x) { return x.code !== item.code; });
        } catch (err) {
            alert(err.message || 'فشل في الترجمة إلى ' + item.name);
            break;
        }
    }
    if (modalEl) modalEl.style.display = 'none';
    btns.forEach(function(b) { b.disabled = false; });
    if (btnAll) btnAll.disabled = false;
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
                likeIcon.className = 'bi bi-hand-thumbs-up-fill';
                // Add animation effect
                likeBtn.style.animation = 'none';
                setTimeout(() => {
                    likeBtn.style.animation = 'likePulse 0.3s ease';
                }, 10);
            } else {
                likeBtn.classList.remove('active');
                likeIcon.className = 'bi bi-hand-thumbs-up';
            }
            likeCount.textContent = data.likes_count > 0 ? formatNumber(data.likes_count) : '';
            
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

// Format number function
function formatNumber(num) {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    } else if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toString();
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
            favoriteCount.textContent = data.favorites_count > 0 ? formatNumber(data.favorites_count) : '';
        }
    } catch (error) {
        console.error('Error toggling favorite:', error);
    }
}

function showLoginModal() {
    const authModal = new bootstrap.Modal(document.getElementById('authModal'));
    authModal.show();
}

// ——— التعليقات ———
document.addEventListener('DOMContentLoaded', function() {
    const commentsList = document.getElementById('commentsList');
    const commentsCountEl = document.getElementById('commentsCount');
    const emptyComments = document.getElementById('emptyComments');
    const commentForm = document.getElementById('commentForm');
    const commentInput = document.getElementById('commentInput');
    const commentSubmitBtn = document.getElementById('commentSubmitBtn');
    const commentCharCount = document.getElementById('commentCharCount');
    const getCommentsUrl = '{{ route("assets.get-comments", $asset) }}';
    const addCommentUrl = '{{ route("assets.add-comment", $asset) }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function renderComment(c) {
        var repliesHtml = (c.replies && c.replies.length) ? c.replies.map(function(r) {
            return '<div class="comment-item comment-reply"><div class="comment-header"><span class="comment-user">' + escapeHtml(r.user_name) + '</span><span class="comment-time">' + escapeHtml(r.created_at) + '</span></div><div class="comment-content">' + escapeHtml(r.content) + '</div></div>';
        }).join('') : '';
        return '<div class="comment-item" data-id="' + c.id + '"><div class="comment-header"><span class="comment-user">' + escapeHtml(c.user_name) + '</span><span class="comment-time">' + escapeHtml(c.created_at) + '</span></div><div class="comment-content">' + escapeHtml(c.content) + '</div>' + (repliesHtml ? '<div class="comment-replies">' + repliesHtml + '</div>' : '') + '</div>';
    }

    function loadComments() {
        if (!commentsList) return;
        fetch(getCommentsUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success && data.comments) {
                    commentsList.innerHTML = data.comments.map(renderComment).join('');
                    if (commentsCountEl) commentsCountEl.textContent = data.comments_count || data.comments.length;
                    if (emptyComments) emptyComments.classList.toggle('d-none', data.comments.length > 0);
                }
            })
            .catch(function() {
                commentsList.innerHTML = '<p class="text-muted small">تعذر تحميل التعليقات.</p>';
            });
    }

    if (commentCharCount && commentInput) {
        commentInput.addEventListener('input', function() {
            commentCharCount.textContent = this.value.length;
        });
    }

    if (commentForm && commentSubmitBtn) {
        commentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var content = commentInput ? commentInput.value.trim() : '';
            if (!content) return;
            if (!csrfToken) {
                alert('خطأ في الجلسة. حدّث الصفحة وحاول مرة أخرى.');
                return;
            }
            commentSubmitBtn.disabled = true;
            fetch(addCommentUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ content: content }),
                credentials: 'same-origin'
            })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success && data.comment) {
                        commentsList.insertAdjacentHTML('afterbegin', renderComment(data.comment));
                        if (commentInput) { commentInput.value = ''; if (commentCharCount) commentCharCount.textContent = '0'; }
                        var countEl = document.getElementById('commentsCount');
                        if (countEl) countEl.textContent = (parseInt(countEl.textContent, 10) || 0) + 1;
                        if (emptyComments) emptyComments.classList.add('d-none');
                    } else if (data.error) {
                        alert(data.error);
                    }
                })
                .catch(function() { alert('حدث خطأ أثناء الإرسال.'); })
                .finally(function() { commentSubmitBtn.disabled = false; });
        });
    }

    loadComments();
});
</script>
@endpush
