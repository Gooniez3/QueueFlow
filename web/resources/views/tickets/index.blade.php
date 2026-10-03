@extends('layouts.app')

@section('title', 'My Tickets - QueueFlow')
@section('body-class', 'bg-[#f7f6f2] text-customer-navy')

@section('content')
    <div class="customer-page">
        <x-customer-navigation />
        <header class="customer-hero pb-16"><div class="relative z-10 flex items-start justify-between gap-4"><div><h1 class="customer-page-title">Tickets</h1><p class="customer-page-copy">Tickets only work in the browser where you joined.</p></div><span class="rounded-full bg-white/15 px-4 py-2 text-xs font-bold">History</span></div></header>

        <main class="relative z-10 -mt-6 px-5 pb-7">
            <div class="grid grid-cols-2 rounded-full bg-white p-1.5 shadow-[0_12px_30px_-22px_rgba(21,17,63,0.55)]"><p class="rounded-full bg-customer-navy px-4 py-3 text-center text-sm font-bold text-white">Active <span class="ml-1 inline-grid size-5 place-items-center rounded-full bg-customer-yellow text-[0.65rem] text-customer-navy">{{ count($activeTickets) }}</span></p><p class="px-4 py-3 text-center text-sm text-customer-muted">History <span class="ml-1">{{ count($historyTickets) }}</span></p></div>

            @if (session('status'))<p class="mt-4 rounded-2xl bg-customer-green/10 px-4 py-3 text-sm font-semibold text-customer-green" role="status">{{ session('status') }}</p>@endif

            <section class="mt-5" aria-label="Your tickets">
                @if ($activeTickets === [] && $historyTickets === [] && $unclassifiedTickets === [])
                    <div class="px-2 pt-14 text-center">
                        <div class="relative mx-auto h-24 w-40" aria-hidden="true"><span class="absolute top-1 left-5 h-20 w-28 -rotate-6 rounded-2xl bg-customer-indigo/10"></span><span class="absolute top-3 left-8 grid h-20 w-28 rotate-6 place-items-center rounded-2xl bg-customer-yellow text-2xl font-bold tracking-[-0.04em]">A-000</span></div>
                        <p class="mt-6 text-2xl font-bold tracking-[-0.035em]">No tickets yet</p><p class="mx-auto mt-2 max-w-xs text-sm leading-5 text-customer-muted">Scan the QR code at the counter to join a queue and get your ticket.</p>
                        <a class="customer-primary-button mt-7" href="{{ route('scanner.show') }}">Scan QR code</a><a class="customer-text-link mt-3" href="{{ route('places.index') }}">Browse places &rsaquo;</a>
                    </div>
                @endif

                @if ($activeTickets !== [])
                    <h2 class="text-sm font-bold tracking-[-0.02em]">Active tickets</h2>
                    <div class="grid gap-4">
                        @foreach ($activeTickets as $ticket)
                            <x-customer-saved-ticket-card class="mt-3" :ticket="$ticket" :presentation="$presentation" />
                        @endforeach
                    </div>
                @endif

                @if ($historyTickets !== [])
                    <h2 class="mt-7 text-sm font-bold tracking-[-0.02em]">Ticket history</h2>
                    <div class="grid gap-4">
                        @foreach ($historyTickets as $ticket)
                            <x-customer-saved-ticket-card class="mt-3" :ticket="$ticket" :presentation="$presentation" historical />
                        @endforeach
                    </div>
                @endif

                @if ($unclassifiedTickets !== [])
                    <div class="customer-empty-state mt-7">
                        <p class="text-sm font-bold">Some ticket statuses are unavailable.</p>
                        <p class="mt-1 text-xs text-customer-muted">Open a saved ticket for its latest status.</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($unclassifiedTickets as $ticket)
                                <a class="customer-text-link" href="{{ route('queue-entries.show', [$ticket->ownership->queueId, $ticket->ownership->entryId]) }}">Ticket {{ $ticket->ownership->ticketNumber }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>
        </main>
    </div>
@endsection
