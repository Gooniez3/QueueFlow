@props(['ticket', 'presentation', 'historical' => false])

@php
    $ownership = $ticket->ownership;
    $position = $ticket->position;
@endphp

<a
    {{ $attributes->class('block rounded-[1.6rem] bg-white p-5 shadow-[0_16px_38px_-25px_rgba(21,17,63,0.55)] ring-1 ring-customer-line/40') }}
    href="{{ route('queue-entries.show', [$ownership->queueId, $ownership->entryId]) }}"
>
    <div class="flex items-center justify-between gap-3">
        <div class="flex min-w-0 items-center gap-3">
            <span class="grid size-11 place-items-center rounded-xl bg-customer-indigo text-xs font-bold text-white">QF</span>
            <div>
                <p class="font-bold">QueueFlow ticket</p>
                <p class="text-xs text-customer-muted">{{ $historical ? 'Saved in ticket history' : 'Open for live status' }}</p>
            </div>
        </div>
        <span @class([
            'customer-status-badge',
            'bg-customer-green/10 text-customer-green' => ! $historical,
            'bg-customer-indigo/10 text-customer-indigo' => $historical,
        ])>{{ $position->status }}</span>
    </div>
    <div class="mt-5 grid grid-cols-[1fr_7rem] items-center gap-4">
        <div>
            <p class="text-[0.65rem] font-bold tracking-[0.13em] text-customer-muted">QUEUE NUMBER</p>
            <p class="mt-1 break-words text-5xl leading-none font-bold tracking-[-0.055em] text-customer-indigo">{{ $ownership->ticketNumber }}</p>
            <p class="mt-3 text-sm"><strong>{{ $presentation['partySize'] }}</strong> &middot; {{ $historical ? 'Final status saved' : 'Details update when opened' }}</p>
        </div>
        <x-demo-qr :pattern="$presentation['qrPattern']" />
    </div>
    <div class="mt-5 grid grid-cols-2 gap-y-4 border-t border-dashed border-customer-line pt-4 text-xs">
        <div><p class="text-customer-muted">Ticket ID</p><p class="mt-1 font-bold">QF-{{ $ownership->entryId }}</p></div>
        <div><p class="text-customer-muted">Date</p><p class="mt-1 font-bold">{{ $presentation['date'] }}</p></div>
        <div><p class="text-customer-muted">Joined</p><p class="mt-1 font-bold">{{ $presentation['joinedAt'] }}</p></div>
        <div><p class="text-customer-muted">Ownership</p><p class="mt-1 font-bold">Saved here</p></div>
    </div>
</a>
