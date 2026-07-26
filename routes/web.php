<?php

use App\Http\Controllers\AudioController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContentItemController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

// Maintenance page - يجب أن يكون قبل middleware
Route::get('/maintenance', [\App\Http\Controllers\MaintenanceController::class, 'index'])->name('maintenance');

// Favicon: توجيه طلب المتصفح الافتراضي إلى الشعار لتجنب 404
Route::get('/favicon.ico', function () {
    return redirect(asset('images/logo.png'), 302);
});

Route::get('/swagger', function () {
    return view('swagger');
})->name('swagger');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.xml');

// Meta Conversions API beacon (browser → server → Meta), CSRF-exempt + throttled
Route::post('/meta/capi', [\App\Http\Controllers\MetaCapiController::class, 'store'])
    ->middleware('throttle:120,1')
    ->name('meta.capi');

// Public routes with maintenance and optional browser cache for public pages
Route::middleware(['maintenance', 'cache.public'])->group(function () {
    Route::get('/search/suggestions', [\App\Http\Controllers\HomeController::class, 'searchSuggestions'])->name('search.suggestions');
    Route::get('/', [\App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::get('/about', [\App\Http\Controllers\HomeController::class, 'about'])->name('public.about');
    Route::get('/portrait-videos', [\App\Http\Controllers\HomeController::class, 'portraitVideos'])->name('public.portrait-videos');
    Route::get('/shorts', [\App\Http\Controllers\HomeController::class, 'shorts'])->name('shorts');
    Route::get('/playlists', [\App\Http\Controllers\HomeController::class, 'playlists'])->name('public.playlists');
    Route::get('/playlist/{playlist}', [\App\Http\Controllers\HomeController::class, 'showPlaylist'])->name('public.playlist.show');
    Route::get('/scholars', [\App\Http\Controllers\HomeController::class, 'scholarsPublic'])->name('public.scholars');
    Route::get('/scholar/{scholar}', [\App\Http\Controllers\HomeController::class, 'showScholarPublic'])->name('public.scholar.show');
    Route::get('/audio', [AudioController::class, 'index'])->name('audio.home');
    Route::get('/audio/{asset}', [AudioController::class, 'show'])->name('audio.show');
    Route::get('/audio/{asset}/download', [\App\Http\Controllers\AssetController::class, 'downloadPublicAudio'])->name('assets.download.public-audio');

    Route::get('/video/{asset}', [\App\Http\Controllers\AssetController::class, 'showPublic'])->name('assets.show.public');
    Route::get('/video/{asset}/download', [\App\Http\Controllers\AssetController::class, 'downloadPublicVideo'])->name('assets.download.public-video');
    Route::get('/stream/video/{asset}', [\App\Http\Controllers\AssetController::class, 'streamPublic'])->name('assets.stream.public');
    Route::post('/video/{asset}/translate-transcription', [\App\Http\Controllers\AssetController::class, 'translateTranscription'])->name('assets.translate-transcription');
    Route::get('/video/{asset}/download-transcription', [\App\Http\Controllers\AssetController::class, 'downloadTranscription'])->name('assets.download-transcription');
    Route::get('/video/{asset}/download-transcription-all', [\App\Http\Controllers\AssetController::class, 'downloadTranscriptionAll'])->name('assets.download-transcription-all');
    Route::get('/live', [\App\Http\Controllers\HomeController::class, 'live'])->name('live');
    Route::get('/live/feed', [\App\Http\Controllers\HomeController::class, 'liveFeed'])->name('live.feed');

    Route::get('/hisana', function () {
        return view('landing.hisana');
    })->name('landing.hisana');
    Route::get('/حصانة', function () {
        return view('landing.hisana');
    })->name('landing.hisana.ar');
    Route::get('/hisana/privacy-policy', function () {
        return view('legal.hisana-privacy-policy');
    })->name('legal.hisana.privacy');
    Route::get('/حصانة/سياسة-الخصوصية', function () {
        return view('legal.hisana-privacy-policy');
    })->name('legal.hisana.privacy.ar');

    Route::get('/calm', [\App\Http\Controllers\CalmController::class, 'index'])->name('landing.calm');
    Route::get('/دعوة-غيب', [\App\Http\Controllers\CalmController::class, 'index'])->name('landing.calm.ar');
    Route::get('/اطمئن', [\App\Http\Controllers\CalmController::class, 'index'])->name('landing.calm.legacy');
    Route::post('/calm/match', [\App\Http\Controllers\CalmController::class, 'match'])->name('landing.calm.match');
    Route::post('/دعوة-غيب/match', [\App\Http\Controllers\CalmController::class, 'match'])->name('landing.calm.match.ar');

    Route::get('/privacy-policy', function () {
        return view('legal.privacy-policy');
    })->name('legal.privacy');
    Route::get('/terms-of-service', function () {
        return view('legal.terms-of-service');
    })->name('legal.terms');

    // User profile and favorites (requires authentication)
    Route::middleware('auth')->group(function () {
        Route::get('/profile', [\App\Http\Controllers\HomeController::class, 'profile'])->name('profile');
        Route::get('/favorites', [\App\Http\Controllers\HomeController::class, 'favorites'])->name('favorites');
        Route::get('/liked', [\App\Http\Controllers\HomeController::class, 'liked'])->name('liked');
    });

    // Like, Favorite, and Comments routes (require authentication)
    Route::middleware('auth')->group(function () {
        Route::post('/assets/{asset}/like', [\App\Http\Controllers\AssetController::class, 'toggleLike'])->name('assets.toggle-like');
        Route::post('/assets/{asset}/favorite', [\App\Http\Controllers\AssetController::class, 'toggleFavorite'])->name('assets.toggle-favorite');
        Route::post('/assets/{asset}/comments', [\App\Http\Controllers\AssetController::class, 'addComment'])->name('assets.add-comment');
        Route::delete('/comments/{comment}', [\App\Http\Controllers\AssetController::class, 'deleteComment'])->name('comments.delete');
    });

    // Get comments (public)
    Route::get('/assets/{asset}/comments', [\App\Http\Controllers\AssetController::class, 'getComments'])->name('assets.get-comments');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/register', [RegisterController::class, 'register'])->name('register');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'resetPasswordWeb'])->name('password.update');

// Google OAuth
Route::get('/auth/google', [\App\Http\Controllers\Auth\GoogleController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [\App\Http\Controllers\Auth\GoogleController::class, 'callback'])->name('google.callback');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/truncate-assets', [DashboardController::class, 'truncateAssets'])->name('dashboard.truncate-assets');

    // Content Management
    Route::resource('content', ContentItemController::class);

    // Categories
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('categories.show');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    Route::get('/categories/{category}/items', [CategoryController::class, 'items'])->name('categories.items');
    Route::post('/categories/{category}/reorder-assets', [CategoryController::class, 'reorderAssets'])->name('categories.reorder-assets');

    // Scholars (الشيوخ) - Admin
    Route::get('/admin/scholars', [\App\Http\Controllers\ScholarController::class, 'index'])->name('scholars.index');
    Route::post('/admin/scholars', [\App\Http\Controllers\ScholarController::class, 'store'])->name('scholars.store');
    Route::post('/admin/scholars/sidebar-visibility', [\App\Http\Controllers\ScholarController::class, 'updateSidebarVisibility'])->name('scholars.sidebar-visibility');
    Route::post('/admin/scholars/{scholar}/toggle-status', [\App\Http\Controllers\ScholarController::class, 'toggleStatus'])->name('scholars.toggle-status');
    Route::put('/admin/scholars/{scholar}', [\App\Http\Controllers\ScholarController::class, 'update'])->name('scholars.update');
    Route::delete('/admin/scholars/{scholar}', [\App\Http\Controllers\ScholarController::class, 'destroy'])->name('scholars.destroy');

    // Playlists (Admin)
    Route::get('/admin/playlists', [\App\Http\Controllers\PlaylistController::class, 'index'])->name('playlists.index');
    Route::post('/admin/playlists', [\App\Http\Controllers\PlaylistController::class, 'store'])->name('playlists.store');
    Route::put('/admin/playlists/{playlist}', [\App\Http\Controllers\PlaylistController::class, 'update'])->name('playlists.update');
    Route::get('/admin/playlists/{playlist}/items', [\App\Http\Controllers\PlaylistController::class, 'items'])->name('playlists.items');
    Route::post('/admin/playlists/{playlist}/reorder', [\App\Http\Controllers\PlaylistController::class, 'reorder'])->name('playlists.reorder');
    Route::post('/admin/playlists/{playlist}/toggle-visibility', [\App\Http\Controllers\PlaylistController::class, 'toggleVisibility'])->name('playlists.toggle-visibility');
    Route::delete('/admin/playlists/{playlist}', [\App\Http\Controllers\PlaylistController::class, 'destroy'])->name('playlists.destroy');

    // Banners (إعلانات)
    Route::resource('banners', \App\Http\Controllers\BannerController::class);

    // Media
    Route::get('/media', [MediaController::class, 'index'])->name('media.index');
    Route::post('/media', [MediaController::class, 'store'])->name('media.store');
    Route::delete('/media/{mediaFile}', [MediaController::class, 'destroy'])->name('media.destroy');

    // Users
    Route::get('/users', [\App\Http\Controllers\UserController::class, 'index'])->name('users.index');
    Route::post('/users', [\App\Http\Controllers\UserController::class, 'store'])->name('users.store');
    Route::post('/users/{user}/update-role', [\App\Http\Controllers\UserController::class, 'updateRole'])->name('users.update-role');

    // Assets (Videos)
    Route::get('/assets', [\App\Http\Controllers\AssetController::class, 'index'])->name('assets.index');
    Route::get('/assets/create', [\App\Http\Controllers\AssetController::class, 'create'])->name('assets.create');
    Route::post('/assets/scan', [\App\Http\Controllers\AssetController::class, 'scanFolder'])->name('assets.scan');
    Route::post('/assets/truncate', [\App\Http\Controllers\AssetController::class, 'truncateAll'])->name('assets.truncate');
    Route::post('/assets/remove-missing-sync', [\App\Http\Controllers\AssetController::class, 'removeMissingFromSync'])->name('assets.remove-missing-sync');
    Route::post('/assets/update-metadata', [\App\Http\Controllers\AssetController::class, 'updateFileMetadata'])->name('assets.update-metadata');
    Route::post('/assets/update-all-metadata', [\App\Http\Controllers\AssetController::class, 'updateAllFilesMetadata'])->name('assets.update-all-metadata');
    Route::post('/assets/scan-web-compatibility', [\App\Http\Controllers\AssetController::class, 'scanWebCompatibility'])->name('assets.scan-web-compatibility');
    Route::get('/assets/duplicates', [\App\Http\Controllers\AssetController::class, 'duplicates'])->name('assets.duplicates');
    Route::get('/assets/analytics', [\App\Http\Controllers\AssetController::class, 'analytics'])->name('assets.analytics');
    Route::get('/assets/import/browse', [\App\Http\Controllers\AssetController::class, 'importBrowse'])->name('assets.import.browse');
    Route::post('/assets/import/upload', [\App\Http\Controllers\AssetController::class, 'uploadImportVideo'])->name('assets.import.upload');
    Route::post('/assets/import', [\App\Http\Controllers\AssetController::class, 'importFromPath'])->name('assets.import');
    Route::get('/assets/{asset}/stream', [\App\Http\Controllers\AssetController::class, 'stream'])->name('assets.stream');
    Route::get('/assets/{asset}', [\App\Http\Controllers\AssetController::class, 'show'])->name('assets.show');
    Route::post('/assets/{asset}/extract', [\App\Http\Controllers\AssetController::class, 'extractMetadata'])->name('assets.extract');
    Route::post('/assets/{asset}/re-extract-metadata', [\App\Http\Controllers\AssetController::class, 'reExtractMetadata'])->name('assets.re-extract-metadata');
    Route::post('/assets/{asset}/update-site-description', [\App\Http\Controllers\AssetController::class, 'updateSiteDescription'])->name('assets.update-site-description');
    Route::post('/assets/{asset}/update-transcription', [\App\Http\Controllers\AssetController::class, 'updateTranscription'])->name('assets.update-transcription');
    Route::post('/assets/{asset}/update-transcription-segments', [\App\Http\Controllers\AssetController::class, 'updateTranscriptionSegments'])->name('assets.update-transcription-segments');
    Route::post('/assets/{asset}/upload-transcription-srt', [\App\Http\Controllers\AssetController::class, 'uploadTranscriptionSrt'])->name('assets.upload-transcription-srt');
    Route::post('/assets/{asset}/upload-translation-srt/{lang}', [\App\Http\Controllers\AssetController::class, 'uploadTranslationSrt'])->name('assets.upload-translation-srt');
    Route::post('/assets/{asset}/update-title', [\App\Http\Controllers\AssetController::class, 'updateTitle'])->name('assets.update-title');
    Route::post('/assets/{asset}/update-gregorian-year', [\App\Http\Controllers\AssetController::class, 'updateGregorianYear'])->name('assets.update-gregorian-year');
    Route::post('/assets/{asset}/update-show-translation', [\App\Http\Controllers\AssetController::class, 'updateShowTranslation'])->name('assets.update-show-translation');
    Route::post('/assets/{asset}/update-show-comments', [\App\Http\Controllers\AssetController::class, 'updateShowComments'])->name('assets.update-show-comments');
    Route::post('/assets/{asset}/update-production-date', [\App\Http\Controllers\AssetController::class, 'updateProductionDate'])->name('assets.update-production-date');
    Route::post('/assets/{asset}/update-speaker', [\App\Http\Controllers\AssetController::class, 'updateSpeaker'])->name('assets.update-speaker');
    Route::post('/assets/{asset}/update-category', [\App\Http\Controllers\AssetController::class, 'updateCategory'])->name('assets.update-category');
    Route::post('/assets/{asset}/update-content-category', [\App\Http\Controllers\AssetController::class, 'updateContentCategory'])->name('assets.update-content-category');
    Route::post('/assets/{asset}/update-playlists', [\App\Http\Controllers\AssetController::class, 'updatePlaylists'])->name('assets.update-playlists');
    Route::post('/assets/{asset}/update-publish-urls', [\App\Http\Controllers\AssetController::class, 'updatePublishUrls'])->name('assets.update-publish-urls');
    Route::post('/assets/{asset}/analyze', [\App\Http\Controllers\AssetController::class, 'analyzeContent'])->name('assets.analyze');
    Route::post('/assets/{asset}/transcribe', [\App\Http\Controllers\AssetController::class, 'transcribe'])->name('assets.transcribe');
    Route::get('/assets/{asset}/transcribe-status', [\App\Http\Controllers\AssetController::class, 'transcribeStatus'])->name('assets.transcribe-status');
    Route::post('/assets/{asset}/move', [\App\Http\Controllers\AssetController::class, 'moveFile'])->name('assets.move');
    Route::get('/assets/{asset}/open-folder', [\App\Http\Controllers\AssetController::class, 'openFolder'])->name('assets.open-folder');
    Route::post('/assets/{asset}/optimize-original', [\App\Http\Controllers\AssetController::class, 'startOptimizeOriginal'])->name('assets.optimize-original');
    Route::get('/assets/{asset}/optimize-original-status', [\App\Http\Controllers\AssetController::class, 'optimizeOriginalStatus'])->name('assets.optimize-original-status');
    Route::post('/assets/{asset}/convert-hls', [\App\Http\Controllers\AssetController::class, 'convertToHls'])->name('assets.convert-hls');
    Route::get('/assets/{asset}/hls-status', [\App\Http\Controllers\AssetController::class, 'hlsStatus'])->name('assets.hls-status');
    Route::post('/assets/{asset}/extract-audio', [\App\Http\Controllers\AssetController::class, 'extractAudio'])->name('assets.extract-audio');
    Route::get('/assets/{asset}/extract-audio-status', [\App\Http\Controllers\AssetController::class, 'extractAudioStatus'])->name('assets.extract-audio-status');
    Route::post('/assets/{asset}/upload-audio', [\App\Http\Controllers\AssetController::class, 'uploadAudio'])->name('assets.upload-audio');
    Route::post('/assets/{asset}/upload-thumbnail', [\App\Http\Controllers\AssetController::class, 'uploadThumbnail'])->name('assets.upload-thumbnail');
    Route::post('/assets/{asset}/upload-cover', [\App\Http\Controllers\AssetController::class, 'uploadCover'])->name('assets.upload-cover');
    Route::post('/assets/{asset}/capture-random-cover', [\App\Http\Controllers\AssetController::class, 'captureRandomCover'])->name('assets.capture-random-cover');
    Route::post('/assets/{asset}/set-web-video', [\App\Http\Controllers\AssetController::class, 'setWebVideo'])->name('assets.set-web-video');
    Route::post('/assets/bulk-publish', [\App\Http\Controllers\AssetController::class, 'bulkPublish'])->name('assets.bulk-publish');
    Route::post('/assets/bulk-unpublish', [\App\Http\Controllers\AssetController::class, 'bulkUnpublish'])->name('assets.bulk-unpublish');
    Route::post('/assets/bulk-update-settings', [\App\Http\Controllers\AssetController::class, 'bulkUpdateSettings'])->name('assets.bulk-update-settings');
    Route::post('/assets/bulk-rename-titles', [\App\Http\Controllers\AssetController::class, 'bulkRenameTitles'])->name('assets.bulk-rename-titles');
    Route::post('/assets/merge', [\App\Http\Controllers\AssetController::class, 'merge'])->name('assets.merge');
    Route::post('/assets/bulk-delete', [\App\Http\Controllers\AssetController::class, 'bulkDelete'])->name('assets.bulk-delete');
    Route::post('/assets/delete-unpublished', [\App\Http\Controllers\AssetController::class, 'deleteAllUnpublished'])->name('assets.delete-unpublished');
    Route::post('/assets/{asset}/mark-published', [\App\Http\Controllers\AssetController::class, 'markPublished'])->name('assets.mark-published');
    Route::post('/assets/{asset}/toggle-publishable', [\App\Http\Controllers\AssetController::class, 'togglePublishable'])->name('assets.toggle-publishable');
    Route::post('/assets/{asset}/toggle-featured', [\App\Http\Controllers\AssetController::class, 'toggleFeatured'])->name('assets.toggle-featured');
    Route::post('/assets/{asset}/update-featured-order', [\App\Http\Controllers\AssetController::class, 'updateFeaturedOrder'])->name('assets.update-featured-order');
    Route::post('/assets/{asset}/schedule-publish', [\App\Http\Controllers\AssetController::class, 'schedulePublish'])->name('assets.schedule-publish');
    Route::delete('/assets/{asset}', [\App\Http\Controllers\AssetController::class, 'destroy'])->name('assets.destroy');
    Route::get('/assets-stats', [\App\Http\Controllers\AssetController::class, 'stats'])->name('assets.stats');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/social-links', [SettingsController::class, 'updateSocialLinks'])->name('settings.social-links');
    Route::post('/settings/maintenance-mode', [SettingsController::class, 'updateMaintenanceMode'])->name('settings.maintenance-mode');
});
