<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Setting;

class ReminderService
{
    /**
     * The escalation ladder. Each step is sent once, when the invoice is at
     * least `days_after_due` days past its due date.
     *
     * @return array<int, array{days_after_due:int, template:string, subject:string, apply_late_fee?:bool}>
     */
    public static function ladder(): array
    {
        return Setting::get('reminder_schedule', [
            [
                'days_after_due' => 1,
                'template' => 'emails.reminder-gentle',
                'subject' => 'Friendly reminder: invoice {number} was due on {due_date}',
            ],
            [
                'days_after_due' => 7,
                'template' => 'emails.reminder-firm',
                'subject' => 'Invoice {number} is now 7 days overdue',
            ],
            [
                'days_after_due' => 14,
                'template' => 'emails.reminder-final',
                'subject' => 'Final notice: invoice {number} — a late fee has been applied',
                'apply_late_fee' => true,
            ],
        ]);
    }

    /**
     * The next ladder step this invoice is due for, or null if nothing to send.
     * The unique (invoice_id, step) constraint in reminder_logs guarantees
     * every step fires exactly once — rerunning the command is always safe.
     */
    public function nextStep(Invoice $invoice): ?array
    {
        if (! $invoice->is_overdue) {
            return null;
        }

        $sentSteps = $invoice->reminderLogs()->pluck('step');

        foreach (self::ladder() as $index => $step) {
            if ($invoice->days_overdue >= $step['days_after_due'] && ! $sentSteps->contains($index)) {
                return $step + ['index' => $index];
            }
        }

        return null;
    }

    public function renderSubject(array $step, Invoice $invoice): string
    {
        return strtr($step['subject'], [
            '{number}' => $invoice->number,
            '{due_date}' => $invoice->due_date->toFormattedDateString(),
        ]);
    }
}
