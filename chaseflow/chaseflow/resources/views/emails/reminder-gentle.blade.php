<x-mail::message>
# Friendly reminder — {{ $invoice->number }}

Hi {{ $client->name }},

Just a quick note that invoice **{{ $invoice->number }}**
({{ $invoice->currency }} {{ number_format($totalDue, 2) }}) was due on
{{ $invoice->due_date->toFormattedDateString() }} and is now {{ $daysOverdue }} day(s) past due.

If you've already sent payment, please ignore this note — and thank you.

<x-mail::button :url="$payUrl">
View invoice
</x-mail::button>

Thanks,<br>
{{ $invoice->user->name }}
</x-mail::message>
