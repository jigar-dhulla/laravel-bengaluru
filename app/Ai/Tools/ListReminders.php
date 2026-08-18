<?php

namespace App\Ai\Tools;

use App\Models\Reminder;
use App\Models\TelegramChat;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ListReminders implements Tool
{
    public function __construct(private readonly TelegramChat $chat) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'List the reminders the user has set, upcoming ones by default.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $includeDelivered = $request->boolean('include_delivered');

        $reminders = $this->chat->reminders()
            ->when(! $includeDelivered, fn ($query) => $query->pending())
            ->orderBy('remind_at')
            ->get();

        if ($reminders->isEmpty()) {
            return $includeDelivered ? 'No reminders at all.' : 'No reminders coming up.';
        }

        return $reminders->map(fn (Reminder $reminder): string => sprintf(
            '#%d: %s — %s%s',
            $reminder->id,
            $reminder->body,
            $reminder->remind_at->format('D j M, H:i'),
            $reminder->delivered_at ? ' (sent)' : '',
        ))->implode("\n");
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'include_delivered' => $schema->boolean()
                ->description('Also list reminders that have already been sent. Defaults to false.'),
        ];
    }
}
