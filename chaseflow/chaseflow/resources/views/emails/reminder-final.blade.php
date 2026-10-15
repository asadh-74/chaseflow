<x-mail::message>
# Final notice — invoice {{ $invoice->number }}

Hi {{ $client->name }},

Despite previous reminders, invoice **{{ $invoice->number }}**
remains unpaid and is now **{{ $daysOverdue }} days overdue**.

Per our terms, a late fee of
**{{ $invoice->currency }} {{ number_format((float) $invoice->late_fee, 2) }}**
has been applied, bringing the total due to
**{{ $invoice->currency }} {{ number_format($totalDue, 2) }}**.

Please pay within 7 days to avoid further action. If you're experiencing
difficulties, reply to this email — we're happy to set up a payment plan.

<x-mail::button :url="$payUrl">
View invoice
</x-mail::button>

{{ $invoice->user->name }}
</x-mail::message>
