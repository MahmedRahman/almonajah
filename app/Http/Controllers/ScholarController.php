<?php

namespace App\Http\Controllers;

use App\Models\Scholar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ScholarController extends Controller
{
    public function index()
    {
        $scholars = Scholar::orderBy('order')
            ->orderBy('name')
            ->paginate(15);

        return view('scholars.index', compact('scholars'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'status' => 'required|in:active,inactive',
            'order' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $validated['order'] = $validated['order'] ?? 0;

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('scholars', 'public');
        }
        unset($validated['image']);

        Scholar::create($validated);

        return redirect()->route('scholars.index')
            ->with('success', 'تم إضافة الشيخ بنجاح');
    }

    public function update(Request $request, Scholar $scholar)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'status' => 'required|in:active,inactive',
            'order' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $validated['order'] = $validated['order'] ?? $scholar->order ?? 0;

        if ($request->hasFile('image')) {
            if ($scholar->image_path && Storage::disk('public')->exists($scholar->image_path)) {
                Storage::disk('public')->delete($scholar->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('scholars', 'public');
        }
        unset($validated['image']);

        $scholar->update($validated);

        return redirect()->route('scholars.index')
            ->with('success', 'تم تحديث الشيخ بنجاح');
    }

    public function destroy(Scholar $scholar)
    {
        if ($scholar->image_path && Storage::disk('public')->exists($scholar->image_path)) {
            Storage::disk('public')->delete($scholar->image_path);
        }
        $scholar->delete();
        return redirect()->route('scholars.index')
            ->with('success', 'تم حذف الشيخ بنجاح');
    }
}
