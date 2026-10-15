<?php

use App\Jobs\SendInvoiceReminder;
use App\Mail\InvoiceReminderMail;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(fn () => $this->user = User::factory()->create());

it('sends step 1 (gentle) exactly once for an invoice 1 day overdue', function () {
    Mail::fake();

    $invoice = Invoice::factory()->for($this->user)->sent()->create([
        'due_date' => now()->subDays(1)->toDateString(),
    ]);

    SendInvoiceReminder::dispatchSync($invoice);

    Mail::assertSent(InvoiceReminderMail::class);
    $this->assertDatabaseHas('reminder_logs', ['invoice_id' => $invoice->id, 'step' => 0]);

    // Second run must be a no-op — the ladder never double-sends.
    SendInvoiceReminder::dispatchSync($invoice->refresh());

    Mail::assertSentCount(1);
    $this->assertSame(1, $invoice->reminderLogs()->count());
});

it('does not send anything before day 1 or for paid invoices', function () {
    Mail::fake();

    $notYet = Invoice::factory()->for($this->user)->sent()->create([
        'due_date' => now()->subHours(12)->toDateString(),
    ]);
    $paid = Invoice::factory()->for($this->user)->paid()->create([
        'due_date' => now()->subDays(20)->toDateString(),
    ]);

    SendInvoiceReminder::dispatchSync($notYet);
    SendInvoiceReminder::dispatchSync($paid);

    Mail::assertNothingSent();
});

it('walks the ladder up to the firm reminder at day 7', function () {
    Mail::fake();

    $invoice = Invoice::factory()->for($this->user)->sent()->create([
        'due_date' => now()->subDays(7)->toDateString(),
    ]);

    SendInvoiceReminder::dispatchSync($invoice); // step 0 + step 1 both due; fires step 0

    SendInvoiceReminder::dispatchSync($invoice->refresh()); // now step 1

    $this->assertSame(2, $invoice->reminderLogs()->count());
    $this->assertDatabaseHas('reminder_logs', ['invoice_id' => $invoice->id, 'step' => 1]);
});

it('stops sending once the invoice is paid', function () {
    Mail::fake();

    $invoice = Invoice::factory()->for($this->user)->sent()->create([
        'due_date' => now()->subDays(3)->toDateString(),
    ]);

    SendInvoiceReminder::dispatchSync($invoice);
    $invoice->update(['status' => 'paid', 'paid_at' => now()]);

    SendInvoiceReminder::dispatchSync($invoice->refresh());

    Mail::assertSentCount(1); // only the first one
});
