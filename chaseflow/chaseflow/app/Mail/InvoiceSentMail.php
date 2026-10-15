<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class InvoiceSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public string $pdfPath,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Invoice {$this->invoice->number} from {$this->invoice->user->name}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.invoice-sent');
    }

    public function attachments(): array
    {
        $disk = config('filesystems.default');

        if (Storage::disk($disk)->exists($this->pdfPath)) {
            return [
                Attachment::fromStorageDisk($disk, $this->pdfPath)
                    ->as("invoice-{$this->invoice->number}.pdf")
                    ->withMime('application/pdf'),
            ];
        }

        return [];
    }
}
