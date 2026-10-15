@extends('layouts.app')
@section('title', 'Edit client · ChaseFlow')
@section('content')
<h1 class="text-2xl font-bold mb-6">Edit client</h1>
<form method="POST" action="{{ route('clients.update', $client) }}" class="bg-white rounded shadow p-6 max-w-lg space-y-4">
    @csrf @method('PUT')
    @include('clients._form')
    <button class="bg-gray-900 text-white rounded px-4 py-2 text-sm hover:bg-gray-700">Update client</button>
</form>
@endsection
