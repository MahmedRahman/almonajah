<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaConversionsApi
{
    /**
     * Event names allowed via the public beacon endpoint.
     *
     * @var list<string>
     */
    public const BEACON_EVENTS = [
        'PageView',
        'ViewContent',
        'Search',
        'Lead',
        'CompleteRegistration',
        'AddToWishlist',
        'Contact',
        'CustomizeProduct',
        'Donate',
        'FindLocation',
        'Schedule',
        'StartTrial',
        'SubmitApplication',
        'Subscribe',
    ];

    public function isConfigured(): bool
    {
        return (bool) config('services.meta.enabled')
            && filled(config('services.meta.pixel_id'))
            && filled(config('services.meta.access_token'));
    }

    /**
     * Queue a conversion event to run after the HTTP response is sent.
     *
     * @param  array<string, mixed>  $userData
     * @param  array<string, mixed>  $customData
     */
    public function queue(
        string $eventName,
        array $userData = [],
        array $customData = [],
        ?string $eventId = null,
        ?string $eventSourceUrl = null,
        string $actionSource = 'website'
    ): void {
        if (!$this->isConfigured()) {
            return;
        }

        $eventName = trim($eventName);
        if ($eventName === '') {
            return;
        }

        dispatch(function () use ($eventName, $userData, $customData, $eventId, $eventSourceUrl, $actionSource) {
            $this->send($eventName, $userData, $customData, $eventId, $eventSourceUrl, $actionSource);
        })->afterResponse();
    }

    /**
     * Send an event immediately to Meta Conversions API.
     *
     * @param  array<string, mixed>  $userData
     * @param  array<string, mixed>  $customData
     */
    public function send(
        string $eventName,
        array $userData = [],
        array $customData = [],
        ?string $eventId = null,
        ?string $eventSourceUrl = null,
        string $actionSource = 'website'
    ): bool {
        if (!$this->isConfigured()) {
            return false;
        }

        $pixelId = (string) config('services.meta.pixel_id');
        $token = (string) config('services.meta.access_token');
        $version = (string) config('services.meta.api_version', 'v21.0');

        $event = [
            'event_name' => $eventName,
            'event_time' => time(),
            'action_source' => $actionSource,
            'user_data' => $this->normalizeUserData($userData),
        ];

        if ($eventId) {
            $event['event_id'] = $eventId;
        }

        if ($eventSourceUrl) {
            $event['event_source_url'] = $eventSourceUrl;
        }

        if ($customData !== []) {
            $event['custom_data'] = $customData;
        }

        $payload = [
            'data' => [$event],
            'access_token' => $token,
        ];

        $testCode = config('services.meta.test_event_code');
        if (filled($testCode)) {
            $payload['test_event_code'] = $testCode;
        }

        try {
            $response = Http::timeout(8)
                ->asJson()
                ->post("https://graph.facebook.com/{$version}/{$pixelId}/events", $payload);

            if (!$response->successful()) {
                Log::warning('Meta CAPI request failed', [
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                    'event_name' => $eventName,
                    'event_id' => $eventId,
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('Meta CAPI exception', [
                'message' => $e->getMessage(),
                'event_name' => $eventName,
                'event_id' => $eventId,
            ]);

            return false;
        }
    }

    /**
     * Build user_data from the current HTTP request (+ optional auth user).
     *
     * @return array<string, mixed>
     */
    public function userDataFromRequest(Request $request): array
    {
        $data = [
            'client_ip_address' => $request->ip(),
            'client_user_agent' => (string) $request->userAgent(),
        ];

        $fbp = $request->cookie('_fbp') ?: $request->input('fbp');
        $fbc = $request->cookie('_fbc') ?: $request->input('fbc');

        if (is_string($fbp) && $fbp !== '') {
            $data['fbp'] = $fbp;
        }

        if (is_string($fbc) && $fbc !== '') {
            $data['fbc'] = $fbc;
        }

        $user = $request->user();
        if ($user && filled($user->email ?? null)) {
            $data['em'] = [$this->hash($user->email)];
        }

        if ($user && filled($user->name ?? null)) {
            $parts = preg_split('/\s+/u', trim((string) $user->name)) ?: [];
            if ($parts !== []) {
                $data['fn'] = [$this->hash($parts[0])];
                if (count($parts) > 1) {
                    $data['ln'] = [$this->hash($parts[count($parts) - 1])];
                }
            }
        }

        return $data;
    }

    public function hash(string $value): string
    {
        return hash('sha256', strtolower(trim($value)));
    }

    /**
     * @param  array<string, mixed>  $userData
     * @return array<string, mixed>
     */
    private function normalizeUserData(array $userData): array
    {
        $normalized = [];

        foreach ($userData as $key => $value) {
            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            if (in_array($key, ['em', 'ph', 'fn', 'ln', 'db', 'ge', 'ct', 'st', 'zp', 'country', 'external_id'], true)) {
                $values = is_array($value) ? $value : [$value];
                $hashed = [];
                foreach ($values as $item) {
                    if (!is_string($item) && !is_numeric($item)) {
                        continue;
                    }
                    $item = (string) $item;
                    // Already hashed (64 hex chars)
                    $hashed[] = preg_match('/^[a-f0-9]{64}$/', $item)
                        ? $item
                        : $this->hash($item);
                }
                if ($hashed !== []) {
                    $normalized[$key] = array_values(array_unique($hashed));
                }
                continue;
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }
}
