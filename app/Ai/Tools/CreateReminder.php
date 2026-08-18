<?php

namespace App\Ai\Tools;

use App\Models\TelegramChat;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Validator;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreateReminder implements Tool
{
    /**
     * The datetime shapes a model is allowed to hand us.
     */
    private const FORMATS = 'Y-m-d H:i,Y-m-d\TH:i,Y-m-d H:i:s,Y-m-d\TH:i:s';

    public function __construct(private readonly TelegramChat $chat) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Set a reminder to be delivered to the user later. Work the date out from the current time in your instructions and pass it as YYYY-MM-DD HH:MM.';
    }

    /**
     * Execute the tool.
     *
     * A broken rule is not an error to log, it is the next thing the model
     * reads, so every message says what to ask the user for.
     */
    public function handle(Request $request): Stringable|string
    {
        $validator = Validator::make($request->all(), [
            'body' => ['required', 'string', 'max:255'],
            // Without bail, "after" tries to parse a string the format rule has
            // already thrown out.
            'remind_at' => ['bail', 'required', 'date_format:'.self::FORMATS, 'after:now'],
        ], [
            'body.required' => 'Ask the user what they want to be reminded about.',
            'remind_at.required' => 'Ask the user when they want the reminder, then pass it as YYYY-MM-DD HH:MM.',
            'remind_at.date_format' => 'That is not a date I can read. Ask the user for a specific day and time, then pass it as YYYY-MM-DD HH:MM.',
            'remind_at.after' => 'That time has already gone by. Tell the user, and ask for one in the future.',
        ]);

        if ($validator->fails()) {
            return $validator->errors()->first();
        }

        $validated = $validator->validated();

        $reminder = $this->chat->reminders()->create([
            'body' => trim($validated['body']),
            'remind_at' => $validated['remind_at'],
        ]);

        return "Reminder #{$reminder->id} set for {$reminder->remind_at->format('D j M Y, H:i')}.";
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'body' => $schema->string()
                ->description('What to remind the user about, written from their point of view.')
                ->required(),
            'remind_at' => $schema->string()
                ->description('When to send it, as YYYY-MM-DD HH:MM. Resolve phrases like "tomorrow at 9" against the current time given in your instructions.')
                ->required(),
        ];
    }
}
