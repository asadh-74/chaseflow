<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public array $step,
        public string $subjectLine,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(
            markdown: $this->step['template'],
            with: [
                'invoice' => $this->invoice,
                'client' => $this->invoice->client,
                'daysOverdue' => $this->invoice->days_overdue,
                'totalDue' => $this->invoice->total_amount,
                'payUrl' => url("/invoices/{$this->invoice->id}"),
            ],
        );
    }
}
