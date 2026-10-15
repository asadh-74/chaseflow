<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'ChaseFlow')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
@if(auth()->check())
<nav class="bg-gray-900 text-white">
    <div class="max-w-7xl mx-auto px-4 flex items-center justify-between h-14">
        <div class="flex items-center gap-6">
            <a href="{{ route('dashboard') }}" class="font-bold text-lg">ChaseFlow</a>
            <a href="{{ route('dashboard') }}" class="text-sm text-gray-300 hover:text-white">Dashboard</a>
            <a href="{{ route('invoices.index') }}" class="text-sm text-gray-300 hover:text-white">Invoices</a>
            <a href="{{ route('clients.index') }}" class="text-sm text-gray-300 hover:text-white">Clients</a>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="text-sm text-gray-300 hover:text-white">{{ auth()->user()->name }} · Logout</button>
        </form>
    </div>
</nav>
@endif

<main class="max-w-7xl mx-auto px-4 py-8">
    @if(session('success'))
        <div class="mb-4 rounded bg-green-100 border border-green-300 text-green-800 px-4 py-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded bg-red-100 border border-red-300 text-red-800 px-4 py-3">
            <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    @yield('content')
</main>
</body>
</html>
