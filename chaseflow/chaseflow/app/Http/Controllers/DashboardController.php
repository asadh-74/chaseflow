<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\ReminderLog;
use App\Services\CashFlowForecastService;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(CashFlowForecastService $forecast)
    {
        $stats = [
            'outstanding_total' => (float) Invoice::whereNull('paid_at')
                ->sum(DB::raw('amount + late_fee')),
            'overdue_total' => (float) Invoice::overdue()->sum(DB::raw('amount + late_fee')),
            'overdue_count' => Invoice::overdue()->count(),
            'paid_this_month' => (float) Invoice::whereNotNull('paid_at')
                ->whereMonth('paid_at', now()->month)
                ->sum('amount'),
            ...$forecast->forecast(30),
        ];

        $overdueInvoices = Invoice::overdue()->with('client')->orderBy('due_date')->limit(10)->get();

        $recentReminders = ReminderLog::with('invoice.client')->latest('sent_at')->limit(8)->get();

        // Last 6 months: paid vs invoiced, for the bar chart.
        $chart = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->startOfMonth())->map(
            fn ($month) => [
                'label' => $month->format('M'),
                'invoiced' => (float) Invoice::whereBetween('created_at', [$month, $month->copy()->endOfMonth()])->sum('amount'),
                'paid' => (float) Invoice::whereBetween('paid_at', [$month, $month->copy()->endOfMonth()])->sum('amount'),
            ]
        );

        return view('dashboard', compact('stats', 'overdueInvoices', 'recentReminders', 'chart'));
    }
}
