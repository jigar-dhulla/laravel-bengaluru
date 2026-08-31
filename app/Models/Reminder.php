<?php

namespace App\Models;

use Database\Factories\ReminderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reminder extends Model
{
    /** @use HasFactory<ReminderFactory> */
    use HasFactory;

    protected $fillable = [
        'body',
        'remind_at',
        'delivered_at',
    ];

    /**
     * The chat the reminder was set from, and will be delivered to.
     *
     * @return BelongsTo<TelegramChat, $this>
     */
    public function telegramChat(): BelongsTo
    {
        return $this->belongsTo(TelegramChat::class);
    }

    /**
     * Scope the query to reminders that are due but have not been sent.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeDue(Builder $query): void
    {
        $query->whereNull('delivered_at')->where('remind_at', '<=', now());
    }

    /**
     * Scope the query to reminders that have not been sent yet.
     *
     * @param  Builder<$this>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->whereNull('delivered_at');
    }

    /**
     * Scope the query to reminders that have already been sent.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeDelivered(Builder $query): void
    {
        $query->whereNotNull('delivered_at');
    }

    protected function casts(): array
    {
        return [
            'remind_at' => 'immutable_datetime',
            'delivered_at' => 'immutable_datetime',
        ];
    }
}
