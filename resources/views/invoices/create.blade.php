@extends('layouts.app')
@section('title', 'New invoice · ChaseFlow')
@section('content')
<h1 class="text-2xl font-bold mb-6">New invoice</h1>
<form method="POST" action="{{ route('invoices.store') }}" class="bg-white rounded shadow p-6 max-w-lg space-y-4">
    @csrf
    <div>
        <label class="block text-sm font-medium">Client</label>
        <select name="client_id" required class="mt-1 w-full rounded border-gray-300 border px-3 py-2">
            <option value="">Select…</option>
            @foreach($clients as $client)
                <option value="{{ $client->id }}" @selected(old('client_id') == $client->id)>{{ $client->name }} ({{ $client->company }})</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-500">No client yet? <a class="text-blue-600" href="{{ route('clients.create') }}">Create one</a>.</p>
    </div>
    <div>
        <label class="block text-sm font-medium">Invoice number</label>
        <input name="number" value="{{ old('number', $nextNumber) }}" required class="mt-1 w-full rounded border-gray-300 border px-3 py-2">
    </div>
    <div class="grid grid-cols-3 gap-4">
        <div class="col-span-2">
            <label class="block text-sm font-medium">Amount</label>
            <input name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount') }}" required class="mt-1 w-full rounded border-gray-300 border px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium">Currency</label>
            <input name="currency" value="{{ old('currency', 'USD') }}" maxlength="3" required class="mt-1 w-full rounded border-gray-300 border px-3 py-2">
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium">Due date (the reminder ladder starts here)</label>
        <input name="due_date" type="date" value="{{ old('due_date') }}" required class="mt-1 w-full rounded border-gray-300 border px-3 py-2">
    </div>
    <button class="bg-gray-900 text-white rounded px-4 py-2 text-sm hover:bg-gray-700">Create invoice</button>
</form>
@endsection
