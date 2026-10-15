<x-mail::message>
# Invoice {{ $invoice->number }} is 7 days overdue

Hi {{ $client->name }},

Invoice **{{ $invoice->number }}** for
**{{ $invoice->currency }} {{ number_format($totalDue, 2) }}**
is now **{{ $daysOverdue }} days past due**.

Kindly arrange payment this week, or reply to this email if there's a problem
with the invoice and we'll sort it out.

<x-mail::button :url="$payUrl">
View invoice
</x-mail::button>

Regards,<br>
{{ $invoice->user->name }}
</x-mail::message>
