<?php

namespace App\Providers;

use App\Support\MessageId;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Capture the SES message ID for the audit log (reminder_logs.message_id).
        Event::listen(MessageSent::class, function (MessageSent $event) {
            $headers = $event->message->getHeaders();

            MessageId::set(
                $headers->get('X-SES-Message-ID')?->getBody()
                    ?? $headers->get('X-Message-ID')?->getBody()
                    ?? $headers->get('Message-ID')?->getBody()
            );
        });
    }
}
