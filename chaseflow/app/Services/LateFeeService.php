<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class LateFeeService
{
    /**
     * Apply the configured late fee percentage to the invoice subtotal.
     * Idempotent: the row lock + guard mean double-processing can never
     * stack two fees on the same invoice.
     */
    public function apply(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            /** @var Invoice $locked */
            $locked = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ((float) $locked->late_fee > 0) {
                return $locked;
            }

            $percent = (float) Setting::get('late_fee_percent', (float) env('LATE_FEE_PERCENT', 5));
            $fee = round(((float) $locked->amount) * $percent / 100, 2);

            $locked->update(['late_fee' => $fee]);

            return $locked->refresh();
        });
    }
}
