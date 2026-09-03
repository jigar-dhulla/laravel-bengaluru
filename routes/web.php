<?php

use App\Http\Controllers\ReminderController;
use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ReminderController::class, 'index'])
    ->name('reminders.index');

Route::view('/thank-you', 'thank-you')
    ->name('thank-you');

Route::post('/telegram/webhook', TelegramWebhookController::class)
    ->name('telegram.webhook');
