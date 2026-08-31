<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scheme-dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Reminders · {{ config('app.name', 'Laravel') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-ink text-paper antialiased">
        <div class="mx-auto max-w-3xl px-6 py-12 sm:py-16">
            <header class="border-b border-line pb-6">
                <p class="font-mono text-xs tracking-[0.2em] text-fog uppercase">Telegram reminder bot</p>

                <div class="mt-3 flex flex-wrap items-baseline justify-between gap-x-6 gap-y-2">
                    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">Reminders</h1>

                    <p class="flex items-center gap-2 font-mono text-xs text-fog">
                        <span class="size-2 animate-pulse rounded-full bg-mint" aria-hidden="true"></span>
                        refreshing every {{ $refreshSeconds }}s
                    </p>
                </div>

                <p class="mt-3 max-w-xl text-sm leading-relaxed text-fog">
                    Everything the bot is holding. Reminders are set, listed and cancelled from the Telegram
                    chat — this page only reads them back.
                </p>
            </header>

            <dl class="mt-8 grid grid-cols-3 gap-3 sm:gap-4">
                @foreach ([
                    ['label' => 'Upcoming', 'value' => $upcoming->count(), 'tint' => 'text-signal'],
                    ['label' => 'Sent', 'value' => $deliveredCount, 'tint' => 'text-mint'],
                    ['label' => 'Chats', 'value' => $chatCount, 'tint' => 'text-paper'],
                ] as $stat)
                    <div class="rounded-lg border border-line bg-ink-1 px-4 py-3">
                        <dt class="font-mono text-xs tracking-wider text-fog uppercase">{{ $stat['label'] }}</dt>
                        <dd class="mt-1 text-2xl font-semibold {{ $stat['tint'] }}">{{ $stat['value'] }}</dd>
                    </div>
                @endforeach
            </dl>

            <section class="mt-10">
                <h2 class="font-mono text-xs tracking-[0.18em] text-fog uppercase">Upcoming</h2>

                <ul class="mt-3 overflow-hidden rounded-lg border border-line bg-ink-1">
                    @forelse ($upcoming as $reminder)
                        <x-reminder-row :$reminder />
                    @empty
                        <li class="px-5 py-8 text-center text-sm text-fog">
                            Nothing waiting. Message the bot on Telegram to set one.
                        </li>
                    @endforelse
                </ul>
            </section>

            @if ($delivered->isNotEmpty())
                <section class="mt-10">
                    <h2 class="font-mono text-xs tracking-[0.18em] text-fog uppercase">
                        Sent
                        @if ($deliveredCount > $delivered->count())
                            <span class="text-fog/70">· last {{ $delivered->count() }} of {{ $deliveredCount }}</span>
                        @endif
                    </h2>

                    <ul class="mt-3 overflow-hidden rounded-lg border border-line bg-ink-1">
                        @foreach ($delivered as $reminder)
                            <x-reminder-row :$reminder />
                        @endforeach
                    </ul>
                </section>
            @endif

            <footer class="mt-12 border-t border-line pt-6 font-mono text-xs text-fog">
                read only · {{ now()->format('D j M Y, H:i:s') }}
            </footer>
        </div>

        <script>
            setTimeout(() => window.location.reload(), {{ $refreshSeconds * 1000 }});
        </script>
    </body>
</html>
