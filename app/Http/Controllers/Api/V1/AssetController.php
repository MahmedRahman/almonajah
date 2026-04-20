<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\AssetResource;
use App\Http\Resources\CommentResource;
use App\Models\Asset;

class AssetController extends ApiController
{
    public function show(Asset $asset)
    {
        abort_unless(
            $asset->relative_path && str_starts_with($asset->relative_path, 'assets/') && $asset->is_publishable,
            404
        );

        $asset->load([
            'categories:id,name',
            'hlsVersions',
            'audioFiles',
            'optimizedVersions:id,asset_id,relative_path',
        ]);

        return $this->successResponse([
            'item' => new AssetResource($asset),
        ]);
    }

    public function related(Asset $asset)
    {
        abort_unless(
            $asset->relative_path && str_starts_with($asset->relative_path, 'assets/') && $asset->is_publishable,
            404
        );

        $asset->load('categories:id,name');
        $relatedQuery = Asset::query()
            ->publishableUnderAssets()
            ->where('id', '!=', $asset->id)
            ->with(['categories:id,name', 'optimizedVersions:id,asset_id,relative_path']);

        $relatedQuery->where(function ($q) use ($asset) {
            if ($asset->speaker_name) {
                $q->where('speaker_name', $asset->speaker_name);
            }

            $categoryIds = $asset->categories->pluck('id');
            if ($categoryIds->isNotEmpty()) {
                $q->orWhereHas('categories', fn ($cq) => $cq->whereIn('categories.id', $categoryIds));
            }
        });

        $paginator = $relatedQuery
            ->orderByRaw('published_at IS NULL ASC')
            ->orderByDesc('published_at')
            ->orderBy('assets.id', 'desc')
            ->paginate($this->perPage(12));

        return $this->paginatedResponse($paginator, [
            'items' => AssetResource::collection($paginator->getCollection()),
        ]);
    }

    public function comments(Asset $asset)
    {
        abort_unless($asset->is_publishable, 404);

        $comments = $asset->comments()
            ->with(['user:id,name', 'replies.user:id,name'])
            ->paginate($this->perPage(20));

        return $this->paginatedResponse($comments, [
            'items' => CommentResource::collection($comments->getCollection()),
        ]);
    }
}
