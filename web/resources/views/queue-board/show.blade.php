@extends('layouts.app')

@php
    $serviceLabel = $resolvedQueue->serviceName ?? 'Shared branch queue';
    $displayName = $resolvedQueue->queueName !== '' ? $resolvedQueue->queueName : $board->name;
    $statusLabel = ucfirst(strtolower($board->status));
    $statusTone = match ($board->status) {
        'OPEN' => 'border-emerald-300/35 bg-emerald-300/15 text-emerald-100',
        'PAUSED' => 'border-amber/45 bg-amber/20 text-amber',
        'CLOSED' => 'border-white/20 bg-white/10 text-white/75',
        default => 'border-white/20 bg-white/10 text-white/75',
    };
@endphp

@section('title', $displayName.' Board - QueueFlow')
@section('body-class', 'bg-[#15123B] text-white')

@section('content')
    <div class="min-h-dvh bg-[#15123B] font-staff-sans text-white">
        <main class="mx-auto flex min-h-dvh w-full max-w-[96rem] flex-col gap-6 px-5 py-5 sm:px-8 sm:py-7 lg:px-10">
            <header class="flex flex-col gap-5 rounded-[2rem] border border-white/10 bg-white/[0.07] px-5 py-5 shadow-[0_24px_80px_-48px_rgba(0,0,0,0.7)] sm:px-7 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="grid size-12 place-items-center rounded-2xl bg-staff-amber font-staff-display text-xl font-extrabold text-[#15123B]" aria-hidden="true">Q</span>
                        <div>
                            <p class="font-staff-display text-xl font-extrabold tracking-[-0.035em]">QueueFlow</p>
                            <p class="text-xs font-semibold tracking-[0.18em] text-white/55">PUBLIC QUEUE BOARD</p>
                        </div>
                    </div>

                    <div class="mt-6">
                        <p class="text-sm font-semibold tracking-[0.16em] text-white/60">{{ $resolvedQueue->branchName }}</p>
                        <h1 class="mt-2 max-w-5xl break-words font-staff-display text-4xl leading-[0.98] font-extrabold tracking-[-0.06em] text-white sm:text-5xl lg:text-7xl">
                            {{ $resolvedQueue->businessName }}
                        </h1>
                        <p class="mt-3 max-w-4xl break-words text-base text-white/70 sm:text-xl">
                            {{ $serviceLabel }} <span class="text-white/35" aria-hidden="true">/</span> {{ $displayName }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center lg:flex-col lg:items-end">
                    <span class="inline-flex min-h-12 items-center justify-center rounded-full border px-5 text-sm font-bold {{ $statusTone }}" aria-label="Queue status {{ $statusLabel }}">
                        {{ $statusLabel }}
                    </span>
                    <a class="inline-flex min-h-12 items-center justify-center rounded-full border border-white/15 bg-white px-5 text-sm font-bold text-[#15123B] transition hover:bg-[#F3F4FB] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber" href="{{ route('queues.board.show', $resolvedQueue->publicCode) }}" aria-label="Refresh public queue board">
                        Refresh board
                    </a>
                    <p class="text-xs text-white/50">Rendered {{ $refreshedAt->format('g:i A') }}</p>
                </div>
            </header>

            <section class="grid flex-1 gap-5 lg:grid-cols-[minmax(0,1.08fr)_minmax(22rem,0.62fr)]" aria-label="Current queue state">
                <div class="grid gap-5">
                    <article class="rounded-[2rem] border border-white/10 bg-white text-[#15123B] p-6 shadow-[0_28px_90px_-52px_rgba(0,0,0,0.8)] sm:p-8 lg:p-10" aria-labelledby="now-serving-heading">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-extrabold tracking-[0.22em] text-[#4338F0]">NOW SERVING</p>
                                <h2 id="now-serving-heading" class="sr-only">Now serving</h2>
                            </div>
                            <p class="rounded-full bg-[#F3F4FB] px-4 py-2 text-sm font-bold text-[#5b5f7a]">{{ $board->waitingCount }} waiting</p>
                        </div>

                        @if ($board->nowServing)
                            <p class="mt-8 break-words font-staff-display text-[clamp(5rem,17vw,14rem)] leading-none font-extrabold tracking-[-0.09em] tabular-nums text-[#15123B]">
                                {{ $board->nowServing }}
                            </p>
                        @else
                            <div class="mt-8 grid min-h-56 place-items-center rounded-[1.5rem] border border-dashed border-[#d8d9ee] bg-[#F3F4FB] px-6 text-center">
                                <p class="max-w-sm text-xl font-bold text-[#5b5f7a]">No one is being served right now.</p>
                            </div>
                        @endif
                    </article>

                    <article class="rounded-[2rem] border border-[#FFC83D]/40 bg-[#FFC83D] p-6 text-[#15123B] shadow-[0_24px_70px_-48px_rgba(255,200,61,0.95)] sm:p-8" aria-labelledby="calling-heading">
                        <p class="text-sm font-extrabold tracking-[0.22em] text-[#15123B]/70">CALLING</p>
                        <h2 id="calling-heading" class="sr-only">Calling</h2>

                        @if ($board->calling)
                            <p class="mt-4 break-words font-staff-display text-[clamp(3.75rem,10vw,8rem)] leading-none font-extrabold tracking-[-0.08em] tabular-nums">
                                {{ $board->calling }}
                            </p>
                        @else
                            <p class="mt-5 rounded-2xl bg-white/60 px-5 py-8 text-lg font-bold text-[#15123B]/70">No ticket is being called.</p>
                        @endif
                    </article>
                </div>

                <aside class="rounded-[2rem] border border-white/10 bg-white/10 p-5 shadow-[0_24px_80px_-50px_rgba(0,0,0,0.75)] sm:p-6 lg:p-7" aria-labelledby="up-next-heading">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 pb-5">
                        <div>
                            <p class="text-sm font-extrabold tracking-[0.22em] text-[#FFC83D]">UP NEXT</p>
                            <h2 id="up-next-heading" class="sr-only">Up next</h2>
                        </div>
                        <p class="rounded-full bg-white/10 px-3 py-1.5 text-sm font-bold text-white/75">{{ $board->waitingCount }} waiting</p>
                    </div>

                    <ol class="mt-3 divide-y divide-white/10">
                        @forelse ($board->upcomingTicketNumbers as $ticketNumber)
                            <li class="grid min-h-24 grid-cols-[3rem_minmax(0,1fr)] items-center gap-4 py-4 sm:min-h-28">
                                <span class="font-staff-display text-xl font-extrabold text-white/35">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="break-words font-staff-display text-[clamp(2.5rem,7vw,5rem)] leading-none font-extrabold tracking-[-0.07em] tabular-nums text-white">{{ $ticketNumber }}</span>
                            </li>
                        @empty
                            <li class="grid min-h-56 place-items-center px-4 py-10 text-center">
                                <p class="max-w-xs text-xl font-bold text-white/65">No upcoming tickets.</p>
                            </li>
                        @endforelse
                    </ol>

                    <div class="mt-6 rounded-[1.5rem] border border-white/10 bg-[#4338F0] px-5 py-5">
                        <p class="font-staff-display text-5xl font-extrabold tracking-[-0.06em] text-white tabular-nums">{{ $board->waitingCount }}</p>
                        <p class="mt-1 text-sm font-bold text-white/70">total waiting</p>
                    </div>
                </aside>
            </section>
        </main>
    </div>
@endsection
