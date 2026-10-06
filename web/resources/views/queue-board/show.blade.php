@extends('layouts.app')

@php
    $serviceLabel = $resolvedQueue->serviceName ?? 'Shared branch queue';
    $displayName = $resolvedQueue->queueName !== '' ? $resolvedQueue->queueName : $board->name;
    $queueContext = $serviceLabel === $displayName ? $serviceLabel : $serviceLabel.' · '.$displayName;
    $statusLabel = strtoupper($board->status);
    $statusTone = match ($board->status) {
        'OPEN' => 'border-emerald-300/30 bg-emerald-300/10 text-emerald-100',
        'PAUSED' => 'border-amber/35 bg-amber/12 text-amber',
        'CLOSED' => 'border-rose-200/25 bg-rose-200/10 text-rose-100',
        default => 'border-white/20 bg-white/10 text-white/70',
    };
    $statusDot = match ($board->status) {
        'OPEN' => 'bg-emerald-300 shadow-[0_0_18px_rgba(110,231,183,0.45)]',
        'PAUSED' => 'bg-amber',
        'CLOSED' => 'bg-rose-200',
        default => 'bg-white/60',
    };
    $statusMessage = match ($board->status) {
        'PAUSED' => 'Queue temporarily paused',
        'CLOSED' => 'Queue closed',
        default => null,
    };
@endphp

@section('title', $displayName.' Board - QueueFlow')
@section('body-class', 'overflow-hidden bg-[#0D0D31] text-white')

