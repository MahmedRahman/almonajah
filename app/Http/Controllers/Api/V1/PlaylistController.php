<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\AssetResource;
use App\Http\Resources\PlaylistResource;
use App\Models\Playlist;

class PlaylistController extends ApiController
{
    public function index()
    {
        $playlists = Playlist::query()
            ->withCount(['assets' => fn ($q) => $q->publishableUnderAssets()->videos()])
            ->whereHas('assets', fn ($q) => $q->publishableUnderAssets()->videos())
            ->orderBy('title')
            ->paginate($this->perPage());

        return $this->paginatedResponse($playlists, [
            'items' => PlaylistResource::collection($playlists->getCollection()),
        ]);
    }

    public function show(Playlist $playlist)
    {
        $assets = $playlist->assets()
            ->publishableUnderAssets()
            ->videos()
            ->with(['categories:id,name', 'optimizedVersions:id,asset_id,relative_path'])
            ->orderByPivot('order', 'asc')
            ->orderBy('assets.id', 'asc')
            ->paginate($this->perPage());

        return $this->paginatedResponse($assets, [
            'playlist' => new PlaylistResource($playlist),
            'items' => AssetResource::collection($assets->getCollection()),
        ]);
    }
}
