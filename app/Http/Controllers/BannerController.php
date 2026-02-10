<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class BannerController extends Controller
{
    private function cacheKeys(): array
    {
        return ['banners_home', 'banners_video_detail', 'banners_categories'];
    }

    private function clearBannerCache(): void
    {
        foreach ($this->cacheKeys() as $key) {
            Cache::forget($key);
        }
    }

    public function index(Request $request)
    {
        $query = Banner::query()->orderBy('order')->orderBy('id', 'desc');

        if ($request->filled('size')) {
            $query->where('size', $request->size);
        }
        if ($request->filled('placement')) {
            $column = match ($request->placement) {
                'home' => 'show_on_home',
                'video_detail' => 'show_on_video_detail',
                'categories' => 'show_on_categories',
                default => null,
            };
            if ($column) {
                $query->where($column, true);
            }
        }

        $banners = $query->paginate(15);
        return view('banners.index', compact('banners'));
    }

    public function create()
    {
        return view('banners.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'link' => 'nullable|url|max:500',
            'size' => 'required|in:vertical,landscape,rectangle',
            'show_on_home' => 'nullable|boolean',
            'show_on_video_detail' => 'nullable|boolean',
            'show_on_categories' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['show_on_home'] = $request->boolean('show_on_home');
        $validated['show_on_video_detail'] = $request->boolean('show_on_video_detail');
        $validated['show_on_categories'] = $request->boolean('show_on_categories');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['order'] = (int) ($validated['order'] ?? 0);

        $imagePath = $request->file('image')->store('banners', 'public');
        $validated['image_path'] = $imagePath;
        unset($validated['image']);

        Banner::create($validated);
        $this->clearBannerCache();

        return redirect()->route('banners.index')->with('success', 'تم إنشاء البنر بنجاح');
    }

    public function show(Banner $banner)
    {
        return redirect()->route('banners.index');
    }

    public function edit(Banner $banner)
    {
        return view('banners.edit', compact('banner'));
    }

    public function update(Request $request, Banner $banner)
    {
        $validated = $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'link' => 'nullable|url|max:500',
            'size' => 'required|in:vertical,landscape,rectangle',
            'show_on_home' => 'nullable|boolean',
            'show_on_video_detail' => 'nullable|boolean',
            'show_on_categories' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['show_on_home'] = $request->boolean('show_on_home');
        $validated['show_on_video_detail'] = $request->boolean('show_on_video_detail');
        $validated['show_on_categories'] = $request->boolean('show_on_categories');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['order'] = (int) ($validated['order'] ?? $banner->order);

        if ($request->hasFile('image')) {
            if ($banner->image_path && Storage::disk('public')->exists($banner->image_path)) {
                Storage::disk('public')->delete($banner->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('banners', 'public');
        }
        unset($validated['image']);

        $banner->update($validated);
        $this->clearBannerCache();

        return redirect()->route('banners.index')->with('success', 'تم تحديث البنر بنجاح');
    }

    public function destroy(Banner $banner)
    {
        if ($banner->image_path && Storage::disk('public')->exists($banner->image_path)) {
            Storage::disk('public')->delete($banner->image_path);
        }
        $banner->delete();
        $this->clearBannerCache();

        return redirect()->route('banners.index')->with('success', 'تم حذف البنر بنجاح');
    }
}
