@php $c = $client ?? null @endphp
<div>
    <label class="block text-sm font-medium">Name</label>
    <input name="name" value="{{ old('name', $c->name ?? '') }}" required class="mt-1 w-full rounded border-gray-300 border px-3 py-2">
</div>
<div>
    <label class="block text-sm font-medium">Email (reminders go here)</label>
    <input name="email" type="email" value="{{ old('email', $c->email ?? '') }}" required class="mt-1 w-full rounded border-gray-300 border px-3 py-2">
</div>
<div>
    <label class="block text-sm font-medium">Company</label>
    <input name="company" value="{{ old('company', $c->company ?? '') }}" class="mt-1 w-full rounded border-gray-300 border px-3 py-2">
</div>
<div>
    <label class="block text-sm font-medium">Phone</label>
    <input name="phone" value="{{ old('phone', $c->phone ?? '') }}" class="mt-1 w-full rounded border-gray-300 border px-3 py-2">
</div>
<div>
    <label class="block text-sm font-medium">Notes</label>
    <textarea name="notes" rows="3" class="mt-1 w-full rounded border-gray-300 border px-3 py-2">{{ old('notes', $c->notes ?? '') }}</textarea>
</div>
