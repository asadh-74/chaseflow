<?php

namespace App\Http\Controllers;

use App\Jobs\SendInvoiceReminder;
use App\Mail\InvoiceSentMail;
use App\Models\Client;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all');

        $invoices = Invoice::with('client')
            ->when($filter === 'overdue', fn ($q) => $q->overdue())
            ->when($filter === 'sent', fn ($q) => $q->where('status', Invoice::STATUS_SENT)->whereDate('due_date', '>=', today()))
            ->when($filter === 'draft', fn ($q) => $q->where('status', Invoice::STATUS_DRAFT))
            ->when($filter === 'paid', fn ($q) => $q->whereNotNull('paid_at'))
            ->orderByRaw("CASE WHEN paid_at IS NULL AND due_date < CURDATE() THEN 0 ELSE 1 END")
            ->orderBy('due_date')
            ->paginate(15)
            ->withQueryString();

        return view('invoices.index', compact('invoices', 'filter'));
    }

    public function create()
    {
        $clients = auth()->user()->clients()->orderBy('name')->get();
        $nextNumber = 'INV-'.str_pad((string) ((Invoice::max('id') ?? 0) + 10001), 5, '0', STR_PAD_LEFT);

        return view('invoices.create', compact('clients', 'nextNumber'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'number' => ['required', 'string', 'max:50', 'unique:invoices,number'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3'],
            'due_date' => ['required', 'date'],
        ]);

        $client = Client::findOrFail($data['client_id']);
        abort_unless($client->user_id === $request->user()->id, 403);

        $invoice = $request->user()->invoices()->create($data + ['status' => Invoice::STATUS_DRAFT]);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice created.');
    }

    public function show(Invoice $invoice)
    {
        $this->authorizeOwner($invoice);
        $invoice->load(['client', 'reminderLogs']);

        return view('invoices.show', compact('invoice'));
    }

    /**
     * Generate the PDF, store it on the configured disk (S3 in production),
     * email it to the client and mark the invoice as sent.
     */
    public function send(Invoice $invoice)
    {
        $this->authorizeOwner($invoice);

        $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $invoice]);
        $path = "invoices/{$invoice->number}.pdf";
        Storage::disk(config('filesystems.default'))->put($path, $pdf->output());

        $invoice->update([
            'status' => Invoice::STATUS_SENT,
            'sent_at' => now(),
            'pdf_path' => $path,
        ]);

        Mail::to($invoice->client->email)->queue(new InvoiceSentMail($invoice->refresh(), $path));

        return back()->with('success', "Invoice {$invoice->number} sent to {$invoice->client->email}.");
    }

    public function markPaid(Invoice $invoice)
    {
        $this->authorizeOwner($invoice);

        $invoice->update([
            'status' => Invoice::STATUS_PAID,
            'paid_at' => now(),
        ]);

        return back()->with('success', "Invoice {$invoice->number} marked as paid. Reminders stopped.");
    }

    /**
     * Manual "nudge": runs the same job the nightly cron uses.
     */
    public function remind(Invoice $invoice)
    {
        $this->authorizeOwner($invoice);

        SendInvoiceReminder::dispatch($invoice->refresh())->onQueue('mail');

        return back()->with('success', 'Reminder queued — it will be sent by the worker momentarily.');
    }

    public function destroy(Invoice $invoice)
    {
        $this->authorizeOwner($invoice);

        if ($invoice->pdf_path && Storage::disk(config('filesystems.default'))->exists($invoice->pdf_path)) {
            Storage::disk(config('filesystems.default'))->delete($invoice->pdf_path);
        }

        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', 'Invoice deleted.');
    }

    private function authorizeOwner(Invoice $invoice): void
    {
        abort_unless($invoice->user_id === auth()->id(), 403);
    }
}
