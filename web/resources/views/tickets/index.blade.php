@extends('layouts.app')

@section('title', 'My Tickets - QueueFlow')
@section('body-class', 'bg-cream text-ink')

@section('content')
    <div class="mx-auto min-h-screen max-w-3xl px-5 pt-5 pb-[calc(7rem+env(safe-area-inset-bottom))] sm:px-6 sm:pt-8 md:pb-10">
        <x-customer-navigation />

        <header class="mt-10 border-b border-line pb-7 sm:mt-14">
            <p class="text-xs font-semibold tracking-[0.18em] text-brand">YOUR QUEUE</p>
            <h1 class="mt-3 font-editorial text-4xl leading-tight tracking-[-0.035em] sm:text-5xl">My Tickets</h1>
            <p class="mt-3 max-w-xl text-sm leading-6 text-muted">Tickets joined from this browser appear here.</p>
        </header>

        @if (session('status'))
            <p class="mt-6 rounded-xl border border-brand/20 bg-white px-4 py-3 text-sm font-medium text-brand">{{ session('status') }}</p>
        @endif

        <section class="py-7" aria-labelledby="owned-tickets-heading">
            <h2 id="owned-tickets-heading" class="text-lg font-semibold tracking-[-0.02em]">Owned tickets</h2>

            @if ($tickets === [])
                <div class="mt-4 rounded-2xl border border-line bg-white px-5 py-7">
                    <p class="font-editorial text-2xl font-semibold">No tickets yet</p>
                    <p class="mt-2 text-sm leading-6 text-muted">Choose a business and service to find an available queue.</p>
                    <a class="mt-5 inline-flex min-h-11 items-center text-sm font-semibold text-brand underline decoration-brand/35 underline-offset-4" href="{{ route('home') }}">Find a service</a>
                </div>
            @else
                <div class="mt-4 divide-y divide-line overflow-hidden rounded-2xl border border-line bg-white">
                    @foreach ($tickets as $ticket)
                        <a class="flex min-h-24 items-center gap-4 px-5 py-4 transition hover:bg-brand-soft/45" href="{{ route('queue-entries.show', [$ticket->queueId, $ticket->entryId]) }}">
                            <span class="min-w-0 grow">
                                <span class="block text-xs font-semibold tracking-[0.14em] text-brand">QUEUE TICKET</span>
                                <span class="mt-1 block font-editorial text-3xl font-semibold tracking-[-0.035em]">{{ $ticket->ticketNumber }}</span>
                            </span>
                            <span class="shrink-0 text-sm font-semibold text-brand">View status <span aria-hidden="true">&rarr;</span></span>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection
