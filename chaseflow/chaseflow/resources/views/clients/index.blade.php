@extends('layouts.app')
@section('title', 'Clients · ChaseFlow')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Clients</h1>
    <a href="{{ route('clients.create') }}" class="bg-gray-900 text-white rounded px-4 py-2 text-sm hover:bg-gray-700">+ New client</a>
</div>
<div class="bg-white rounded shadow overflow-x-auto">
    <table class="w-full text-sm">
        <thead><tr class="text-left text-gray-500">
            <th class="p-4">Name</th><th class="p-4">Email</th><th class="p-4">Company</th>
            <th class="p-4 text-right">Invoices</th><th class="p-4 text-right">Outstanding</th><th class="p-4"></th>
        </tr></thead>
        <tbody>
        @foreach($clients as $client)
            <tr class="border-t">
                <td class="p-4 font-medium">{{ $client->name }}</td>
                <td class="p-4">{{ $client->email }}</td>
                <td class="p-4">{{ $client->company }}</td>
                <td class="p-4 text-right">{{ $client->invoices_count }}</td>
                <td class="p-4 text-right">${{ number_format((float) ($client->outstanding_sum ?? 0), 2) }}</td>
                <td class="p-4 text-right">
                    <a href="{{ route('clients.edit', $client) }}" class="text-blue-600 mr-3">Edit</a>
                    <form method="POST" action="{{ route('clients.destroy', $client) }}" class="inline"
                          onsubmit="return confirm('Delete this client and all their invoices?')">
                        @csrf @method('DELETE')
                        <button class="text-red-600">Delete</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $clients->links() }}
@endsection
