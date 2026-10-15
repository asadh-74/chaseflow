@extends('layouts.app')
@section('title', 'New client · ChaseFlow')
@section('content')
<h1 class="text-2xl font-bold mb-6">New client</h1>
<form method="POST" action="{{ route('clients.store') }}" class="bg-white rounded shadow p-6 max-w-lg space-y-4">
    @csrf
    @include('clients._form')
    <button class="bg-gray-900 text-white rounded px-4 py-2 text-sm hover:bg-gray-700">Save client</button>
</form>
@endsection
