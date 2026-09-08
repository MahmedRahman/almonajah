<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class ResendMailer
{
    public ?string $lastError = null;

    public function send(string $to, string $subject, string $html, ?string $text = null): bool
    {
        $this->lastError = null;
        $apiKey = (string) config('services.resend.api_key');
        $from = (string) config('services.resend.from');

        if ($apiKey === '' || $from === '') {
            $this->lastError = 'إعدادات Resend غير مكتملة.';
            $this->safeLog('warning', 'ResendMailer: missing API key or from address.');

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
                $body = $response->json() ?? $response->body();
                $message = is_array($body) ? ($body['message'] ?? json_encode($body, JSON_UNESCAPED_UNICODE)) : (string) $body;
                $this->lastError = $message;
                $this->safeLog('error', 'ResendMailer failed: '.$message, [
                    'status' => $response->status(),
                    'to' => $to,
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            $this->safeLog('error', 'ResendMailer exception: '.$e->getMessage(), [
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

    private function safeLog(string $level, string $message, array $context = []): void
    {
        try {
            Log::{$level}($message, $context);
        } catch (\Throwable $e) {
            // Ignore logging failures so email sending is not interrupted.
        }
    }
}
