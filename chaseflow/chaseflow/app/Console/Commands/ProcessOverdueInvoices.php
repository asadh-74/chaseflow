<?php

namespace App\Console\Commands;

use App\Jobs\SendInvoiceReminder;
use App\Models\Invoice;
use Illuminate\Console\Command;

class ProcessOverdueInvoices extends Command
{
    protected $signature = 'chaseflow:process-overdue-invoices';

    protected $description = 'Dispatch reminder emails for every overdue invoice, per the escalation ladder.';

    public function handle(): int
    {
        $count = 0;

        Invoice::query()
            ->overdue()
            ->with('client')
            ->chunkById(100, function ($invoices) use (&$count) {
                foreach ($invoices as $invoice) {
                    SendInvoiceReminder::dispatch($invoice)->onQueue('mail');
                    $count++;
                }
            });

        $this->info("Dispatched {$count} reminder job(s).");

        return self::SUCCESS;
    }
}
