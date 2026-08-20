<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Playlist;
use App\Models\Scholar;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = [];

        $add = function (string $loc, string $changefreq = 'weekly', string $priority = '0.7', ?string $lastmod = null) use (&$urls): void {
            $urls[] = [
                'loc' => $loc,
                'changefreq' => $changefreq,
                'priority' => $priority,
                'lastmod' => $lastmod,
            ];
        };

        $add(route('home'), 'daily', '1.0');
        $add(route('shorts'), 'daily', '0.9');
        $add(route('audio.home'), 'daily', '0.9');
        $add(route('public.playlists'), 'weekly', '0.8');
        $add(route('public.scholars'), 'weekly', '0.8');
        $add(route('live'), 'daily', '0.7');
        $add(route('public.about'), 'monthly', '0.5');
        $add(route('public.portrait-videos'), 'daily', '0.8');
        $add(route('legal.privacy'), 'monthly', '0.3');
        $add(route('legal.terms'), 'monthly', '0.3');
        $add(route('landing.hisana'), 'monthly', '0.5');
        $add(route('legal.hisana.privacy'), 'monthly', '0.4');
        $add(route('landing.calm'), 'weekly', '0.7');
        $add(route('landing.itama'), 'monthly', '0.6');
        $add(route('landing.table-moment'), 'monthly', '0.6');

        Playlist::query()
            ->whereHas('assets', fn ($q) => $q->publishableUnderAssets()->videos())
            ->select('id', 'updated_at')
            ->orderBy('id')
            ->get()
            ->each(function (Playlist $playlist) use ($add): void {
                $add(
                    route('public.playlist.show', $playlist),
                    'weekly',
                    '0.7',
                    optional($playlist->updated_at)->toAtomString()
                );
            });

        Scholar::query()
            ->where('status', 'active')
            ->whereHas('assets', fn ($q) => $q->publishableUnderAssets()->videos())
            ->select('id', 'updated_at')
            ->orderBy('id')
            ->get()
            ->each(function (Scholar $scholar) use ($add): void {
                $add(
                    route('public.scholar.show', $scholar),
                    'weekly',
                    '0.7',
                    optional($scholar->updated_at)->toAtomString()
                );
            });

        Asset::query()
            ->publishableUnderAssets()
            ->videos()
            ->select('id', 'published_at', 'updated_at')
            ->orderByDesc('id')
            ->get()
            ->each(function (Asset $asset) use ($add): void {
                $add(
                    route('assets.show.public', $asset),
                    'weekly',
                    '0.6',
                    optional($asset->published_at ?? $asset->updated_at)->toAtomString()
                );
            });

        $xml = view('sitemap.xml', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
