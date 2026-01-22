<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ContentItemController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

// Maintenance page - يجب أن يكون قبل middleware
Route::get('/maintenance', [\App\Http\Controllers\MaintenanceController::class, 'index'])->name('maintenance');

// Public routes with maintenance middleware
Route::middleware('maintenance')->group(function () {
    Route::get('/', [\App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::get('/shorts', [\App\Http\Controllers\HomeController::class, 'shorts'])->name('shorts');
    Route::get('/video/{asset}', [\App\Http\Controllers\AssetController::class, 'showPublic'])->name('assets.show.public');
    
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
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Media
    Route::get('/media', [MediaController::class, 'index'])->name('media.index');
    Route::post('/media', [MediaController::class, 'store'])->name('media.store');
    Route::delete('/media/{mediaFile}', [MediaController::class, 'destroy'])->name('media.destroy');

    // Users
    Route::get('/users', [\App\Http\Controllers\UserController::class, 'index'])->name('users.index');
    Route::post('/users/{user}/update-role', [\App\Http\Controllers\UserController::class, 'updateRole'])->name('users.update-role');

    // Assets (Videos)
    Route::get('/assets', [\App\Http\Controllers\AssetController::class, 'index'])->name('assets.index');
    Route::post('/assets/scan', [\App\Http\Controllers\AssetController::class, 'scanFolder'])->name('assets.scan');
    Route::post('/assets/update-metadata', [\App\Http\Controllers\AssetController::class, 'updateFileMetadata'])->name('assets.update-metadata');
    Route::post('/assets/update-all-metadata', [\App\Http\Controllers\AssetController::class, 'updateAllFilesMetadata'])->name('assets.update-all-metadata');
    Route::get('/assets/duplicates', [\App\Http\Controllers\AssetController::class, 'duplicates'])->name('assets.duplicates');
    Route::get('/assets/analytics', [\App\Http\Controllers\AssetController::class, 'analytics'])->name('assets.analytics');
    Route::get('/assets/{asset}', [\App\Http\Controllers\AssetController::class, 'show'])->name('assets.show');
    Route::post('/assets/{asset}/extract', [\App\Http\Controllers\AssetController::class, 'extractMetadata'])->name('assets.extract');
    Route::post('/assets/{asset}/re-extract-metadata', [\App\Http\Controllers\AssetController::class, 'reExtractMetadata'])->name('assets.re-extract-metadata');
    Route::post('/assets/{asset}/update-site-description', [\App\Http\Controllers\AssetController::class, 'updateSiteDescription'])->name('assets.update-site-description');
    Route::post('/assets/{asset}/update-transcription', [\App\Http\Controllers\AssetController::class, 'updateTranscription'])->name('assets.update-transcription');
    Route::post('/assets/{asset}/update-title', [\App\Http\Controllers\AssetController::class, 'updateTitle'])->name('assets.update-title');
    Route::post('/assets/{asset}/update-category', [\App\Http\Controllers\AssetController::class, 'updateCategory'])->name('assets.update-category');
    Route::post('/assets/{asset}/update-content-category', [\App\Http\Controllers\AssetController::class, 'updateContentCategory'])->name('assets.update-content-category');
    Route::post('/assets/{asset}/analyze', [\App\Http\Controllers\AssetController::class, 'analyzeContent'])->name('assets.analyze');
    Route::post('/assets/{asset}/transcribe', [\App\Http\Controllers\AssetController::class, 'transcribe'])->name('assets.transcribe');
    Route::get('/assets/{asset}/transcribe-status', [\App\Http\Controllers\AssetController::class, 'transcribeStatus'])->name('assets.transcribe-status');
    Route::post('/assets/{asset}/move', [\App\Http\Controllers\AssetController::class, 'moveFile'])->name('assets.move');
    Route::get('/assets/{asset}/open-folder', [\App\Http\Controllers\AssetController::class, 'openFolder'])->name('assets.open-folder');
    Route::post('/assets/{asset}/convert-hls', [\App\Http\Controllers\AssetController::class, 'convertToHls'])->name('assets.convert-hls');
    Route::get('/assets/{asset}/hls-status', [\App\Http\Controllers\AssetController::class, 'hlsStatus'])->name('assets.hls-status');
    Route::post('/assets/{asset}/extract-audio', [\App\Http\Controllers\AssetController::class, 'extractAudio'])->name('assets.extract-audio');
    Route::get('/assets/{asset}/extract-audio-status', [\App\Http\Controllers\AssetController::class, 'extractAudioStatus'])->name('assets.extract-audio-status');
    Route::post('/assets/{asset}/upload-thumbnail', [\App\Http\Controllers\AssetController::class, 'uploadThumbnail'])->name('assets.upload-thumbnail');
    Route::post('/assets/{asset}/toggle-publishable', [\App\Http\Controllers\AssetController::class, 'togglePublishable'])->name('assets.toggle-publishable');
    Route::delete('/assets/{asset}', [\App\Http\Controllers\AssetController::class, 'destroy'])->name('assets.destroy');
    Route::get('/assets-stats', [\App\Http\Controllers\AssetController::class, 'stats'])->name('assets.stats');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/social-links', [SettingsController::class, 'updateSocialLinks'])->name('settings.social-links');
    Route::post('/settings/maintenance-mode', [SettingsController::class, 'updateMaintenanceMode'])->name('settings.maintenance-mode');
});

