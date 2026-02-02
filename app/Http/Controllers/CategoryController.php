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
}


