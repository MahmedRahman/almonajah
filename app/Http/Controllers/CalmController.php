<?php

namespace App\Http\Controllers;

use App\Services\FeelingAssetMatcher;
use Illuminate\Http\Request;

class CalmController extends Controller
{
    public function index()
    {
        $chips = config('feelings.chips', []);

        return view('landing.calm', compact('chips'));
    }

    public function match(Request $request, FeelingAssetMatcher $matcher)
    {
        $validated = $request->validate([
            'feeling' => 'nullable|string|max:600',
            'chip' => 'nullable|string|max:50',
            'exclude_ids' => 'nullable|array|max:30',
            'exclude_ids.*' => 'integer',
        ]);

        $feeling = trim((string) ($validated['feeling'] ?? ''));
        $chip = isset($validated['chip']) ? trim((string) $validated['chip']) : null;
        $excludeIds = $validated['exclude_ids'] ?? [];

        if ($feeling === '' && ($chip === null || $chip === '')) {
            return response()->json([
                'success' => false,
                'error' => 'اكتب شعورك أو اختر حالة من القائمة.',
            ], 422);
        }

        // إذا اختار chip بدون نص، نستخدم اسم الحالة كنص إدخال
        if ($feeling === '' && $chip) {
            $feeling = $chip;
        }

        $result = $matcher->match($feeling, $chip ?: null, $excludeIds);

        if (! $result) {
            return response()->json([
                'success' => false,
                'error' => 'لم نجد محتوى مناسبًا لهذه الحالة حاليًا. جرّب شعورًا آخر أو عد لاحقًا.',
            ], 404);
        }

        $asset = $result['asset'];

        return response()->json([
            'success' => true,
            'feeling_key' => $result['feeling_key'],
            'item' => [
                'id' => $asset->id,
                'title' => $asset->title ?: ($asset->file_name ?: 'دعاء'),
                'speaker_name' => $asset->speaker_name,
                'excerpt' => $result['excerpt'],
                'audio_url' => $result['audio_url'],
                'deep_link' => $result['deep_link'],
                'duration_seconds' => $asset->duration_seconds,
                'thumbnail_url' => $asset->thumbnail_path
                    ? asset('storage/'.$asset->thumbnail_path)
                    : null,
            ],
        ]);
    }
}
