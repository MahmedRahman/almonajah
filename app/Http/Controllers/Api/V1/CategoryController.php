<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\CategoryResource;
use App\Models\Category;

class CategoryController extends ApiController
{
    public function index()
    {
        $categories = Category::query()
            ->where('show_on_site', true)
            ->withCount('assets')
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        return $this->successResponse([
            'items' => CategoryResource::collection($categories),
        ]);
    }
}
