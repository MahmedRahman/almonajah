<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\AudioTrackResource;
use App\Support\AssetQueryService;
use Illuminate\Http\Request;

class AudioController extends ApiController
{
    public function __construct(private readonly AssetQueryService $assets) {}

    public function home(Request $request)
    {
        return $this->tracks($request);
    }

    public function tracks(Request $request)
    {
        $query = $this->assets->publicAudioBaseQuery()->with([
            'categories:id,name',
            'audioFiles:id,asset_id,format,duration_seconds,file_path',
            'optimizedVersions:id,asset_id,relative_path',
        ]);
        $this->assets->applySearchFilter($query, $request->query('q'));
        $category = $this->assets->applyCategoryFilter(
            $query,
            $request->query('category_name'),
            $request->integer('category_id') ?: null
        );
        $this->assets->applyYearFilter($query, $request->query('year'));

        if ($category) {
            $query->orderByRaw('(SELECT ac.`order` FROM asset_category ac WHERE ac.asset_id = assets.id AND ac.category_id = ?) ASC', [$category->id])
                ->orderBy('assets.id', 'asc');
        } else {
            $query->orderByRaw('published_at IS NULL ASC')->orderByDesc('published_at')->orderBy('assets.id', 'desc');
        }

        $paginator = $query->paginate($this->perPage());

        return $this->paginatedResponse($paginator, [
            'items' => AudioTrackResource::collection($paginator->getCollection()),
        ]);
    }

    public function showTrack(int $assetId)
    {
        $asset = $this->assets->publicAudioBaseQuery()
            ->with([
                'categories:id,name',
                'audioFiles:id,asset_id,format,duration_seconds,file_path',
                'hlsVersions',
                'optimizedVersions:id,asset_id,relative_path',
            ])
            ->findOrFail($assetId);

        return $this->successResponse([
            'item' => new AudioTrackResource($asset),
        ]);
    }
}
