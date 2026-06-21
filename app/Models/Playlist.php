<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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

    /**
     * معرّفات هذه القائمة وجميع الفروع (بالترتيب: الأب ثم الأبناء ثم الأحفاد).
     */
    public function descendantPlaylistIdsInOrder(): array
    {
        $this->loadMissing([
            'children' => function ($query) {
                $query->orderBy('sort_order')->orderBy('title')->orderBy('id')
                    ->with(['children' => function ($childQuery) {
                        $childQuery->orderBy('sort_order')->orderBy('title')->orderBy('id');
                    }]);
            },
        ]);

        $ids = [$this->id];
        foreach ($this->children as $child) {
            $ids[] = $child->id;
            foreach ($child->children as $grandchild) {
                $ids[] = $grandchild->id;
            }
        }

        return $ids;
    }

    public static function indexedForRootLookup()
    {
        return Cache::remember('playlist_root_nodes', 3600, function () {
            return static::query()
                ->select('id', 'parent_id', 'title', 'slug', 'image_path')
                ->get()
                ->keyBy('id');
        });
    }

    public static function forgetRootLookupCache(): void
    {
        Cache::forget('playlist_root_nodes');
        Cache::forget('playlist_root_id_map');
    }

    public static function findRootPlaylist(int $playlistId): ?self
    {
        $byId = static::indexedForRootLookup();
        if (! $byId->has($playlistId)) {
            return null;
        }

        $current = $byId[$playlistId];
        while ($current->parent_id && $byId->has($current->parent_id)) {
            $current = $byId[$current->parent_id];
        }

        return $current;
    }
}
