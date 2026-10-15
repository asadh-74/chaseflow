<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'client_id', 'user_id', 'number', 'amount', 'currency',
        'due_date', 'status', 'late_fee', 'pdf_path', 'sent_at', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
            'amount' => 'decimal:2',
            'late_fee' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reminderLogs(): HasMany
    {
        return $this->hasMany(ReminderLog::class)->orderBy('sent_at');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNull('paid_at')
            ->where('status', self::STATUS_SENT)
            ->whereDate('due_date', '<', today());
    }

    public function getTotalAmountAttribute(): float
    {
        return (float) $this->amount + (float) $this->late_fee;
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === self::STATUS_SENT
            && $this->paid_at === null
            && $this->due_date->isPast();
    }

    public function getDaysOverdueAttribute(): int
    {
        return $this->is_overdue ? (int) $this->due_date->diffInDays(today()) : 0;
    }
}
