@extends('layouts.app')

@section('title', 'Your Queue - QueueFlow')
@section('body-class', 'bg-cream text-ink')

@section('content')
    <div class="mx-auto min-h-screen max-w-6xl px-5 pt-5 pb-[calc(7rem+env(safe-area-inset-bottom))] sm:px-6 sm:pt-8 md:pb-8 lg:px-8 xl:px-10">
        <x-customer-navigation :updated="$home['lastUpdated']" />

        <div class="mt-10 lg:grid lg:grid-cols-[minmax(0,0.92fr)_minmax(0,1.08fr)] lg:grid-rows-[auto_auto_1fr] lg:items-start lg:gap-x-14 xl:gap-x-20">
            <section class="lg:col-start-1 lg:row-start-1" aria-labelledby="queue-context-heading">
                <h1 id="queue-context-heading" class="text-xs font-semibold tracking-[0.2em] text-brand">YOUR QUEUE</h1>
                <p class="mt-4 font-editorial text-3xl leading-none tracking-[-0.03em]">{{ $home['businessName'] }}</p>
                <p class="mt-2 text-sm text-muted">{{ $home['branchName'] }} <span class="px-1 text-line">&middot;</span> {{ $home['queueName'] }}</p>
            </section>

            <section class="mt-8 flex min-w-0 flex-col rounded-2xl border border-brand/10 bg-[#fbfdf9]/90 p-5 shadow-[0_18px_45px_-32px_rgba(13,107,89,0.32)] backdrop-blur-sm supports-[backdrop-filter]:bg-[#fbfdf9]/75 sm:p-6 lg:col-start-1 lg:row-start-2" aria-labelledby="active-ticket-heading">
                <h2 id="active-ticket-heading" class="order-2 mt-5 text-6xl leading-none font-semibold tracking-[-0.065em] text-brand tabular-nums">{{ $home['activeTicket']['ticketNumber'] }}</h2>

                <div class="order-1 flex items-center justify-between gap-5">
                    <p class="text-xs font-medium text-muted">Current status</p>
                    <p class="rounded-full bg-amber px-4 py-2 text-xs font-bold tracking-[0.08em] text-amber-ink">{{ $home['activeTicket']['status'] }}</p>
                </div>

                <p class="order-3 mt-3 text-sm font-medium">{{ $home['activeTicket']['serviceName'] }}</p>

                @if ($home['activeTicket']['counter'])
                    <div class="order-4 mt-6 flex items-end justify-between gap-5 border-l-3 border-amber pl-4">
                        <p class="text-sm text-muted">Please proceed to</p>
                        <p class="text-2xl font-semibold tracking-[-0.025em]">{{ $home['activeTicket']['counter'] }}</p>
                    </div>
                @endif

                <dl class="order-5 mt-6 grid grid-cols-2 gap-6 border-t border-brand/10 pt-5">
                    <div>
                        <dt class="text-xs text-muted">People ahead</dt>
                        <dd class="mt-1 text-3xl font-semibold tracking-[-0.035em] tabular-nums">{{ $home['activeTicket']['peopleAhead'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted">Estimated wait</dt>
                        <dd class="mt-2 text-lg font-semibold">{{ $home['activeTicket']['estimatedWait'] }}</dd>
                    </div>
                </dl>
            </section>

            <section class="mt-10 min-w-0 lg:col-start-2 lg:row-span-3 lg:row-start-1 lg:mt-0" aria-labelledby="live-queue-heading">
                <div class="flex items-baseline justify-between gap-4">
                    <h2 id="live-queue-heading" class="text-lg font-semibold tracking-[-0.02em]">Live queue</h2>
                    <a class="text-xs font-semibold text-brand underline decoration-brand/30 underline-offset-4 transition hover:decoration-brand" href="{{ route('queue-board.show') }}">View full queue board <span aria-hidden="true">&rarr;</span></a>
                </div>

                <section class="pt-6" aria-labelledby="home-serving-heading">
                    <h3 id="home-serving-heading" class="text-xs font-semibold tracking-[0.16em] text-muted">NOW SERVING</h3>
                    <div class="mt-3 grid grid-cols-2 gap-8 bg-white/55 px-4 py-5">
                        @forelse ($home['serving'] as $entry)
                            <div>
                                <p class="text-3xl font-semibold tracking-[-0.04em] text-brand tabular-nums">{{ $entry['ticketNumber'] }}</p>
                                <p class="mt-1 text-xs leading-5 text-muted">{{ $entry['serviceName'] }}</p>
                            </div>
                        @empty
                            <p class="col-span-2 py-5 text-sm text-muted">No tickets are being served.</p>
                        @endforelse
                    </div>
                </section>

                <section class="pt-6" aria-labelledby="home-calling-heading">
                    <h3 id="home-calling-heading" class="text-xs font-semibold tracking-[0.16em] text-muted">CALLING</h3>
                    <div class="mt-3">
                        @forelse ($home['calling'] as $entry)
                            <div class="flex items-center justify-between gap-4 border-l-3 border-amber bg-amber/10 px-4 py-3">
                                <p class="text-2xl font-semibold tracking-[-0.035em] tabular-nums">{{ $entry['ticketNumber'] }}</p>
                                @if ($entry['counter'])
                                    <p class="text-sm font-medium text-muted">{{ $entry['counter'] }}</p>
                                @endif
                            </div>
                        @empty
                            <p class="py-4 text-sm text-muted">No tickets are being called.</p>
                        @endforelse
                    </div>
                </section>

                <section class="pt-6" aria-labelledby="home-waiting-heading">
                    <h3 id="home-waiting-heading" class="text-xs font-semibold tracking-[0.16em] text-muted">UP NEXT</h3>
                    <ol class="mt-3 divide-y divide-line">
                        @forelse ($home['waiting'] as $entry)
                            <li class="flex items-center gap-4 py-3.5">
                                <span class="w-5 text-xs text-muted tabular-nums">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="text-lg font-semibold tracking-[-0.025em] tabular-nums">{{ $entry['ticketNumber'] }}</span>
                                <span class="min-w-0 flex-1 truncate text-right text-xs text-muted">{{ $entry['serviceName'] }}</span>
                            </li>
                        @empty
                            <li class="py-4 text-sm text-muted">No one is waiting.</li>
                        @endforelse
                    </ol>
                </section>
            </section>

            <a class="mt-8 flex w-full items-center justify-center rounded-xl bg-brand px-5 py-3.5 text-sm font-semibold text-white transition hover:bg-ink lg:col-start-1 lg:row-start-3 lg:self-start" href="{{ route('tickets.show') }}">View my ticket</a>
        </div>
    </div>
@endsection
