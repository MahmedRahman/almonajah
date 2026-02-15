<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('assets')
            ->orderBy('order')
            ->orderBy('name')
            ->paginate(15);

        return view('categories.index', compact('categories'));
    }

    public function show(Category $category)
    {
        $assets = $category->assets()
            ->select('assets.id', 'assets.title', 'assets.file_name', 'assets.original_path', 'assets.relative_path', 'assets.is_publishable', 'assets.thumbnail_path')
            ->orderByPivot('order')
            ->orderBy('assets.id')
            ->get();

        $coverImageUrl = null;
        if ($category->image_path && Storage::disk('public')->exists($category->image_path)) {
            $coverImageUrl = asset('storage/' . $category->image_path);
        } elseif ($assets->isNotEmpty()) {
            $first = $assets->first();
            if ($first->thumbnail_path && Storage::disk('public')->exists($first->thumbnail_path)) {
                $coverImageUrl = asset('storage/' . $first->thumbnail_path);
            }
        }

        return view('categories.show', compact('category', 'assets', 'coverImageUrl'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'show_on_site' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['show_on_site'] = $request->boolean('show_on_site');
        $validated['order'] = $validated['order'] ?? 0;

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('categories', 'public');
            $validated['image_path'] = $imagePath;
        }

        // Remove image from validated array as it's not a database column
        unset($validated['image']);

        Category::create($validated);

        // تحديث القائمة الجانبية "استكشاف" في الصفحة الرئيسية
        Cache::forget('home_categories');
        Cache::forget('home_content_categories');

        return redirect()->route('categories.index')
            ->with('success', 'تم إنشاء التصنيف بنجاح');
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug,' . $category->id,
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'show_on_site' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        $validated['show_on_site'] = $request->boolean('show_on_site');
        $validated['order'] = $validated['order'] ?? $category->order ?? 0;

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($category->image_path && Storage::disk('public')->exists($category->image_path)) {
                Storage::disk('public')->delete($category->image_path);
            }
            
            $imagePath = $request->file('image')->store('categories', 'public');
            $validated['image_path'] = $imagePath;
        }

        // Remove image from validated array as it's not a database column
        unset($validated['image']);

        $category->update($validated);

        // تحديث القائمة الجانبية "استكشاف" في الصفحة الرئيسية (الاسم واللوجو)
        Cache::forget('home_categories');
        Cache::forget('home_content_categories');

        return redirect()->route('categories.index')
            ->with('success', 'تم تحديث التصنيف بنجاح');
    }

    public function destroy(Category $category)
    {
        // Delete image if exists
        if ($category->image_path && Storage::disk('public')->exists($category->image_path)) {
            Storage::disk('public')->delete($category->image_path);
        }
        
        $category->delete();

        // تحديث القائمة الجانبية "استكشاف" في الصفحة الرئيسية
        Cache::forget('home_categories');
        Cache::forget('home_content_categories');

        return redirect()->route('categories.index')
            ->with('success', 'تم حذف التصنيف بنجاح');
    }

    /**
     * جلب فيديوهات التصنيف بالترتيب الحالي (لنافذة ترتيب الفيديوهات).
     */
    public function items(Category $category)
    {
        $assets = $category->assets()
            ->select('assets.id', 'assets.file_name', 'assets.title', 'assets.thumbnail_path', 'assets.duration_seconds')
            ->orderByPivot('order')
            ->orderBy('assets.id')
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
     * حفظ ترتيب فيديوهات التصنيف. body: { asset_ids: [id1, id2, ...] }
     */
    public function reorderAssets(Request $request, Category $category)
    {
        $request->validate([
            'asset_ids' => 'required|array',
            'asset_ids.*' => 'integer|exists:assets,id',
        ]);
        $assetIds = $request->input('asset_ids');
        $existingIds = $category->assets()->pluck('assets.id')->toArray();
        $validIds = array_values(array_intersect($assetIds, $existingIds));
        foreach ($validIds as $position => $assetId) {
            \Illuminate\Support\Facades\DB::table('asset_category')
                ->where('category_id', $category->id)
                ->where('asset_id', $assetId)
                ->update(['order' => $position]);
        }
        return response()->json(['success' => true, 'message' => 'تم تحديث ترتيب الفيديوهات بنجاح']);
    }
}


