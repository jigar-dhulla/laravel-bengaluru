# Agentic Telegram Reminder Bot

A minimal Laravel app that puts an AI agent behind a Telegram bot, built with the
[Laravel AI SDK](https://github.com/laravel/ai).

You tell it what to remind you about in
whatever words you like; it works out the date, checks it, remembers the
conversation, and messages you when the time comes.

There is a conference talk about it in `public/slides.html` — open it in a
browser, or serve the app and visit `/slides.html`.

## How a message flows

```
telegram:poll ────────────────────────┐
                                      ├─▶ TelegramBot ─▶ TelegramAssistant ─▶ Gemini
POST /telegram/webhook                │                   (agent + tools)       │
  └─▶ HandleTelegramUpdate (queued) ──┘                         ▲               │
                                                                └── CreateReminder ◀┘
                                                                    ListReminders
                                                                    CancelReminder

reminders:deliver (every minute) ─────────────────────────────▶ Telegram
```

| File | Role |
| --- | --- |
| `app/Services/Telegram.php` | Thin wrapper over the Telegram Bot HTTP API |
| `app/Jobs/HandleTelegramUpdate.php` | Answers a webhook update off the request |
| `app/Services/TelegramBot.php` | Turns an update into a reply |
| `app/Ai/Agents/TelegramAssistant.php` | The agent: instructions, memory, tools |
| `app/Ai/Tools/*.php` | What the agent is allowed to *do* |
| `app/Console/Commands/DeliverReminders.php` | Sends the reminders that have come due |
| `app/Console/Commands/TelegramPoll.php` | Runs the bot locally, no public URL needed |

## Setup

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate
```

Then fill in two values in `.env`:

```env
TELEGRAM_BOT_TOKEN=   # from @BotFather
GEMINI_API_KEY=       # https://aistudio.google.com/apikey
```

`AI_PROVIDER` selects the lab. Swap it for `openai`, `anthropic`, `ollama` or
anything else in `config/ai.php` and no application code changes.

## Running it

Locally, use long polling — nothing to expose, no tunnel:

```bash
php artisan telegram:poll
```

In production, use the webhook instead. Set `TELEGRAM_WEBHOOK_SECRET` in `.env`,
then point Telegram at your public URL:

```bash
php artisan telegram:webhook https://example.com/telegram/webhook
php artisan telegram:webhook --remove
```

The webhook verifies Telegram's `X-Telegram-Bot-Api-Secret-Token` header on
every request.

## Talking to it

| Message | What happens |
| --- | --- |
| `/start` | Canned welcome, no model call |
| `/forget` | Drops the conversation and starts fresh |
| "Remind me to call the dentist tomorrow at 9" | The model works the date out from the clock in its instructions and calls `CreateReminder` |
| "Remind me sometime next week" | `CreateReminder` refuses the date, and the model asks which day |
| "What have I got coming up?" | The model calls `ListReminders` |
| "Cancel the dentist one" | `ListReminders`, then `CancelReminder` |

## How a reminder is set

A model has no clock, so the current moment is written into the agent's
instructions on every call. The model resolves "tomorrow at 9" itself, and
`CreateReminder` checks the answer before anything is written:

```php
$validator = Validator::make($request->all(), [
    'body' => ['required', 'string', 'max:255'],
    'remind_at' => ['bail', 'required', 'date_format:'.self::FORMATS, 'after:now'],
], [
    'remind_at.date_format' => 'That is not a date I can read. Ask the user for a specific day and time, then pass it as YYYY-MM-DD HH:MM.',
]);

if ($validator->fails()) {
    return $validator->errors()->first();
}
```

A failed rule is not an error to log — it is the next thing the model reads, so
every message says what to ask the user for. `bail` matters: without it `after`
tries to parse a string the format rule has already thrown out.

Everything runs in one time zone, the app's. Set `APP_TIMEZONE` in `.env` if you
want the bot's clock to match the room you are demoing in.

Delivery is the one thing the bot does without being spoken to first, and it is
a scheduled command rather than a model call:

```bash
php artisan schedule:work      # or a real cron entry in production
php artisan reminders:deliver  # or run it by hand
```

A reminder the Bot API refuses is left undelivered so the next minute tries
again.

## Tests

```bash
php artisan test
```

The suite fakes the model with `TelegramAssistant::fake()` and the Bot API with
`Http::fake()`, so it never touches the network.

## Notes for production

Webhook updates are answered in a queued job, so Telegram gets its `204` while
the model is still thinking and never retries the update. Run a worker next to
the app:

```bash
php artisan queue:work
php artisan schedule:work
```

`telegram:poll` answers inline instead, which keeps local development to one
process — though reminders still need the scheduler running to go out.

Set `AI_FAILOVER_PROVIDERS` to a comma separated list — `openai,anthropic` — and
a rate limited or overloaded provider falls through to the next one rather than
failing the reply. Each provider you list needs its key in `.env`.
