<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\AssetResource;
use App\Http\Resources\BannerResource;
use App\Http\Resources\CategoryResource;
use App\Models\Banner;
use App\Models\Category;
use App\Support\AssetQueryService;
use Illuminate\Http\Request;

class HomeController extends ApiController
{
    public function __construct(private readonly AssetQueryService $assets) {}

    public function index(Request $request)
    {
        $query = $this->assets->publicVideoBaseQuery()->with([
            'categories:id,name',
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
        $assets = AssetResource::collection($paginator->getCollection());

        $categories = Category::query()
            ->where('show_on_site', true)
            ->withCount(['assets' => fn ($q) => $q->publishableUnderAssets()->videos()])
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        $banners = Banner::query()
            ->active()
            ->forPlacement(Banner::PLACEMENT_HOME)
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        return $this->paginatedResponse($paginator, [
            'items' => $assets,
            'categories' => CategoryResource::collection($categories),
            'banners' => BannerResource::collection($banners),
        ]);
    }
}
