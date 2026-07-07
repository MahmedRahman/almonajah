<?php

namespace App\Http\Controllers;

use App\Models\Playlist;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PlaylistController extends Controller
{
    public function index()
    {
        $playlists = Playlist::withCount(['assets', 'children'])
            ->with(['children' => function ($query) {
                $this->loadNestedChildren($query);
            }])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->orderBy('id')
            ->paginate(15);

        return view('playlists.index', compact('playlists'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|integer|exists:playlists,id',
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:playlists',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_visible' => 'nullable|boolean',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);
        $parentId = ! empty($validated['parent_id']) ? (int) $validated['parent_id'] : null;
        $validated['parent_id'] = $parentId;
        $validated['sort_order'] = $this->nextSortOrderForParent($parentId);
        $validated['is_visible'] = $request->boolean('is_visible', true);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('playlists', 'public');
            $validated['image_path'] = $imagePath;
        }

        unset($validated['image']);

        Playlist::create($validated);
        Playlist::forgetRootLookupCache();

        $message = $parentId
            ? 'تم إنشاء القائمة الفرعية بنجاح'
            : 'تم إنشاء قائمة التشغيل بنجاح';

        return redirect()->route('playlists.index')
            ->with('success', $message);
    }

    public function update(Request $request, Playlist $playlist)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:playlists,slug,' . $playlist->id,
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_visible' => 'nullable|boolean',
        ]);

        $validated['is_visible'] = $request->boolean('is_visible', true);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($playlist->image_path && Storage::disk('public')->exists($playlist->image_path)) {
                Storage::disk('public')->delete($playlist->image_path);
            }
            
            $imagePath = $request->file('image')->store('playlists', 'public');
            $validated['image_path'] = $imagePath;
        }

        // Remove image from validated array as it's not a database column
        unset($validated['image']);

        $playlist->update($validated);
        Playlist::forgetRootLookupCache();

        return redirect()->route('playlists.index')
            ->with('success', 'تم تحديث قائمة التشغيل بنجاح');
    }

    public function toggleVisibility(Playlist $playlist)
    {
        $playlist->update(['is_visible' => ! $playlist->is_visible]);
        Playlist::forgetRootLookupCache();

        $message = $playlist->is_visible
            ? 'تم إظهار قائمة التشغيل'
            : 'تم إخفاء قائمة التشغيل';

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_visible' => $playlist->is_visible,
                'message' => $message,
            ]);
        }

        return redirect()->route('playlists.index')->with('success', $message);
    }

    public function destroy(Playlist $playlist)
    {
        $playlist->load(['children' => function ($query) {
            $this->loadNestedChildren($query);
        }]);
        $descendants = $playlist->descendantsCount();
        $this->deletePlaylistImagesRecursively($playlist);
        $playlist->delete();
        Playlist::forgetRootLookupCache();

        $message = $descendants > 0
            ? 'تم حذف قائمة التشغيل و'.$descendants.' قائمة فرعية بنجاح'
            : 'تم حذف قائمة التشغيل بنجاح';

        return redirect()->route('playlists.index')
            ->with('success', $message);
    }

    private function loadNestedChildren($query): void
    {
        $query->withCount(['assets', 'children'])
            ->with(['children' => function ($childQuery) {
                $this->loadNestedChildren($childQuery);
            }])
            ->orderBy('sort_order')
            ->orderBy('title')
            ->orderBy('id');
    }

    private function nextSortOrderForParent(?int $parentId): int
    {
        $max = Playlist::where('parent_id', $parentId)->max('sort_order');

        return is_null($max) ? 0 : ((int) $max + 1);
    }

    private function deletePlaylistImagesRecursively(Playlist $playlist): void
    {
        foreach ($playlist->children as $child) {
            $this->deletePlaylistImagesRecursively($child);
        }

        if ($playlist->image_path && Storage::disk('public')->exists($playlist->image_path)) {
            Storage::disk('public')->delete($playlist->image_path);
        }
    }

    /**
     * جلب ملفات قائمة التشغيل بالترتيب الحالي (للعرض في واجهة الترتيب).
     */
    public function items(Playlist $playlist)
    {
        $assets = $playlist->assets()
            ->select('assets.id', 'assets.file_name', 'assets.title', 'assets.thumbnail_path', 'assets.duration_seconds')
            ->get();
        $items = $assets->map(function ($asset) {
            $duration = $asset->duration_seconds
                ? sprintf('%d:%02d', floor($asset->duration_seconds / 60), $asset->duration_seconds % 60)
                : null;
            return [
                'id' => $asset->id,
                'title' => $asset->title ?: $asset->file_name,
                'duration' => $duration,
            ];
        });
        return response()->json(['items' => $items]);
    }

    /**
     * إعادة ترتيب ملفات قائمة التشغيل (يُستدعى عبر AJAX).
     * body: { asset_ids: [id1, id2, ...] }
     */
    public function reorder(Request $request, Playlist $playlist)
    {
        $request->validate([
            'asset_ids' => 'required|array',
            'asset_ids.*' => 'integer|exists:assets,id',
        ]);
        $assetIds = $request->input('asset_ids');
        $existingIds = $playlist->assets()->pluck('assets.id')->toArray();
        $validIds = array_values(array_intersect($assetIds, $existingIds));
        foreach ($validIds as $position => $assetId) {
            \Illuminate\Support\Facades\DB::table('asset_playlist')
                ->where('playlist_id', $playlist->id)
                ->where('asset_id', $assetId)
                ->update(['order' => $position]);
        }
        return response()->json(['success' => true, 'message' => 'تم تحديث الترتيب بنجاح']);
    }
}
