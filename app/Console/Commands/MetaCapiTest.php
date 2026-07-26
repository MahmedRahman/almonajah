<?php

namespace App\Console\Commands;

use App\Services\MetaConversionsApi;
use Illuminate\Console\Command;

class MetaCapiTest extends Command
{
    protected $signature = 'meta:capi-test {--event=PageView : Event name to send}';

    protected $description = 'Send a test event to Meta Conversions API';

    public function handle(MetaConversionsApi $capi): int
    {
        if (!$capi->isConfigured()) {
            $this->error('Meta CAPI is not configured. Set META_PIXEL_ID and META_CAPI_ACCESS_TOKEN in .env');
            return self::FAILURE;
        }

        $event = (string) $this->option('event');
        $eventId = 'test_' . bin2hex(random_bytes(8));

        $ok = $capi->send(
            $event,
            [
                'client_ip_address' => '127.0.0.1',
                'client_user_agent' => 'Almonajah-CAPI-Test/1.0',
            ],
            [],
            $eventId,
            config('app.url')
        );

        if ($ok) {
            $this->info("Sent {$event} (event_id={$eventId}). Check Events Manager → Test Events.");
            if (filled(config('services.meta.test_event_code'))) {
                $this->line('Using test_event_code: ' . config('services.meta.test_event_code'));
            }
            return self::SUCCESS;
        }

        $this->error('Failed to send event. Check storage/logs/laravel.log');
        return self::FAILURE;
    }
}
