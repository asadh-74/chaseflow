@extends('layouts.app')
@section('title', 'Login · ChaseFlow')
@section('content')
<div class="max-w-sm mx-auto mt-20 bg-white rounded shadow p-8">
    <h1 class="text-2xl font-bold mb-6">ChaseFlow</h1>
    <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium">Email</label>
            <input name="email" type="email" value="{{ old('email') }}" required
                   class="mt-1 w-full rounded border-gray-300 border px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium">Password</label>
            <input name="password" type="password" required
                   class="mt-1 w-full rounded border-gray-300 border px-3 py-2">
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember"> Remember me
        </label>
        <button class="w-full bg-gray-900 text-white rounded py-2 font-medium hover:bg-gray-700">
            Sign in
        </button>
    </form>
    <p class="mt-4 text-xs text-gray-500">Demo: admin@chaseflow.test / password</p>
</div>
@endsection
