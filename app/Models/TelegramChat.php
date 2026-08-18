<?php

namespace App\Models;

use Database\Factories\TelegramChatFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramChat extends Model
{
    /** @use HasFactory<TelegramChatFactory> */
    use HasFactory;

    protected $fillable = [
        'telegram_id',
        'name',
        'conversation_id',
    ];

    /**
     * The reminders waiting to be delivered to this chat.
     *
     * @return HasMany<Reminder, $this>
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    protected function casts(): array
    {
        return [
            'telegram_id' => 'integer',
        ];
    }
}
