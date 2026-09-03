<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scheme-dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Thank you · {{ config('app.name', 'Laravel') }}</title>

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-ink text-paper antialiased">
        <div class="mx-auto flex min-h-screen max-w-md flex-col px-6 py-12">
            <header>
                <p class="font-mono text-xs tracking-[0.2em] text-fog uppercase">Laravel Bengaluru</p>
                <h1 class="mt-3 text-4xl font-bold tracking-tight">Thank you</h1>
                <p class="mt-3 text-sm leading-relaxed text-fog">
                    Building an agentic Telegram bot. Everything from the talk is below.
                </p>
            </header>

            <ul class="mt-8 grid gap-3">
                @foreach ([
                    ['label' => 'Feedback', 'text' => 'how was the talk?', 'href' => 'https://forms.gle/rwjXKDxRBH8S8beq5'],
                    ['label' => 'Twitter', 'text' => '@jigar_dhulla', 'href' => 'https://twitter.com/jigar_dhulla'],
                    ['label' => 'Slides', 'text' => 'the deck from this talk', 'href' => url('/slides.html')],
                    ['label' => 'GitHub', 'text' => 'jigar-dhulla/laravel-bengaluru', 'href' => 'https://github.com/jigar-dhulla/laravel-bengaluru'],
                    ['label' => 'Laravel WhatsApp', 'text' => 'jigar-dhulla/laravel-whatsapp-ai-agent', 'href' => 'https://github.com/jigar-dhulla/laravel-whatsapp-ai-agent'],
                ] as $link)
                    <li>
                        <a
                            href="{{ $link['href'] }}"
                            class="flex items-center justify-between gap-4 rounded-xl border border-line bg-ink-1 px-5 py-4 transition hover:border-signal"
                        >
                            <span class="min-w-0">
                                <span class="block font-mono text-xs tracking-[0.16em] text-signal uppercase">
                                    {{ $link['label'] }}
                                </span>
                                <span class="mt-1 block truncate text-[0.95rem]">{{ $link['text'] }}</span>
                            </span>
                            <span class="shrink-0 text-fog" aria-hidden="true">&rarr;</span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <footer class="mt-auto pt-10">
                <a href="{{ route('reminders.index') }}" class="font-mono text-xs text-fog hover:text-signal">
                    see the bot's reminders &rarr;
                </a>
            </footer>
        </div>
    </body>
</html>
