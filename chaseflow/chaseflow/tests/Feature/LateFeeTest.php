<?php

use App\Jobs\SendInvoiceReminder;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\User;
use App\Services\LateFeeService;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->user = User::factory()->create();
    Setting::set('late_fee_percent', 5);
    Mail::fake();
});

it('applies the configured late fee at the final ladder step', function () {
    $invoice = Invoice::factory()->for($this->user)->sent()->create([
        'amount' => 1000,
        'due_date' => now()->subDays(14)->toDateString(),
    ]);

    SendInvoiceReminder::dispatchSync($invoice); // step 0
    SendInvoiceReminder::dispatchSync($invoice->refresh()); // step 1
    SendInvoiceReminder::dispatchSync($invoice->refresh()); // step 2: final notice + late fee

    $invoice->refresh();

    expect((float) $invoice->late_fee)->toBe(50.0) // 5% of 1000
        ->and($invoice->total_amount)->toBe(1050.0);

    $this->assertDatabaseHas('reminder_logs', ['invoice_id' => $invoice->id, 'step' => 2]);
});

it('never stacks a late fee twice', function () {
    $invoice = Invoice::factory()->for($this->user)->sent()->create([
        'amount' => 800,
        'due_date' => now()->subDays(30)->toDateString(),
    ]);

    $service = app(LateFeeService::class);
    $service->apply($invoice);
    $service->apply($invoice->refresh()); // simulated double-processing

    expect((float) $invoice->refresh()->late_fee)->toBe(40.0);
});

it('respects the configurable late fee percentage', function () {
    Setting::set('late_fee_percent', 10);

    $invoice = Invoice::factory()->for($this->user)->sent()->create([
        'amount' => 200,
        'due_date' => now()->subDays(1)->toDateString(),
    ]);

    app(LateFeeService::class)->apply($invoice);

    expect((float) $invoice->refresh()->late_fee)->toBe(20.0);
});
