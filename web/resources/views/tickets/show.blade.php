@extends('layouts.app')

@section('title', 'Ticket '.$ownership->ticketNumber.' - QueueFlow')
@section('body-class', 'bg-cream text-ink')

@section('content')
    <div class="mx-auto flex min-h-screen max-w-2xl flex-col px-5 pt-5 pb-[calc(7rem+env(safe-area-inset-bottom))] sm:px-6 sm:pt-8 md:pb-10 lg:pt-10">
        <x-customer-navigation />

        <header class="mt-10 sm:mt-14">
            <a class="inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-brand" href="{{ route('tickets.show') }}"><span aria-hidden="true">&larr;</span> My Tickets</a>
            <p class="mt-6 text-xs font-semibold tracking-[0.18em] text-brand">MY TICKET</p>
        </header>

        <article class="mt-4 rounded-2xl bg-brand p-5 text-white sm:p-6">
            <div class="flex items-center justify-between gap-4">
                <p class="text-xs font-medium text-white/70">Current status</p>
                <p class="rounded-full bg-amber px-4 py-2 text-xs font-bold tracking-[0.08em] text-amber-ink">{{ $status['label'] }}</p>
            </div>

            <p class="mt-5 text-6xl leading-none font-semibold tracking-[-0.065em] tabular-nums sm:text-7xl">{{ $ownership->ticketNumber }}</p>
            <h1 class="mt-4 font-editorial text-2xl font-semibold tracking-[-0.025em]">{{ $status['heading'] }}</h1>
            <p class="mt-2 text-sm leading-6 text-white/75">{{ $status['message'] }}</p>
        </article>

        @if ($status['showWaitingPosition'])
            <dl class="grid grid-cols-2 border-b border-line py-6">
                <div class="pr-5">
                    <dt class="text-xs text-muted">People ahead</dt>
                    <dd class="mt-1 text-3xl font-semibold tracking-[-0.035em] tabular-nums">{{ $position->peopleAhead }}</dd>
                </div>
                <div class="border-l border-line pl-5">
                    <dt class="text-xs text-muted">Estimated wait</dt>
                    <dd class="mt-2 text-lg font-semibold tracking-[-0.025em] tabular-nums">{{ $position->estimatedWaitMinutes }} {{ $position->estimatedWaitMinutes === 1 ? 'minute' : 'minutes' }}</dd>
                </div>
            </dl>
        @else
            <p class="border-b border-line py-6 text-sm leading-6 text-muted">Waiting position is not shown for this ticket status.</p>
        @endif

        <section class="py-6" aria-labelledby="ticket-actions-heading">
            <h2 id="ticket-actions-heading" class="text-lg font-semibold tracking-[-0.02em]">Latest status</h2>
            <p class="mt-2 text-sm leading-6 text-muted">QueueFlow updates this page when you refresh it.</p>
            <a class="mt-5 inline-flex min-h-12 w-full items-center justify-center rounded-xl border border-brand/25 bg-white px-5 py-3 text-sm font-semibold text-brand transition hover:bg-brand-soft/45 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand" href="{{ route('queue-entries.show', [$ownership->queueId, $ownership->entryId]) }}">
                Refresh status
            </a>
        </section>

        <footer class="mt-auto border-t border-line pt-6 pb-3">
            <a class="text-sm font-medium text-muted underline decoration-line underline-offset-4 transition hover:text-brand hover:decoration-brand" href="{{ route('home') }}"><span aria-hidden="true">&larr;</span> Back to discovery</a>
        </footer>
    </div>
@endsection
