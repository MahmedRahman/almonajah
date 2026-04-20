<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\AssetResource;
use App\Models\Asset;
use App\Support\AssetQueryService;
use Illuminate\Http\Request;

class SearchController extends ApiController
{
    public function __construct(private readonly AssetQueryService $assets) {}

    public function index(Request $request)
    {
        $type = $request->query('type');
        $query = $type === 'audio'
            ? $this->assets->publicAudioBaseQuery()
            : $this->assets->publicVideoBaseQuery();

        $query->with([
            'categories:id,name',
            'optimizedVersions:id,asset_id,relative_path',
        ]);
        $this->assets->applySearchFilter($query, $request->query('q'));
        $query->orderByRaw('published_at IS NULL ASC')->orderByDesc('published_at')->orderBy('assets.id', 'desc');

        $paginator = $query->paginate($this->perPage());

        return $this->paginatedResponse($paginator, [
            'items' => AssetResource::collection($paginator->getCollection()),
        ], [
            'filters' => [
                'q' => (string) $request->query('q', ''),
                'type' => $type,
            ],
        ]);
    }

    public function suggestions(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return $this->successResponse(['items' => []]);
        }

        $isAudio = $request->query('type') === 'audio';
        $query = $isAudio ? $this->assets->publicAudioBaseQuery() : $this->assets->publicVideoBaseQuery();
        $this->assets->applySearchFilter($query, $q);
        $items = $query->select('id', 'title', 'file_name', 'speaker_name', 'thumbnail_path')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get()
            ->map(fn (Asset $asset) => [
                'id' => $asset->id,
                'title' => $asset->title ?: $asset->file_name,
                'speaker_name' => $asset->speaker_name,
                'thumbnail_path' => $asset->thumbnail_path,
            ]);

        return $this->successResponse(['items' => $items]);
    }
}
