<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CachePublicResponse
{
    /** Cache for 5 minutes for anonymous public pages. */
    private const MAX_AGE = 300;

    /**
     * Paths that may receive public cache headers (GET only).
     * Excluded: /profile, /favorites, /liked (user-specific).
     */
    private function isCacheablePath(string $path): bool
    {
        $path = '/' . ltrim($path, '/');
        if ($path === '/' && request()->has('search') && trim((string) request('search')) !== '') {
            return false;
        }
        $cacheable = [
            '/',
            '/shorts',
            '/playlists',
            '/live',
            '/',
            '/shorts',
            '/playlists',
            '/live',
            '/hisana',
            '/hisana/privacy-policy',
            '/calm',
            '/adab-itama',
        ];
        if (in_array($path, $cacheable, true)) {
            return true;
        }
        if (preg_match('#^/playlist/#', $path)) {
            return true;
        }
        if (preg_match('#^/scholar/#', $path) || $path === '/scholars') {
            return true;
        }
        if (preg_match('#^/video/#', $path)) {
            return true;
        }
        if (preg_match('#^/حصانة$#u', $path)) {
            return true;
        }
        if (preg_match('#^/(اطمئن|دعوة-غيب|آداب-الإطعام)$#u', $path)) {
            return true;
        }
        return false;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->method() !== 'GET') {
            return $response;
        }

        if (!$this->isCacheablePath($request->path())) {
            return $response;
        }

        // عدم تخزين الصفحة في المتصفح عندما المستخدم مسجّل دخول (المحتوى يعتمد على الحساب)
        if (Auth::check()) {
            $response->headers->set('Cache-Control', 'private, no-store, must-revalidate');
        } else {
            $response->headers->set('Cache-Control', 'public, max-age=' . self::MAX_AGE);
        }

        return $response;
    }
}
