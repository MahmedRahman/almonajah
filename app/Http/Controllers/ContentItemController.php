<?php

namespace App\Http\Controllers;

use App\Models\ContentItem;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ContentItemController extends Controller
{
    public function index()
    {
        $contentItems = ContentItem::with('author', 'categories')
            ->latest()
            ->paginate(15);

        return view('content.index', compact('contentItems'));
    }

    public function create()
    {
        $categories = Category::all();
        return view('content.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:content_items',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'status' => 'required|in:draft,published,archived',
            'type' => 'required|in:article,page,video,image,document',
            'featured_image' => 'nullable|string',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);
        $validated['author_id'] = auth()->id();

        if ($validated['status'] === 'published') {
            $validated['published_at'] = now();
        }

        $contentItem = ContentItem::create($validated);

        if ($request->has('categories')) {
            $contentItem->categories()->sync($request->categories);
        }

        return redirect()->route('content.index')
            ->with('success', 'تم إنشاء المحتوى بنجاح');
    }

    public function show(ContentItem $contentItem)
    {
        $contentItem->load('author', 'categories');
        return view('content.show', compact('contentItem'));
    }

    public function edit(ContentItem $contentItem)
    {
        $categories = Category::all();
        $contentItem->load('categories');
        return view('content.edit', compact('contentItem', 'categories'));
    }

    public function update(Request $request, ContentItem $contentItem)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:content_items,slug,' . $contentItem->id,
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'status' => 'required|in:draft,published,archived',
            'type' => 'required|in:article,page,video,image,document',
            'featured_image' => 'nullable|string',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
        ]);

        if ($validated['status'] === 'published' && !$contentItem->published_at) {
            $validated['published_at'] = now();
        }

        $contentItem->update($validated);

        if ($request->has('categories')) {
            $contentItem->categories()->sync($request->categories);
        } else {
            $contentItem->categories()->detach();
        }

        return redirect()->route('content.index')
            ->with('success', 'تم تحديث المحتوى بنجاح');
    }

    public function destroy(ContentItem $contentItem)
    {
        $contentItem->delete();
        return redirect()->route('content.index')
            ->with('success', 'تم حذف المحتوى بنجاح');
    }
}


