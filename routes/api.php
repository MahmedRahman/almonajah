<?php

use App\Http\Controllers\Api\V1\AssetController;
use App\Http\Controllers\Api\V1\AudioController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\FeedController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\PlaylistController;
use App\Http\Controllers\Api\V1\ScholarController;
use App\Http\Controllers\Api\V1\SearchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/google', [AuthController::class, 'google']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
    });

    Route::get('/videos', [HomeController::class, 'index'])->name('api.v1.videos');
    Route::get('/home', [HomeController::class, 'index']);
    Route::get('/categories', [CategoryController::class, 'index']);

    Route::get('/search', [SearchController::class, 'index']);
    Route::get('/search/suggestions', [SearchController::class, 'suggestions']);

    Route::get('/assets/{asset}', [AssetController::class, 'show']);
    Route::get('/assets/{asset}/related', [AssetController::class, 'related']);
    Route::get('/assets/{asset}/comments', [AssetController::class, 'comments']);

    Route::prefix('audio')->group(function () {
        Route::get('/home', [AudioController::class, 'home']);
        Route::get('/tracks', [AudioController::class, 'tracks']);
        Route::get('/tracks/{assetId}', [AudioController::class, 'showTrack']);
    });

    Route::get('/playlists', [PlaylistController::class, 'index']);
    Route::get('/playlists/{playlist}', [PlaylistController::class, 'show']);

    Route::get('/scholars', [ScholarController::class, 'index']);
    Route::get('/scholars/{scholar}', [ScholarController::class, 'show']);

    Route::get('/shorts', [FeedController::class, 'shorts']);
    Route::get('/live/feed', [FeedController::class, 'liveFeed']);
});
