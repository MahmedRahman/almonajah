<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\AssetResource;
use App\Models\Asset;

class FeedController extends ApiController
{
    public function shorts()
    {
        $shorts = Asset::query()
            ->publishableUnderAssets()
            ->videos()
            ->where('orientation', 'portrait')
            ->with(['categories:id,name', 'hlsVersions', 'optimizedVersions:id,asset_id,relative_path'])
            ->orderByDesc('id')
            ->paginate($this->perPage());

        return $this->paginatedResponse($shorts, [
            'items' => AssetResource::collection($shorts->getCollection()),
        ]);
    }

    public function liveFeed()
    {
        $live = Asset::query()
            ->publishableUnderAssets()
            ->videos()
            ->where('orientation', 'landscape')
            ->with(['categories:id,name', 'optimizedVersions:id,asset_id,relative_path'])
            ->inRandomOrder()
            ->paginate($this->perPage(30));

        return $this->paginatedResponse($live, [
            'items' => AssetResource::collection($live->getCollection()),
        ]);
    }
}
