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
        $playlists = Playlist::withCount('assets')
            ->latest()
            ->paginate(15);

        return view('playlists.index', compact('playlists'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:playlists',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('playlists', 'public');
            $validated['image_path'] = $imagePath;
        }

        // Remove image from validated array as it's not a database column
        unset($validated['image']);

        Playlist::create($validated);

        return redirect()->route('playlists.index')
            ->with('success', 'تم إنشاء قائمة التشغيل بنجاح');
    }

    public function update(Request $request, Playlist $playlist)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:playlists,slug,' . $playlist->id,
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

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

        return redirect()->route('playlists.index')
            ->with('success', 'تم تحديث قائمة التشغيل بنجاح');
    }

    public function destroy(Playlist $playlist)
    {
        // Delete image if exists
        if ($playlist->image_path && Storage::disk('public')->exists($playlist->image_path)) {
            Storage::disk('public')->delete($playlist->image_path);
        }
        
        $playlist->delete();
        return redirect()->route('playlists.index')
            ->with('success', 'تم حذف قائمة التشغيل بنجاح');
    }
}
