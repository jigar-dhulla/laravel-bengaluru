<?php

namespace App\Providers;

use App\Services\Telegram;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Telegram::class, fn (): Telegram => new Telegram(
            (string) config('services.telegram.token'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
