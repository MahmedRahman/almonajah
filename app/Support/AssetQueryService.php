<?php

namespace App\Support;

use App\Models\Asset;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class AssetQueryService
{
    public function publicVideoBaseQuery(): Builder
    {
        return Asset::query()->publishableUnderAssets()->videos();
    }

    public function publicAudioBaseQuery(): Builder
    {
        return Asset::query()->publishableUnderAssets()->audioPlatform();
    }

    public function applySearchFilter(Builder $query, ?string $search): void
    {
        $search = trim((string) $search);
        if ($search === '') {
            return;
        }

        $term = '%'.mb_strtolower($search).'%';
        $query->where(function (Builder $q) use ($term) {
            $q->whereRaw('LOWER(COALESCE(title,"")) LIKE ?', [$term])
                ->orWhereRaw('LOWER(COALESCE(file_name,"")) LIKE ?', [$term])
                ->orWhereRaw('LOWER(COALESCE(speaker_name,"")) LIKE ?', [$term])
                ->orWhereRaw('LOWER(COALESCE(site_description,"")) LIKE ?', [$term])
                ->orWhereRaw('LOWER(COALESCE(transcription_plain,"")) LIKE ?', [$term])
                ->orWhereRaw('LOWER(COALESCE(topics,"")) LIKE ?', [$term])
                ->orWhereHas('categories', function (Builder $categoryQuery) use ($term) {
                    $categoryQuery->whereRaw('LOWER(COALESCE(categories.name,"")) LIKE ?', [$term]);
                });
        });
    }

    public function applyCategoryFilter(Builder $query, ?string $categoryName, ?int $categoryId = null): ?Category
    {
        if ($categoryId) {
            $category = Category::query()
                ->where('show_on_site', true)
                ->whereKey($categoryId)
                ->first();

            if (! $category) {
                $query->whereRaw('0 = 1');

                return null;
            }

            $query->whereHas('categories', function (Builder $sub) use ($category) {
                $sub->where('categories.id', $category->id);
            });

            return $category;
        }

        $categoryName = trim((string) $categoryName);
        if ($categoryName === '') {
            return null;
        }

        $category = Category::query()
            ->where('show_on_site', true)
            ->where('name', $categoryName)
            ->first();

        $hasContentCategoryColumn = Schema::hasColumn((new Asset)->getTable(), 'content_category');

        if (! $category && ! $hasContentCategoryColumn) {
            $query->whereRaw('0 = 1');

            return null;
        }

        $query->where(function (Builder $q) use ($category, $categoryName, $hasContentCategoryColumn) {
            if ($category) {
                $q->whereHas('categories', function (Builder $sub) use ($category) {
                    $sub->where('categories.id', $category->id);
                });
            } elseif ($hasContentCategoryColumn) {
                $q->where('content_category', $categoryName);
            }
        });

        return $category;
    }

    public function applyYearFilter(Builder $query, ?string $year): void
    {
        $year = trim((string) $year);
        if ($year === '') {
            return;
        }

        $query->where(function (Builder $q) use ($year) {
            $q->where('relative_path', 'like', "%{$year}%")
                ->orWhere('year', $year);
        });
    }
}
