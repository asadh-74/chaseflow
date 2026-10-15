@extends('layouts.app')
@section('title', 'Invoices · ChaseFlow')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Invoices</h1>
    <a href="{{ route('invoices.create') }}" class="bg-gray-900 text-white rounded px-4 py-2 text-sm hover:bg-gray-700">+ New invoice</a>
</div>

@php $tabs = ['all' => 'All', 'overdue' => 'Overdue', 'sent' => 'Sent', 'draft' => 'Draft', 'paid' => 'Paid']; @endphp
<div class="flex gap-2 mb-4">
    @foreach($tabs as $key => $label)
        <a href="{{ route('invoices.index', ['filter' => $key]) }}"
           class="px-3 py-1.5 rounded text-sm {{ $filter === $key ? 'bg-gray-900 text-white' : 'bg-white shadow' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="bg-white rounded shadow overflow-x-auto">
    <table class="w-full text-sm">
        <thead><tr class="text-left text-gray-500">
            <th class="p-4">Number</th><th class="p-4">Client</th><th class="p-4">Due date</th>
            <th class="p-4 text-right">Amount</th><th class="p-4">Status</th><th class="p-4"></th>
        </tr></thead>
        <tbody>
        @foreach($invoices as $invoice)
            <tr class="border-t {{ $invoice->is_overdue ? 'bg-red-50' : '' }}">
                <td class="p-4"><a class="text-blue-600 font-medium" href="{{ route('invoices.show', $invoice) }}">{{ $invoice->number }}</a></td>
                <td class="p-4">{{ $invoice->client->name }}</td>
                <td class="p-4">
                    {{ $invoice->due_date->format('M j, Y') }}
                    @if($invoice->is_overdue)
                        <span class="text-red-600 text-xs font-medium">({{ $invoice->days_overdue }}d overdue)</span>
                    @endif
                </td>
                <td class="p-4 text-right">${{ number_format($invoice->total_amount, 2) }}</td>
                <td class="p-4">
                    @if($invoice->paid_at)
                        <span class="text-green-700 bg-green-100 rounded px-2 py-0.5 text-xs font-medium">Paid</span>
                    @elseif($invoice->is_overdue)
                        <span class="text-red-700 bg-red-100 rounded px-2 py-0.5 text-xs font-medium">Overdue</span>
                    @elseif($invoice->status === 'sent')
                        <span class="text-blue-700 bg-blue-100 rounded px-2 py-0.5 text-xs font-medium">Sent</span>
                    @else
                        <span class="text-gray-600 bg-gray-100 rounded px-2 py-0.5 text-xs font-medium">Draft</span>
                    @endif
                </td>
                <td class="p-4 text-right whitespace-nowrap">
                    @if($invoice->status === 'draft')
                        <form method="POST" action="{{ route('invoices.send', $invoice) }}" class="inline">
                            @csrf
                            <button class="text-green-700 mr-3">Send</button>
                        </form>
                    @endif
                    @if(!$invoice->paid_at)
                        <form method="POST" action="{{ route('invoices.paid', $invoice) }}" class="inline">
                            @csrf
                            <button class="text-blue-600 mr-3">Mark paid</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" class="inline"
                          onsubmit="return confirm('Delete invoice {{ $invoice->number }}?')">
                        @csrf @method('DELETE')
                        <button class="text-red-600">Delete</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $invoices->links() }}
@endsection
