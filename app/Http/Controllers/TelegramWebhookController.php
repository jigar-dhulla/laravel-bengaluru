<?php

namespace App\Http\Controllers;

use App\Jobs\HandleTelegramUpdate;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TelegramWebhookController extends Controller
{
    /**
     * Handle an update pushed to us by Telegram.
     */
    public function __invoke(Request $request): Response
    {
        $secret = (string) config('services.telegram.webhook_secret');

        abort_if(
            $secret === '' || ! hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token')),
            403,
        );

        HandleTelegramUpdate::dispatch($request->all());

        return response()->noContent();
    }
}