@section('content')
    <div class="h-dvh overflow-hidden bg-[#0D0D31] font-staff-sans text-white">
        <main class="mx-auto grid h-dvh w-full max-w-[120rem] grid-rows-[auto_minmax(0,1fr)_auto_auto] gap-5 px-6 py-6 lg:px-8 xl:px-10">
            <header class="grid shrink-0 grid-cols-[minmax(13rem,0.8fr)_minmax(0,1.45fr)_minmax(16rem,0.9fr)] items-center gap-5 rounded-[1.5rem] border border-white/10 bg-[#171646] px-5 py-4 shadow-[0_20px_70px_-60px_rgba(0,0,0,0.9)]">
                <div class="flex min-w-0 items-center gap-3.5">
                    <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-[#FFC83D] font-staff-display text-lg font-extrabold text-[#0D0D31]" aria-hidden="true">Q</span>
                    <div class="min-w-0">
                        <p class="truncate font-staff-display text-xl font-extrabold tracking-[-0.04em]">QueueFlow</p>
                        <p class="truncate text-[0.68rem] font-extrabold tracking-[0.22em] text-white/48">PUBLIC QUEUE BOARD</p>
                    </div>
                </div>

                <div class="min-w-0 border-x border-white/10 px-5 text-center">
                    <h1 class="truncate font-staff-display text-[clamp(1.55rem,2.15vw,2.5rem)] leading-none font-extrabold tracking-[-0.045em] text-white">
                        {{ $resolvedQueue->businessName }}
                    </h1>
                    <p class="mt-1 truncate text-[clamp(0.95rem,1.15vw,1.25rem)] font-semibold text-white/62">
                        {{ $resolvedQueue->branchName }} <span class="text-white/30" aria-hidden="true">·</span> {{ $queueContext }}
                    </p>
                    @if ($statusMessage)
                        <p @class([
                            'mt-1 truncate font-staff-display text-[clamp(1rem,1.15vw,1.3rem)] font-extrabold tracking-[-0.02em]',
                            'text-amber' => $board->status === 'PAUSED',
                            'text-rose-100' => $board->status === 'CLOSED',
                        ])>{{ $statusMessage }}</p>
                    @endif
                </div>

                <div class="flex min-w-0 items-center justify-end gap-3.5">
                    <span class="inline-flex min-h-10 shrink-0 items-center gap-2 rounded-full border px-4 text-sm font-extrabold {{ $statusTone }}" aria-label="Queue status {{ $statusLabel }}">
                        <span class="size-2 rounded-full {{ $statusDot }}" aria-hidden="true"></span>
                        {{ $statusLabel }}
                    </span>
                    <div class="min-w-0 text-right">
                        <p class="truncate text-sm font-semibold text-white/50">Rendered {{ $refreshedAt->format('g:i A') }}</p>
                        <a class="mt-1.5 inline-flex min-h-9 items-center justify-center rounded-full border border-[#8D8AFF]/50 bg-[#201F58] px-3.5 text-sm font-bold text-white/82 transition hover:border-[#A9A6FF]/70 hover:bg-[#28266A] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#FFC83D]" href="{{ route('queues.board.show', $resolvedQueue->publicCode) }}" aria-label="Refresh public queue board">
                            <span aria-hidden="true">↻</span>
                            <span class="ml-1.5">Refresh board</span>
                        </a>
                    </div>
                </div>
            </header>

            <section class="grid min-h-0 grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)] gap-5" aria-label="Current tickets">
                <article class="relative grid min-h-0 grid-rows-[auto_minmax(0,1fr)] overflow-hidden rounded-[1.875rem] border border-[#F7F2E7]/90 bg-[#F8F3EA] p-6 text-[#0D0D31] shadow-[0_34px_110px_-78px_rgba(0,0,0,0.95)] lg:p-8" aria-labelledby="now-serving-heading">
                    <div class="pointer-events-none absolute -right-20 -top-24 size-64 rounded-full border-[2.25rem] border-[#4338F0]/[0.055]" aria-hidden="true"></div>
                    <div class="relative">
                        <p class="text-[clamp(0.78rem,0.9vw,0.98rem)] font-extrabold tracking-[0.25em] text-[#4338F0]">NOW SERVING</p>
                        <h2 id="now-serving-heading" class="sr-only">Now serving</h2>
                    </div>

                    <p class="relative grid min-h-0 place-items-center break-words text-center font-staff-display text-[clamp(6rem,10vw,11rem)] leading-none font-extrabold tracking-[-0.08em] tabular-nums">
                        {{ $board->nowServing ?? '—' }}
                    </p>
                </article>

                <article class="relative grid min-h-0 grid-rows-[auto_minmax(0,1fr)] overflow-hidden rounded-[1.875rem] border border-[#F7C948]/70 bg-[#F4B740] p-6 text-[#0D0D31] shadow-[0_34px_90px_-78px_rgba(244,183,64,0.95)] lg:p-8" aria-labelledby="calling-heading">
                    <div class="pointer-events-none absolute -right-16 -top-20 size-52 rounded-full border-[1.85rem] border-[#0D0D31]/[0.055]" aria-hidden="true"></div>
                    <div class="relative">
                        <p class="text-[clamp(0.78rem,0.9vw,0.98rem)] font-extrabold tracking-[0.25em] text-[#0D0D31]/65">CALLING</p>
                        <h2 id="calling-heading" class="sr-only">Calling</h2>
                    </div>

                    <p class="relative grid min-h-0 place-items-center break-words text-center font-staff-display text-[clamp(5rem,7.5vw,8.75rem)] leading-none font-extrabold tracking-[-0.08em] tabular-nums">
                        {{ $board->calling ?? '—' }}
                    </p>
                </article>
            </section>

            <section class="shrink-0 rounded-[1.625rem] border border-[#DAD7FF]/70 bg-[#F3F1FF] p-4 text-[#0D0D31] shadow-[0_24px_80px_-72px_rgba(0,0,0,0.9)]" aria-label="Upcoming tickets">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-extrabold tracking-[0.24em] text-[#4338F0]">UP NEXT</p>
                        <h2 class="sr-only">Up next</h2>
                    </div>
                    <p class="text-sm font-bold text-[#59577A]">Upcoming tickets</p>
                </div>

                <ol class="mt-3 grid grid-cols-5 gap-3">
                    @forelse ($board->upcomingTicketNumbers as $ticketNumber)
                        <li class="flex min-h-[5.25rem] items-center justify-center rounded-[1.125rem] border border-[#D7D4FA] bg-[#FFFCF6] px-3 py-2 text-center">
                            <span class="break-words font-staff-display text-[clamp(1.9rem,2.75vw,3.25rem)] leading-none font-extrabold tracking-[-0.07em] tabular-nums">{{ $ticketNumber }}</span>
                        </li>
                    @empty
                        <li class="col-span-5 flex min-h-14 items-center justify-center rounded-[1rem] px-4 py-2 text-center text-[clamp(1.05rem,1.35vw,1.4rem)] font-bold text-[#59577A]">
                            No upcoming tickets
                        </li>
                    @endforelse
                </ol>
            </section>

            <footer class="grid shrink-0 grid-cols-[minmax(0,1fr)_auto] items-center gap-4 rounded-[1.25rem] border border-white/10 bg-[#171646] px-5 py-3">
                <div class="min-w-0">
                    <p class="truncate font-staff-display text-lg font-extrabold tracking-[-0.035em] text-white">{{ $resolvedQueue->businessName }}</p>
                    <p class="truncate text-sm font-semibold text-white/48">{{ $resolvedQueue->branchName }}</p>
                </div>

                <div class="flex items-baseline gap-3 border-l border-white/10 pl-5 text-right">
                    <span class="font-staff-display text-[clamp(1.9rem,2.8vw,3rem)] leading-none font-extrabold tabular-nums">{{ $board->waitingCount }}</span>
                    <span class="text-xs font-extrabold tracking-[0.18em] text-white/55">{{ $board->waitingCount === 1 ? 'PERSON WAITING' : 'PEOPLE WAITING' }}</span>
                </div>
            </footer>
        </main>
    </div>
@endsection
