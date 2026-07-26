<?php

namespace App\Http\Controllers;

use App\Services\MetaConversionsApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MetaCapiController extends Controller
{
    public function store(Request $request, MetaConversionsApi $capi): JsonResponse
    {
        if (!$capi->isConfigured()) {
            return response()->noContent();
        }

        $validated = $request->validate([
            'event_name' => ['required', 'string', 'max:64'],
            'event_id' => ['required', 'string', 'max:128'],
            'event_source_url' => ['nullable', 'string', 'max:2048'],
            'fbp' => ['nullable', 'string', 'max:255'],
            'fbc' => ['nullable', 'string', 'max:255'],
            'custom_data' => ['nullable', 'array'],
        ]);

        $eventName = $validated['event_name'];
        if (!in_array($eventName, MetaConversionsApi::BEACON_EVENTS, true)) {
            return response()->json(['ok' => false, 'reason' => 'invalid_event'], 422);
        }

        $userData = $capi->userDataFromRequest($request);
        if (!empty($validated['fbp'])) {
            $userData['fbp'] = $validated['fbp'];
        }
        if (!empty($validated['fbc'])) {
            $userData['fbc'] = $validated['fbc'];
        }

        $sourceUrl = $validated['event_source_url']
            ?? $request->headers->get('Referer')
            ?? url('/');

        if (!$this->isAllowedSourceUrl($sourceUrl)) {
            $sourceUrl = url('/');
        }

        $capi->queue(
            $eventName,
            $userData,
            is_array($validated['custom_data'] ?? null) ? $validated['custom_data'] : [],
            $validated['event_id'],
            $sourceUrl
        );

        return response()->json(['ok' => true]);
    }

    private function isAllowedSourceUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (!$host || !$appHost) {
            return false;
        }

        $host = strtolower((string) $host);
        $appHost = strtolower((string) $appHost);

        return $host === $appHost
            || str_ends_with($host, '.' . $appHost)
            || str_ends_with($appHost, '.' . $host);
    }
}
