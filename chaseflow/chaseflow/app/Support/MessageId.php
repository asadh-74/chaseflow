<?php

namespace App\Support;

/**
 * Holds the AWS SES message ID of the most recently sent message.
 * Populated by the MessageSent listener registered in AppServiceProvider,
 * consumed by SendInvoiceReminder to write the audit log.
 */
class MessageId
{
    protected static ?string $id = null;

    public static function set(?string $id): void
    {
        static::$id = $id;
    }

    public static function take(): ?string
    {
        $id = static::$id;
        static::$id = null;

        return $id;
    }
}
