@extends('layouts.app')

@section('title', 'My Tickets - QueueFlow')
@section('body-class', 'bg-[#f7f6f2] text-customer-navy')

@section('content')
    <div class="customer-page">
        <x-customer-navigation />
        <header class="customer-hero pb-16"><div class="relative z-10 flex items-start justify-between gap-4"><div><h1 class="customer-page-title">Tickets</h1><p class="customer-page-copy">Tickets only work in the browser where you joined.</p></div><span class="rounded-full bg-white/15 px-4 py-2 text-xs font-bold">History</span></div></header>

        <main class="relative z-10 -mt-6 px-5 pb-7">
            <div class="grid grid-cols-2 rounded-full bg-white p-1.5 shadow-[0_12px_30px_-22px_rgba(21,17,63,0.55)]"><p class="rounded-full bg-customer-navy px-4 py-3 text-center text-sm font-bold text-white">Active <span class="ml-1 inline-grid size-5 place-items-center rounded-full bg-customer-yellow text-[0.65rem] text-customer-navy">{{ count($tickets) }}</span></p><p class="px-4 py-3 text-center text-sm text-customer-muted">Past</p></div>

            @if (session('status'))<p class="mt-4 rounded-2xl bg-customer-green/10 px-4 py-3 text-sm font-semibold text-customer-green" role="status">{{ session('status') }}</p>@endif

            <section class="mt-5" aria-label="Your tickets">
                @if ($tickets === [])
                    <div class="px-2 pt-14 text-center">
                        <div class="relative mx-auto h-24 w-40" aria-hidden="true"><span class="absolute top-1 left-5 h-20 w-28 -rotate-6 rounded-2xl bg-customer-indigo/10"></span><span class="absolute top-3 left-8 grid h-20 w-28 rotate-6 place-items-center rounded-2xl bg-customer-yellow text-2xl font-bold tracking-[-0.04em]">A-000</span></div>
                        <p class="mt-6 text-2xl font-bold tracking-[-0.035em]">No tickets yet</p><p class="mx-auto mt-2 max-w-xs text-sm leading-5 text-customer-muted">Scan the QR code at the counter to join a queue and get your ticket.</p>
                        <a class="customer-primary-button mt-7" href="{{ route('scanner.show') }}">Scan QR code</a><a class="customer-text-link mt-3" href="{{ route('places.index') }}">Browse places &rsaquo;</a>
                    </div>
                @else
                    <div class="grid gap-4">
                        @foreach ($tickets as $ticket)
                            <a class="block rounded-[1.6rem] bg-white p-5 shadow-[0_16px_38px_-25px_rgba(21,17,63,0.55)] ring-1 ring-customer-line/40" href="{{ route('queue-entries.show', [$ticket->queueId, $ticket->entryId]) }}">
                                <div class="flex items-center justify-between gap-3"><div class="flex min-w-0 items-center gap-3"><span class="grid size-11 place-items-center rounded-xl bg-customer-indigo text-xs font-bold text-white">QF</span><div><p class="font-bold">QueueFlow ticket</p><p class="text-xs text-customer-muted">Open for live status</p></div></div><span class="customer-status-badge bg-customer-indigo/10 text-customer-indigo">Saved</span></div>
                                <div class="mt-5 grid grid-cols-[1fr_7rem] items-center gap-4"><div><p class="text-[0.65rem] font-bold tracking-[0.13em] text-customer-muted">QUEUE NUMBER</p><p class="mt-1 break-words text-5xl leading-none font-bold tracking-[-0.055em] text-customer-indigo">{{ $ticket->ticketNumber }}</p><p class="mt-3 text-sm"><strong>{{ $presentation['partySize'] }}</strong> &middot; Details update when opened</p></div><x-demo-qr :pattern="$presentation['qrPattern']" /></div>
                                <div class="mt-5 grid grid-cols-2 gap-y-4 border-t border-dashed border-customer-line pt-4 text-xs"><div><p class="text-customer-muted">Ticket ID</p><p class="mt-1 font-bold">QF-{{ $ticket->entryId }}</p></div><div><p class="text-customer-muted">Date</p><p class="mt-1 font-bold">{{ $presentation['date'] }}</p></div><div><p class="text-customer-muted">Joined</p><p class="mt-1 font-bold">{{ $presentation['joinedAt'] }}</p></div><div><p class="text-customer-muted">Ownership</p><p class="mt-1 font-bold">Saved here</p></div></div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        </main>
    </div>
@endsection
