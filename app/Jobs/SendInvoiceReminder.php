<?php

namespace App\Jobs;

use App\Mail\InvoiceReminderMail;
use App\Models\Invoice;
use App\Services\LateFeeService;
use App\Services\ReminderService;
use App\Support\MessageId;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendInvoiceReminder implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 300; // 5 minutes between retries

    public function __construct(public Invoice $invoice) {}

    public function handle(ReminderService $reminders, LateFeeService $lateFees): void
    {
        $this->invoice->refresh();
        $step = $reminders->nextStep($this->invoice);

        if (! $step) {
            return; // Nothing due — paid, not overdue yet, or step already sent.
        }

        if (! empty($step['apply_late_fee'])) {
            $this->invoice = $lateFees->apply($this->invoice);
        }

        $subject = $reminders->renderSubject($step, $this->invoice);

        Mail::to($this->invoice->client->email)->send(
            new InvoiceReminderMail($this->invoice, $step, $subject)
        );

        $this->invoice->reminderLogs()->create([
            'step' => $step['index'],
            'template' => $step['template'],
            'subject' => $subject,
            'message_id' => MessageId::take(),
            'meta' => [
                'days_overdue' => $this->invoice->days_overdue,
                'total_due' => $this->invoice->total_amount,
            ],
            'sent_at' => now(),
        ]);
    }

    /**
     * Send the final notice only to the client; never spam a wrong address forever.
     */
    public function failed(?\Throwable $exception): void
    {
        logger()->error('SendInvoiceReminder failed permanently', [
            'invoice_id' => $this->invoice->id,
            'error' => $exception?->getMessage(),
        ]);
    }
}
