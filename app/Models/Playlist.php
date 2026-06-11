<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Playlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'sort_order',
        'title',
        'slug',
        'description',
        'image_path',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'sort_order' => 'integer',
    ];

    public function parent()
    {
        return $this->belongsTo(Playlist::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Playlist::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->orderBy('id');
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    public function descendantsCount(): int
    {
        $count = 0;
        foreach ($this->children as $child) {
            $count += 1 + $child->descendantsCount();
        }

        return $count;
    }

    /**
     * علاقة many-to-many مع جدول assets (مع عمود الترتيب في الـ pivot)
     */
    public function assets()
    {
        return $this->belongsToMany(Asset::class, 'asset_playlist')
            ->withPivot('order')
            ->orderByPivot('order')
            ->orderBy('assets.id');
    }

    public function publishedVideosQuery()
    {
        return $this->assets()->publishableUnderAssets()->videos();
    }

    public function hasPublishedContentInTree(): bool
    {
        if ($this->publishedVideosQuery()->exists()) {
            return true;
        }

        foreach ($this->children as $child) {
            if ($child->hasPublishedContentInTree()) {
                return true;
            }
        }

        return false;
    }

    public function totalPublishedVideosCount(): int
    {
        $this->loadMissing([
            'children' => function ($query) {
                $query->withCount(['assets' => fn ($q) => $q->publishableUnderAssets()->videos()])
                    ->with(['children' => fn ($childQuery) => $childQuery->withCount(['assets' => fn ($q) => $q->publishableUnderAssets()->videos()])]);
            },
        ]);

        if (! isset($this->assets_count)) {
            $this->loadCount(['assets' => fn ($q) => $q->publishableUnderAssets()->videos()]);
        }

        $total = (int) $this->assets_count;
        foreach ($this->children as $child) {
            $total += (int) ($child->assets_count ?? 0);
            foreach ($child->children as $grandchild) {
                $total += (int) ($grandchild->assets_count ?? 0);
            }
        }

        return $total;
    }
}
