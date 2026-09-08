<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class ResendMailer
{
    public function send(string $to, string $subject, string $html, ?string $text = null): bool
    {
        $apiKey = (string) config('services.resend.api_key');
        $from = (string) config('services.resend.from');

        if ($apiKey === '' || $from === '') {
            Log::warning('ResendMailer: missing API key or from address.');

            return false;
        }

        $payload = [
            'from' => $from,
            'to' => [$to],
            'subject' => $subject,
            'html' => $html,
        ];

        if ($text !== null && $text !== '') {
            $payload['text'] = $text;
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout(20)
                ->post('https://api.resend.com/emails', $payload);

            if (! $response->successful()) {
                Log::error('ResendMailer failed', [
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                    'to' => $to,
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('ResendMailer exception: '.$e->getMessage(), [
                'to' => $to,
            ]);

            return false;
        }
    }

    public function sendView(string $to, string $subject, string $view, array $data = []): bool
    {
        $html = View::make($view, $data)->render();

        return $this->send($to, $subject, $html);
    }
}
