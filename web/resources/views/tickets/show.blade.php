@extends('layouts.app')

@section('title', 'Ticket '.$ticket['ticketNumber'].' - QueueFlow')
@section('body-class', 'bg-cream text-ink')

@section('content')
    <div class="mx-auto flex min-h-screen max-w-2xl flex-col px-5 pt-5 pb-[calc(7rem+env(safe-area-inset-bottom))] sm:px-6 sm:pt-8 md:pb-8 lg:pt-10">
        <x-customer-navigation :updated="$ticket['lastUpdated']" />

        <header>
            <h1 class="mt-10 text-xs font-semibold tracking-[0.2em] text-brand">MY TICKET</h1>
            <p class="mt-4 font-editorial text-3xl leading-none tracking-[-0.03em] text-ink">{{ $ticket['businessName'] }}</p>
            <p class="mt-2 text-sm text-muted">{{ $ticket['branchName'] }} <span class="px-1 text-line">&middot;</span> {{ $ticket['queueName'] }}</p>
        </header>

        <article class="mt-6 rounded-2xl bg-brand p-5 text-white sm:p-6">
            <div class="flex items-center justify-between gap-4">
                <p class="text-xs font-medium text-white/70">Current status</p>
                <p class="rounded-full bg-amber px-4 py-2 text-xs font-bold tracking-[0.08em] text-amber-ink">{{ $ticket['status'] }}</p>
            </div>

            <p class="mt-4 text-6xl leading-none font-semibold tracking-[-0.065em] tabular-nums sm:text-7xl">{{ $ticket['ticketNumber'] }}</p>
            <p class="mt-3 text-base font-semibold">{{ $ticket['serviceName'] }}</p>

            @if ($ticket['counter'])
                <div class="mt-5 flex items-end justify-between gap-4 border-t border-dashed border-white/25 pt-4">
                    <p class="text-sm text-white/75">Please proceed to</p>
                    <p class="text-2xl font-semibold tracking-[-0.025em] text-amber">{{ $ticket['counter'] }}</p>
                </div>
            @endif
        </article>

        <dl class="grid grid-cols-2 border-b border-line py-5">
            <div class="pr-5">
                <dt class="text-xs text-muted">People ahead</dt>
                <dd class="mt-1 text-3xl font-semibold tracking-[-0.035em] tabular-nums">{{ $ticket['peopleAhead'] }}</dd>
            </div>
            <div class="border-l border-line pl-5">
                <dt class="text-xs text-muted">Estimated wait</dt>
                <dd class="mt-2 text-lg font-semibold tracking-[-0.025em]">{{ $ticket['estimatedWait'] }}</dd>
            </div>
        </dl>

        <section class="border-b border-line py-6" aria-labelledby="qr-heading">
            <div class="flex items-baseline justify-between gap-4">
                <h2 id="qr-heading" class="text-lg font-semibold tracking-[-0.02em]">Ticket QR</h2>
                @if ($ticket['qrPresentation']['isMock'])
                    <p class="text-[0.65rem] text-muted">Mock preview &middot; not scannable</p>
                @endif
            </div>

            <div class="mt-4 grid grid-cols-[6rem_minmax(0,1fr)] items-center gap-5 sm:grid-cols-[6.5rem_minmax(0,1fr)]">
                <div class="aspect-square overflow-hidden border-8 border-white bg-white text-ink" role="img" aria-label="Mock QR visual, not scannable">
                    <svg class="size-full" viewBox="0 0 17 17" aria-hidden="true" shape-rendering="crispEdges">
                        <rect width="17" height="17" fill="white" />
                        @foreach ($ticket['qrPresentation']['pattern'] as $rowIndex => $row)
                            @foreach (str_split($row) as $columnIndex => $cell)
                                @if ($cell === '1')
                                    <rect x="{{ $columnIndex }}" y="{{ $rowIndex }}" width="1" height="1" fill="currentColor" />
                                @endif
                            @endforeach
                        @endforeach
                    </svg>
                </div>

                <div class="min-w-0">
                    <h3 class="text-base leading-5 font-semibold">{{ $ticket['qrPresentation']['heading'] }}</h3>
                    <p class="mt-1 text-sm leading-5 text-muted">{{ $ticket['qrPresentation']['instructions'] }}</p>
                    <p class="mt-2 text-xs font-semibold text-brand">{{ $ticket['qrPresentation']['ticketLabel'] }}</p>
                </div>
            </div>
        </section>

        <section class="border-b border-line py-6" aria-label="Ticket progress">
            <div class="grid grid-cols-4 gap-2" aria-hidden="true">
                <span class="h-1.5 rounded-full bg-brand"></span>
                <span class="h-1.5 rounded-full bg-brand"></span>
                <span class="h-1.5 rounded-full bg-amber"></span>
                <span class="h-1.5 rounded-full bg-line"></span>
            </div>
            <ol class="mt-2 grid grid-cols-4 gap-2 text-[0.68rem] text-muted">
                <li>Joined</li>
                <li class="text-center">Almost</li>
                <li class="text-center font-semibold text-ink">Called</li>
                <li class="text-right">Served</li>
            </ol>
        </section>

        <section class="pt-6">
            <h2 class="font-editorial text-2xl font-semibold tracking-[-0.025em] text-ink">Your turn is coming up.</h2>
            <p class="mt-2 text-sm leading-6 text-muted">{{ $ticket['statusMessage'] }} Stay nearby.</p>
        </section>

        <footer class="mt-auto pt-8 pb-3">
            <a class="text-sm font-medium text-muted underline decoration-line underline-offset-4 transition hover:text-brand hover:decoration-brand" href="{{ route('queue-board.show') }}"><span aria-hidden="true">&larr;</span> Back to queue board</a>
        </footer>
    </div>
@endsection
