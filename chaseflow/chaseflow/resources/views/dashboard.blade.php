@extends('layouts.app')
@section('title', 'Dashboard · ChaseFlow')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Dashboard</h1>
    <a href="{{ route('invoices.create') }}" class="bg-gray-900 text-white rounded px-4 py-2 text-sm hover:bg-gray-700">+ New invoice</a>
</div>

@php $fmt = fn ($n) => '$'.number_format((float) $n, 2); @endphp

<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
    <div class="bg-white rounded shadow p-4">
        <div class="text-xs text-gray-500 uppercase">Outstanding</div>
        <div class="text-xl font-bold">{{ $fmt($stats['outstanding_total']) }}</div>
    </div>
    <div class="bg-white rounded shadow p-4 border-l-4 border-red-500">
        <div class="text-xs text-gray-500 uppercase">Overdue</div>
        <div class="text-xl font-bold text-red-600">{{ $fmt($stats['overdue_total']) }}</div>
        <div class="text-xs text-gray-500">{{ $stats['overdue_count'] }} invoice(s)</div>
    </div>
    <div class="bg-white rounded shadow p-4">
        <div class="text-xs text-gray-500 uppercase">Next 7 days</div>
        <div class="text-xl font-bold text-green-600">{{ $fmt($stats['next_7_days']) }}</div>
    </div>
    <div class="bg-white rounded shadow p-4">
        <div class="text-xs text-gray-500 uppercase">Next 30 days</div>
        <div class="text-xl font-bold text-green-600">{{ $fmt($stats['next_30_days']) }}</div>
    </div>
    <div class="bg-white rounded shadow p-4">
        <div class="text-xs text-gray-500 uppercase">Paid this month</div>
        <div class="text-xl font-bold">{{ $fmt($stats['paid_this_month']) }}</div>
    </div>
</div>

<div class="grid md:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded shadow p-6">
        <h2 class="font-semibold mb-4">Cash-in forecast (next 30 days)</h2>
        @forelse($stats['weeks'] as $week)
            <div class="flex justify-between items-center py-2 border-b text-sm">
                <span>Week of {{ \Carbon\Carbon::parse($week['week'])->format('M j') }}
                    <span class="text-gray-400">({{ $week['count'] }})</span></span>
                <span class="font-medium">{{ $fmt($week['total']) }}</span>
            </div>
        @empty
            <p class="text-sm text-gray-500">No outstanding invoices in this window.</p>
        @endforelse
    </div>

    <div class="bg-white rounded shadow p-6">
        <h2 class="font-semibold mb-4">Paid vs invoiced — last 6 months</h2>
        <div class="flex items-end gap-3 h-40">
            @foreach($chart as $row)
                @php $max = max(1, $row['invoiced'], $row['paid']) @endphp
                <div class="flex-1 flex flex-col items-center gap-1">
                    <div class="w-full flex items-end gap-1 h-32">
                        <div class="bg-gray-300 flex-1" style="height: {{ round($row['invoiced'] / $max * 100) }}%"></div>
                        <div class="bg-green-500 flex-1" style="height: {{ round($row['paid'] / $max * 100) }}%"></div>
                    </div>
                    <span class="text-xs text-gray-500">{{ $row['label'] }}</span>
                </div>
            @endforeach
        </div>
        <div class="flex gap-4 mt-2 text-xs text-gray-500">
            <span class="flex items-center gap-1"><span class="w-3 h-3 bg-gray-300 inline-block"></span> Invoiced</span>
            <span class="flex items-center gap-1"><span class="w-3 h-3 bg-green-500 inline-block"></span> Paid</span>
        </div>
    </div>
</div>

<div class="grid md:grid-cols-2 gap-6">
    <div class="bg-white rounded shadow p-6">
        <div class="flex justify-between mb-4">
            <h2 class="font-semibold">Overdue invoices — chase list</h2>
            <a href="{{ route('invoices.index', ['filter' => 'overdue']) }}" class="text-sm text-blue-600">View all</a>
        </div>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-gray-500">
                <th class="pb-2">Invoice</th><th class="pb-2">Client</th><th class="pb-2">Days overdue</th><th class="pb-2 text-right">Total due</th>
            </tr></thead>
            <tbody>
            @foreach($overdueInvoices as $inv)
                <tr class="border-t">
                    <td class="py-2"><a class="text-blue-600" href="{{ route('invoices.show', $inv) }}">{{ $inv->number }}</a></td>
                    <td>{{ $inv->client->name }}</td>
                    <td><span class="text-red-600 font-medium">{{ $inv->days_overdue }}</span></td>
                    <td class="text-right">{{ $fmt($inv->total_amount) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded shadow p-6">
        <h2 class="font-semibold mb-4">Recent automated reminders</h2>
        @forelse($recentReminders as $log)
            <div class="text-sm py-2 border-b flex justify-between">
                <span><span class="font-medium">{{ $log->invoice->number }}</span>
                    → {{ $log->invoice->client->name }}
                    <span class="text-gray-400">(step {{ $log->step + 1 }})</span></span>
                <span class="text-gray-500">{{ $log->sent_at->diffForHumans() }}</span>
            </div>
        @empty
            <p class="text-sm text-gray-500">No reminders sent yet. The nightly job fires at 1 AM.</p>
        @endforelse
    </div>
</div>
@endsection
