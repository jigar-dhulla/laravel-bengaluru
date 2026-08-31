<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use App\Models\TelegramChat;
use Illuminate\Contracts\View\View;

class ReminderController extends Controller
{
    /**
     * The number of delivered reminders kept on the page.
     */
    private const DELIVERED_LIMIT = 20;

    /**
     * How often the page reloads itself, so a reminder set in the chat shows up
     * here without anyone touching the browser during a demo.
     */
    private const REFRESH_SECONDS = 10;

    /**
     * Show every reminder the bot is holding.
     *
     * The page is read only. Reminders are created, listed and cancelled from
     * the Telegram chat, so this view is a window onto what the bot has done.
     */
    public function index(): View
    {
        return view('reminders.index', [
            'upcoming' => Reminder::pending()
                ->with('telegramChat')
                ->orderBy('remind_at')
                ->get(),
            'delivered' => Reminder::delivered()
                ->with('telegramChat')
                ->latest('delivered_at')
                ->limit(self::DELIVERED_LIMIT)
                ->get(),
            'deliveredCount' => Reminder::delivered()->count(),
            'chatCount' => TelegramChat::count(),
            'refreshSeconds' => self::REFRESH_SECONDS,
        ]);
    }
}
