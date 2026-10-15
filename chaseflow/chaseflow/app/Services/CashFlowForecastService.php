<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CashFlowForecastService
{
    /**
     * Expected incoming payments grouped by week, for outstanding invoices
     * due within the next N days, plus the current overdue total.
     */
    public function forecast(int $days = 30): array
    {
        $outstanding = Invoice::query()
            ->whereNull('paid_at')
            ->whereIn('status', [Invoice::STATUS_SENT, Invoice::STATUS_DRAFT])
            ->whereDate('due_date', '<=', today()->addDays($days))
            ->select('id', 'number', 'amount', 'late_fee', 'due_date', 'status')
            ->get();

        $byWeek = $outstanding
            ->groupBy(fn (Invoice $i) => $i->due_date->startOfWeek()->toDateString())
            ->map(fn (Collection $group, $week) => [
                'week' => $week,
                'count' => $group->count(),
                'total' => $group->sum(fn (Invoice $i) => $i->total_amount),
            ])
            ->sortKeys()
            ->values();

        return [
            'weeks' => $byWeek,
            'overdue_total' => (float) Invoice::overdue()->sum(DB::raw('amount + late_fee')),
            'overdue_count' => Invoice::overdue()->count(),
            'next_7_days' => (float) $outstanding
                ->filter(fn (Invoice $i) => $i->due_date->lte(today()->addDays(7)))
                ->sum(fn (Invoice $i) => $i->total_amount),
            'next_30_days' => (float) $outstanding->sum(fn (Invoice $i) => $i->total_amount),
        ];
    }
}
