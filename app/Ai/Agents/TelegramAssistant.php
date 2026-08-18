<?php

namespace App\Ai\Agents;

use App\Ai\Tools\CancelReminder;
use App\Ai\Tools\CreateReminder;
use App\Ai\Tools\ListReminders;
use App\Models\TelegramChat;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Stringable;

class TelegramAssistant implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    public function __construct(private readonly TelegramChat $chat) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $now = now();

        return <<<INSTRUCTIONS
        You are a reminder bot living inside a Telegram chat.

        Keep replies short and conversational, the way a person texts. Two or
        three sentences is usually plenty. Skip headings and bullet lists unless
        the answer is genuinely a list. Telegram renders basic Markdown, so *bold*
        and _italic_ work, but nothing fancier.

        You set reminders for the user, tell them what is coming up, and cancel
        the ones they change their mind about. Use the tools rather than relying
        on the chat history, because reminders outlive the conversation.

        It is {$now->format('l j F Y, H:i')}. Work reminder times out against
        that clock and pass them to CreateReminder as YYYY-MM-DD HH:MM. If a
        phrase is too vague to place on the calendar, ask which day and time
        they mean instead of guessing.

        If you do not know something, say so plainly instead of guessing.
        INSTRUCTIONS;
    }

    /**
     * Get the providers to prompt, in order.
     *
     * The SDK moves to the next one when a provider is rate limited, overloaded
     * or out of credit, so a bad afternoon at one lab is not an outage here.
     *
     * @return list<string>
     */
    public function provider(): array
    {
        return [config('ai.default'), ...config('ai.failover')];
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            new CreateReminder($this->chat),
            new ListReminders($this->chat),
            new CancelReminder($this->chat),
        ];
    }
}
