@props(['ticket', 'presentation' => null, 'historical' => false])

@php
    $position = $ticket->position;
    $active = in_array($position->status, ['WAITING', 'CALLED', 'SERVING'], true);
    $called = $position->status === 'CALLED';
@endphp

<a
    {{ $attributes->class([
        'block rounded-[1.35rem] bg-white p-5 shadow-[0_18px_42px_-25px_rgba(21,17,63,0.62)] ring-1 transition hover:-translate-y-0.5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-customer-indigo',
        'ring-customer-yellow/70' => $called,
        'ring-customer-green/25' => $active && ! $called,
        'ring-customer-line/50' => ! $active,
    ]) }}
    href="{{ route('queue-entries.show', [$position->queueId, $position->entryId]) }}"
>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            @if ($position->businessName)<p class="truncate font-bold text-customer-navy">{{ $position->businessName }}</p>@endif
            @if ($position->serviceName)<p class="truncate text-sm text-customer-muted">{{ $position->serviceName }}</p>@endif
        </div>
        <span @class([
            'customer-status-badge shrink-0',
            'bg-customer-yellow text-customer-navy' => $called,
            'bg-customer-green/10 text-customer-green' => $active && ! $called,
            'bg-customer-indigo/10 text-customer-indigo' => ! $active,
        ])>{{ $position->status }}</span>
    </div>

    <div class="mt-4 flex items-end justify-between gap-4">
        <div class="min-w-0">
            <p class="text-[0.65rem] font-bold uppercase tracking-[0.15em] text-customer-muted">Queue number</p>
            <p class="mt-1 whitespace-nowrap font-sora text-[clamp(2.5rem,13vw,3.25rem)] font-extrabold leading-none tracking-[-0.08em] text-customer-indigo">{{ $position->ticketNumber }}</p>
        </div>
        @if ($active)
            <div class="shrink-0 text-right text-xs leading-5 text-customer-muted">
                <p><strong class="text-base tabular-nums text-customer-navy">{{ $position->peopleAhead }}</strong> ahead</p>
                <p><strong class="text-base tabular-nums text-customer-navy">{{ $position->estimatedWaitMinutes }} min</strong></p>
            </div>
        @endif
    </div>

    <div class="mt-5 flex items-center justify-between border-t border-customer-line/70 pt-4">
        <span class="text-sm font-bold text-customer-navy">{{ $active ? ($called ? "It's your turn" : ($position->status === 'SERVING' ? 'Now serving' : 'You\'re in the queue')) : 'Ticket '.$position->status }}</span>
        <span class="text-sm font-bold text-customer-indigo">View ticket details <span aria-hidden="true">&rarr;</span></span>
    </div>
</a>
