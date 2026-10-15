<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #222; font-size: 13px; }
        .header { border-bottom: 2px solid #111; padding-bottom: 12px; margin-bottom: 24px; }
        .header h1 { margin: 0; font-size: 22px; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
        .totals { margin-top: 16px; width: 40%; margin-left: auto; }
        .totals td { text-align: right; }
        .big { font-weight: bold; font-size: 15px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>INVOICE {{ $invoice->number }}</h1>
        <div>Issued by {{ $invoice->user->name }} · {{ $invoice->sent_at?->toFormattedDateString() ?? now()->toFormattedDateString() }}</div>
    </div>

    <p><strong>Bill to:</strong><br>
        {{ $invoice->client->name }}<br>
        {{ $invoice->client->company }}<br>
        {{ $invoice->client->email }}</p>

    <table>
        <thead><tr><th>Description</th><th style="text-align:right">Amount</th></tr></thead>
        <tbody>
            <tr>
                <td>Professional services — invoice {{ $invoice->number }}</td>
                <td style="text-align:right">{{ $invoice->currency }} {{ number_format((float) $invoice->amount, 2) }}</td>
            </tr>
            @if((float) $invoice->late_fee > 0)
            <tr>
                <td>Late fee</td>
                <td style="text-align:right">{{ $invoice->currency }} {{ number_format((float) $invoice->late_fee, 2) }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Due date:</td><td>{{ $invoice->due_date->toFormattedDateString() }}</td></tr>
        <tr class="big"><td>Total due:</td><td>{{ $invoice->currency }} {{ number_format($invoice->total_amount, 2) }}</td></tr>
    </table>
</body>
</html>
