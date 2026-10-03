@extends('layouts.app')

@section('title', $board['queueName'].' Queue - QueueFlow')
@section('body-class', 'bg-[#f7f6f2] text-customer-navy')

@section('content')
    <div class="mx-auto min-h-dvh w-full max-w-2xl overflow-hidden bg-customer-canvas pb-8 sm:my-6 sm:min-h-[calc(100dvh-3rem)] sm:rounded-[2rem] sm:shadow-[0_24px_70px_-32px_rgba(21,17,63,0.35)]">
        <header class="customer-hero pb-16">
            <div class="relative z-10 flex items-center justify-between gap-4">
                <a class="customer-back-link" href="{{ route('home') }}" aria-label="Back to QueueFlow home">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m14.5 6-6 6 6 6" /></svg>
                </a>
                <p class="text-lg font-bold">Live queue</p>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-2 text-[0.68rem] font-bold">
                    <span class="size-2 rounded-full bg-[#54e5ad]" aria-hidden="true"></span>
                    {{ $board['queueStatus'] }}
                </span>
            </div>
            <div class="relative z-10 mt-7">
                <h1 class="text-2xl font-bold tracking-[-0.035em]">{{ $board['businessName'] }}</h1>
                <p class="mt-1 text-sm text-white/75">{{ $board['branchName'] }} &middot; {{ $board['queueName'] }}</p>
                <p class="mt-1 text-xs text-white/55">Updated {{ strtolower($board['lastUpdated']) }}</p>
            </div>
        </header>

        <div class="relative z-10 -mt-7 px-5">
            <section aria-labelledby="serving-heading">
                <h2 id="serving-heading" class="sr-only">NOW SERVING</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    @forelse ($board['serving'] as $entry)
                        <article class="customer-ticket-card min-h-48">
                            <div class="relative z-10 flex h-full flex-col justify-between">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="text-[0.65rem] font-bold tracking-[0.14em] text-white/65">NOW SERVING</p>
                                    @if ($entry['counter'])
                                        <p class="rounded-full bg-customer-yellow px-3 py-1.5 text-[0.68rem] font-bold text-customer-navy">{{ $entry['counter'] }}</p>
                                    @endif
                                </div>
                                <p class="mt-5 break-words text-6xl leading-none font-bold tracking-[-0.065em] text-customer-yellow tabular-nums">{{ $entry['ticketNumber'] }}</p>
                                <p class="mt-5 truncate text-xs text-white/65">{{ $entry['serviceName'] }}</p>
                            </div>
                        </article>
                    @empty
                        <p class="rounded-[1.5rem] bg-customer-navy px-5 py-8 text-center text-sm text-white/65 sm:col-span-2">No tickets are being served right now.</p>
                    @endforelse
                </div>
                <dl class="mt-3 grid grid-cols-3 rounded-[1.25rem] bg-customer-navy px-5 py-4 text-white">
                    <div><dd class="text-xl font-bold">{{ $presentation['waiting'] }}</dd><dt class="text-[0.62rem] text-white/55">waiting</dt></div>
                    <div><dd class="text-xl font-bold">{{ $presentation['averageWaitMinutes'] }} min</dd><dt class="text-[0.62rem] text-white/55">average wait</dt></div>
                    <div><dd class="text-xl font-bold">{{ $presentation['servedToday'] }}</dd><dt class="text-[0.62rem] text-white/55">served today</dt></div>
                </dl>
            </section>

            <section class="mt-4" aria-labelledby="calling-heading">
                <h2 id="calling-heading" class="sr-only">CALLING</h2>
                <div class="grid gap-3">
                    @forelse ($board['calling'] as $entry)
                        <article class="flex items-center justify-between gap-4 rounded-[1.25rem] bg-customer-yellow px-5 py-4 text-customer-navy shadow-[0_12px_30px_-24px_rgba(21,17,63,0.5)]">
                            <div class="min-w-0">
                                <p class="text-[0.65rem] font-bold tracking-[0.13em]">CALLING</p>
                                <p class="mt-1 text-4xl leading-none font-bold tracking-[-0.05em] tabular-nums">{{ $entry['ticketNumber'] }}</p>
                                <p class="mt-1 truncate text-xs text-customer-navy/65">{{ $entry['serviceName'] }}</p>
                            </div>
                            @if ($entry['counter'])
                                <p class="shrink-0 text-sm font-bold">{{ $entry['counter'] }} <span aria-hidden="true">&rarr;</span></p>
                            @endif
                        </article>
                    @empty
                        <p class="rounded-[1.25rem] bg-white px-5 py-5 text-center text-sm text-customer-muted">No tickets are being called.</p>
                    @endforelse
                </div>
            </section>

            <section class="mt-5" aria-labelledby="waiting-heading">
                <div class="flex items-center justify-between gap-4">
                    <h2 id="waiting-heading" class="text-xs font-bold tracking-[0.12em]">UP NEXT</h2>
                    <p class="text-xs text-customer-muted">Waiting order</p>
                </div>
                <ol class="mt-3 divide-y divide-customer-line/70 overflow-hidden rounded-[1.5rem] bg-white px-4 py-1 shadow-[0_14px_35px_-26px_rgba(21,17,63,0.55)] ring-1 ring-customer-line/45">
                    @forelse ($board['waiting'] as $entry)
                        <li @class(['flex min-h-17 items-center gap-3 rounded-xl px-2 py-3', 'bg-customer-indigo/10 text-customer-indigo' => $ownedTicket?->ticketNumber === $entry['ticketNumber']])>
                            <span class="grid size-8 shrink-0 place-items-center rounded-full bg-customer-canvas text-xs font-bold text-customer-muted">{{ $loop->iteration }}</span>
                            <div class="min-w-0 grow">
                                <p class="text-xl font-bold tracking-[-0.03em] tabular-nums">{{ $entry['ticketNumber'] }}</p>
                                <p class="truncate text-xs text-customer-muted">{{ $entry['serviceName'] }}</p>
                            </div>
                            @if ($ownedTicket?->ticketNumber === $entry['ticketNumber'])<span class="rounded-full bg-customer-indigo px-2.5 py-1 text-[0.65rem] font-bold text-white">YOU</span>@endif
                            @if ($entry['counter'])
                                <span class="shrink-0 text-xs font-semibold text-customer-muted">{{ $entry['counter'] }}</span>
                            @endif
                        </li>
                    @empty
                        <li class="py-7 text-center text-sm text-customer-muted">No one is waiting.</li>
                    @endforelse
                </ol>
            </section>

            <p class="mt-4 text-center text-xs text-customer-muted">Recently served &middot; @foreach ($presentation['recentlyServed'] as $served){{ $served['ticketNumber'] }} {{ $served['minutesAgo'] }} min ago{{ ! $loop->last ? ' · ' : '' }}@endforeach</p>

            @if ($ownedTicket)
                <a class="mt-6 flex items-center justify-between rounded-full bg-customer-navy px-5 py-4 text-white shadow-[0_14px_35px_-24px_rgba(21,17,63,0.55)]" href="{{ route('queue-entries.show', [$ownedTicket->queueId, $ownedTicket->entryId]) }}"><span><span class="block text-sm font-bold">Your ticket: {{ $ownedTicket->ticketNumber }}</span><span class="text-xs text-white/55">Open your live ticket</span></span><span class="text-customer-yellow" aria-hidden="true">&rsaquo;</span></a>
            @else
                <a class="mt-6 flex items-center justify-between rounded-full bg-customer-navy px-5 py-4 text-white shadow-[0_14px_35px_-24px_rgba(21,17,63,0.55)]" href="{{ route('tickets.show') }}"><span class="text-sm font-bold">View my tickets</span><span class="text-customer-yellow" aria-hidden="true">&rsaquo;</span></a>
            @endif
        </div>
    </div>
@endsection
