<?php

namespace App\Ai\Tools;

use App\Models\TelegramChat;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CancelReminder implements Tool
{
    public function __construct(private readonly TelegramChat $chat) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Cancel one of the user\'s reminders by its id. Use ListReminders first if you do not know the id.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $id = $request->integer('id');

        $cancelled = $this->chat->reminders()->pending()->whereKey($id)->delete();

        return $cancelled > 0
            ? "Cancelled reminder #{$id}."
            : "No reminder #{$id} is waiting to be sent.";
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('The id of the reminder to cancel.')
                ->required(),
        ];
    }
}
