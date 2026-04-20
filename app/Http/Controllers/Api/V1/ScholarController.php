<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\AssetResource;
use App\Http\Resources\ScholarResource;
use App\Models\Scholar;

class ScholarController extends ApiController
{
    public function index()
    {
        $scholars = Scholar::query()
            ->where('status', 'active')
            ->withCount(['assets' => fn ($q) => $q->publishableUnderAssets()->videos()])
            ->whereHas('assets', fn ($q) => $q->publishableUnderAssets()->videos())
            ->orderBy('order')
            ->orderBy('name')
            ->paginate($this->perPage());

        return $this->paginatedResponse($scholars, [
            'items' => ScholarResource::collection($scholars->getCollection()),
        ]);
    }

    public function show(Scholar $scholar)
    {
        $assets = $scholar->assets()
            ->publishableUnderAssets()
            ->videos()
            ->with(['categories:id,name', 'optimizedVersions:id,asset_id,relative_path'])
            ->orderByDesc('assets.id')
            ->paginate($this->perPage());

        return $this->paginatedResponse($assets, [
            'scholar' => new ScholarResource($scholar),
            'items' => AssetResource::collection($assets->getCollection()),
        ]);
    }
}
