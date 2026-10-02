@extends('layouts.app')

@section('title', $board['queueName'].' Queue - QueueFlow')
@section('body-class', 'bg-board text-board-ink')

@section('content')
    <div class="mx-auto flex min-h-screen max-w-[90rem] flex-col px-4 py-6 sm:px-7 sm:py-8 lg:px-10 lg:py-10 xl:px-14">
        <header class="flex flex-col gap-7 sm:flex-row sm:items-center sm:justify-between">
            <a class="inline-flex w-fit items-center gap-3 text-board-ink" href="{{ route('queue-board.show') }}" aria-label="QueueFlow queue board">
                <span class="grid size-9 place-items-center rounded-lg bg-mint font-bold text-ink">Q</span>
                <span class="text-lg font-semibold tracking-[-0.02em]">QueueFlow</span>
            </a>

            <div class="flex items-center gap-4 text-sm sm:justify-end">
                <span class="inline-flex items-center gap-2 font-semibold text-mint">
                    <span class="size-1.5 rounded-full bg-mint" aria-hidden="true"></span>
                    {{ ucfirst(strtolower($board['queueStatus'])) }}
                </span>
                <span class="text-board-muted">Updated {{ strtolower($board['lastUpdated']) }}</span>
            </div>
        </header>

        <div class="mt-8 sm:mt-10">
            <p class="text-xs font-medium tracking-[0.2em] text-board-muted uppercase">{{ $board['branchName'] }} <span class="px-1 text-board-line">&middot;</span> {{ $board['queueName'] }}</p>
            <h1 class="mt-3 font-editorial text-4xl leading-none tracking-[-0.035em] text-board-ink sm:text-5xl lg:text-6xl">{{ $board['businessName'] }}</h1>
        </div>

        <div class="mt-10 grid flex-1 gap-8 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-stretch xl:gap-10">
            <div>
                <section aria-labelledby="serving-heading">
                    <h2 id="serving-heading" class="text-xs font-medium tracking-[0.2em] text-board-muted">NOW SERVING</h2>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2 sm:gap-5">
                        @forelse ($board['serving'] as $entry)
                            <article class="flex min-h-52 flex-col justify-between rounded-2xl border border-board-line bg-board-surface p-6 sm:min-h-60 sm:p-7 lg:min-h-64 xl:p-8">
                                <p class="text-[clamp(4.5rem,10vw,7rem)] leading-none font-semibold tracking-[-0.07em] text-board-ink tabular-nums">{{ $entry['ticketNumber'] }}</p>
                                <div class="mt-8 flex flex-wrap items-center justify-between gap-x-4 gap-y-2 text-sm">
                                    <p class="text-board-muted">{{ $entry['serviceName'] }}</p>
                                    @if ($entry['counter'])
                                        <p class="rounded-full bg-[#194337] px-4 py-2 font-semibold text-mint">{{ $entry['counter'] }}</p>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <p class="col-span-full border-y border-board-line px-5 py-8 text-center text-board-muted">No tickets are being served right now.</p>
                        @endforelse
                    </div>
                </section>

                <section class="mt-7 sm:mt-9" aria-labelledby="calling-heading">
                    <h2 id="calling-heading" class="text-xs font-medium tracking-[0.2em] text-board-muted">CALLING</h2>

                    <div class="mt-4 flex flex-col gap-3">
                        @forelse ($board['calling'] as $entry)
                            <article class="grid gap-3 rounded-2xl bg-amber px-6 py-5 text-amber-ink sm:grid-cols-[8rem_1fr_auto] sm:items-center sm:gap-6 sm:px-7">
                                <p class="text-5xl leading-none font-semibold tracking-[-0.055em] tabular-nums sm:text-6xl">{{ $entry['ticketNumber'] }}</p>
                                <p class="text-base font-medium">{{ $entry['serviceName'] }}</p>
                                @if ($entry['counter'])
                                    <p class="w-fit rounded-full bg-ink px-5 py-3 text-sm font-semibold text-amber">Please proceed to {{ $entry['counter'] }}</p>
                                @endif
                            </article>
                        @empty
                            <p class="border-y border-board-line px-5 py-6 text-center text-board-muted">No tickets are being called.</p>
                        @endforelse
                    </div>
                </section>
            </div>

            <aside class="flex flex-col rounded-2xl border border-board-line bg-board-surface p-6 lg:p-7" aria-labelledby="waiting-heading">
                <h2 id="waiting-heading" class="text-xs font-medium tracking-[0.2em] text-board-muted">UP NEXT</h2>

                <ol class="mt-4 divide-y divide-board-line border-y border-board-line">
                    @forelse ($board['waiting'] as $entry)
                        <li class="flex items-center gap-5 py-6">
                            <span class="w-5 shrink-0 text-xs text-board-muted tabular-nums">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <div class="min-w-0 flex-1 sm:flex sm:items-baseline sm:gap-4 lg:block">
                                <p class="text-3xl font-semibold tracking-[-0.035em] text-board-ink tabular-nums">{{ $entry['ticketNumber'] }}</p>
                                <p class="mt-1 truncate text-xs text-board-muted sm:mt-0 lg:mt-1">{{ $entry['serviceName'] }}</p>
                            </div>
                            @if ($entry['counter'])
                                <span class="shrink-0 text-xs text-board-muted">{{ $entry['counter'] }}</span>
                            @endif
                        </li>
                    @empty
                        <li class="py-7 text-center text-board-muted">No one is waiting.</li>
                    @endforelse
                </ol>

                <div class="mt-auto pt-9 text-sm leading-6 text-board-muted lg:pt-12">
                    <p>Please keep your ticket ready and listen for your number.</p>
                    <a class="mt-3 inline-block font-semibold text-mint underline decoration-mint/50 underline-offset-4 transition hover:decoration-mint" href="{{ route('tickets.show') }}">View my tickets <span aria-hidden="true">&rarr;</span></a>
                </div>
            </aside>
        </div>
    </div>
@endsection
