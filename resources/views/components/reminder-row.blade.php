@props(['reminder'])

@php
    $isDelivered = $reminder->delivered_at !== null;
    $isOverdue = ! $isDelivered && $reminder->remind_at->isPast();
@endphp

<li class="border-t border-line/60 px-5 py-4 first:border-t-0 sm:flex sm:items-start sm:justify-between sm:gap-6">
    <div class="flex min-w-0 items-baseline gap-3">
        <span
            @class([
                'mt-1.5 size-2 shrink-0 rounded-full',
                'bg-fog/40' => $isDelivered,
                'bg-ember' => $isOverdue,
                'bg-signal' => ! $isDelivered && ! $isOverdue,
            ])
            aria-hidden="true"
        ></span>

        <div class="min-w-0">
            <p @class(['text-[0.95rem] leading-snug', 'text-fog' => $isDelivered])>{{ $reminder->body }}</p>
            <p class="mt-1 font-mono text-xs text-fog">
                #{{ $reminder->id }} · {{ $reminder->telegramChat->name ?? 'unknown chat' }}
            </p>
        </div>
    </div>

    <div class="mt-2 pl-5 sm:mt-0 sm:pl-0 sm:text-right">
        <p class="font-mono text-sm whitespace-nowrap">{{ $reminder->remind_at->format('D j M, H:i') }}</p>
        <p
            @class([
                'mt-1 text-xs whitespace-nowrap',
                'text-fog' => ! $isOverdue,
                'text-ember' => $isOverdue,
            ])
        >
            @if ($isDelivered)
                sent {{ $reminder->delivered_at->diffForHumans() }}
            @elseif ($isOverdue)
                due now
            @else
                {{ $reminder->remind_at->diffForHumans() }}
            @endif
        </p>
    </div>
</li>
