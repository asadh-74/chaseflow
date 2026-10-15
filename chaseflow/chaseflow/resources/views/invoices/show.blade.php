@extends('layouts.app')
@section('title', $invoice->number.' · ChaseFlow')
@section('content')
@php $fmt = fn ($n) => $invoice->currency.' '.number_format((float) $n, 2); @endphp

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Invoice {{ $invoice->number }}</h1>
    <div class="flex gap-2">
        @if($invoice->status === 'draft')
            <form method="POST" action="{{ route('invoices.send', $invoice) }}">@csrf
                <button class="bg-green-600 text-white rounded px-4 py-2 text-sm hover:bg-green-500">Send to client</button>
            </form>
        @endif
        @if(!$invoice->paid_at)
            <form method="POST" action="{{ route('invoices.remind', $invoice) }}">@csrf
                <button class="bg-white shadow rounded px-4 py-2 text-sm">Send reminder now</button>
            </form>
            <form method="POST" action="{{ route('invoices.paid', $invoice) }}">@csrf
                <button class="bg-gray-900 text-white rounded px-4 py-2 text-sm hover:bg-gray-700">Mark paid</button>
            </form>
        @endif
    </div>
</div>

<div class="grid md:grid-cols-2 gap-6">
    <div class="bg-white rounded shadow p-6">
        <h2 class="font-semibold mb-4">Details</h2>
        <dl class="text-sm space-y-2">
            <div class="flex justify-between"><dt class="text-gray-500">Client</dt><dd>{{ $invoice->client->name }} · {{ $invoice->client->email }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Status</dt>
                <dd>@if($invoice->paid_at)<span class="text-green-700 font-medium">Paid {{ $invoice->paid_at->toFormattedDateString() }}</span>
                    @elseif($invoice->is_overdue)<span class="text-red-600 font-medium">Overdue {{ $invoice->days_overdue }} days</span>
                    @else<span class="capitalize">{{ $invoice->status }}</span>@endif</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Due date</dt><dd>{{ $invoice->due_date->toFormattedDateString() }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Sent</dt><dd>{{ $invoice->sent_at?->toFormattedDateString() ?? '—' }}</dd></div>
            <div class="flex justify-between border-t pt-2"><dt class="text-gray-500">Subtotal</dt><dd>{{ $fmt($invoice->amount) }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Late fee</dt><dd>{{ $invoice->late_fee > 0 ? $fmt($invoice->late_fee) : '—' }}</dd></div>
            <div class="flex justify-between text-base font-bold"><dt>Total due</dt><dd>{{ $fmt($invoice->total_amount) }}</dd></div>
        </dl>
    </div>

    <div class="bg-white rounded shadow p-6">
        <h2 class="font-semibold mb-4">Automation timeline</h2>
        @if($invoice->reminderLogs->isEmpty())
            <p class="text-sm text-gray-500">No reminders sent yet. Day 1 / Day 7 / Day 14 emails fire automatically
                via the nightly cron job once the invoice is overdue.</p>
        @else
            <ol class="relative border-l border-gray-200 ml-2 space-y-4">
                @foreach($invoice->reminderLogs as $log)
                    <li class="ml-4">
                        <div class="absolute w-3 h-3 bg-gray-900 rounded-full -left-1.5 mt-1"></div>
                        <p class="text-sm font-medium">{{ $log->subject }}</p>
                        <p class="text-xs text-gray-500">{{ $log->sent_at->toDayDateTimeString() }}
                            · SES ID: <code class="text-gray-400">{{ $log->message_id ?? 'n/a' }}</code></p>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
</div>
@endsection
