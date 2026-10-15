<x-mail::message>
# Invoice {{ $invoice->number }}

Hi {{ $invoice->client->name }},

Please find attached invoice **{{ $invoice->number }}** for
**{{ $invoice->currency }} {{ number_format((float) $invoice->amount, 2) }}**,
due on **{{ $invoice->due_date->toFormattedDateString() }}**.

<x-mail::button :url="$payUrl">
View invoice
</x-mail::button>

Thanks,<br>
{{ $invoice->user->name }}
</x-mail::message>
