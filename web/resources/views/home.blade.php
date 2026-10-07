@extends('layouts.app')

@section('title', 'Home - QueueFlow')
@section('body-class', 'bg-[#f7f6f2] text-customer-navy')

@section('content')
    <div class="customer-page">
        <x-customer-navigation />

        <header class="customer-hero {{ $activeTicket ? 'pb-24' : 'pb-20' }}">
            <div class="relative z-10 flex items-start justify-between gap-4">
                <div>
                    <p class="text-xl font-bold tracking-[-0.03em]">Hi, Guest!</p>
                    @if ($activeTicket)
                        <p class="mt-0.5 text-sm text-white/80">Your ticket is active</p>
                    @else
                        <h1 class="mt-6 max-w-[17rem] text-[2rem] leading-[1.02] font-bold tracking-[-0.045em]">No ticket yet.<br>Scan to join a queue.</h1>
                    @endif
                </div>
                <button class="grid size-11 shrink-0 cursor-not-allowed place-items-center rounded-full bg-white/15 text-white/75" type="button" aria-label="Notifications are coming later" aria-disabled="true">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 7h18s-3 0-3-7" /><path d="M10 20h4" /></svg>
                </button>
            </div>
        </header>

        @if ($activeTicket && $position)
            <section class="relative z-10 -mt-16 px-5" aria-label="Active ticket">
                <a class="customer-ticket-card block" href="{{ route('queue-entries.show', [$activeTicket->queueId, $activeTicket->entryId]) }}">
                    <div class="relative z-10">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-customer-yellow text-sm font-bold text-customer-navy">QF</span>
                                <div class="min-w-0">
                                    <p class="truncate font-bold">{{ $activeBusiness?->name ?? 'QueueFlow queue' }}</p>
                                    <p class="truncate text-xs text-white/60">Your saved ticket</p>
                                </div>
                            </div>
                            <span class="customer-status-badge bg-customer-green/20 text-[#54e5ad]">{{ $statusLabel }}</span>
                        </div>

                        <p class="mt-5 text-[0.65rem] font-bold tracking-[0.13em] text-white/55">QUEUE NUMBER</p>
                        <p class="mt-1 break-words text-[3.75rem] leading-none font-bold tracking-[-0.065em]">{{ $activeTicket->ticketNumber }}</p>

                        <div class="mt-5 h-1.5 overflow-hidden rounded-full bg-white/15" aria-hidden="true"><span class="block h-full w-2/3 rounded-full bg-customer-yellow"></span></div>
                        <div class="mt-2 flex justify-between gap-3 text-xs"><span class="text-white/65">Now serving {{ $presentation['activeTicket']['nowServing'] }}</span><span class="font-bold text-customer-yellow">You {{ $activeTicket->ticketNumber }}</span></div>

                        <div class="relative mt-5 grid grid-cols-[7rem_1fr] gap-5 border-t border-dashed border-white/25 pt-5">
                            <x-demo-qr :pattern="$presentation['ticket']['qrPattern']" dark />
                            <dl class="grid content-center gap-4">
                                <div><dt class="text-xs text-white/55">People ahead</dt><dd class="mt-1 text-2xl font-bold tabular-nums">{{ $position->peopleAhead }}</dd></div>
                                <div><dt class="text-xs text-white/55">Estimated wait</dt><dd class="mt-1 text-2xl font-bold tabular-nums">{{ $position->estimatedWaitMinutes }} min</dd></div>
                            </dl>
                        </div>
                    </div>
                </a>
            </section>

            <section class="px-5 pt-6" aria-labelledby="live-queue-heading">
                <div class="flex items-center justify-between gap-4"><div class="flex items-center gap-2"><span class="size-2 rounded-full bg-customer-green"></span><h2 id="live-queue-heading" class="text-xs font-bold tracking-[0.08em]">LIVE QUEUE</h2></div></div>
                <div class="mt-3 grid grid-cols-3 gap-2">
                    @foreach (['nowServing' => 'Now serving', 'calling' => 'Calling', 'upNext' => 'Up next'] as $key => $label)
                        <div @class(['rounded-2xl px-3 py-3', 'bg-customer-indigo text-white' => $key === 'nowServing', 'bg-customer-yellow' => $key === 'calling', 'bg-white' => $key === 'upNext'])><p class="text-[0.65rem]">{{ $label }}</p><p class="mt-2 text-xl font-bold">{{ $presentation['activeTicket'][$key] }}</p></div>
                    @endforeach
                </div>
                <div class="mt-4 flex items-center gap-3 rounded-[1.25rem] bg-white p-4 ring-1 ring-customer-line/40">
                    <span class="grid size-10 shrink-0 place-items-center rounded-full bg-customer-indigo/10 text-customer-indigo"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 7h18s-3 0-3-7" /></svg></span>
                    <div class="min-w-0 grow"><p class="text-sm font-bold">Alert me when I am 2 away</p><p class="text-xs text-customer-muted">Presentation preview for a future notification phase.</p></div>
                    <span class="flex h-7 w-12 shrink-0 items-center justify-end rounded-full bg-customer-indigo p-1" role="switch" aria-checked="true" aria-disabled="true"><span class="size-5 rounded-full bg-white"></span></span>
                </div>
            </section>
        @else
            <section class="relative z-10 -mt-12 px-5" aria-label="Join a queue">
                <div class="rounded-[1.75rem] bg-white px-5 py-6 text-center shadow-[0_18px_45px_-28px_rgba(21,17,63,0.55)]">
                    <div class="mx-auto grid size-28 place-items-center text-customer-indigo" aria-hidden="true">
                        <svg class="size-28" viewBox="0 0 112 112" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round"><path d="M28 14H14v14M84 14h14v14M28 98H14V84M84 98h14V84" /><path d="M44 44h8v8h-8zM60 44h8v8h-8zM44 60h8v8h-8zM60 60h8v8h-8z" /></svg>
                    </div>
                    <p class="mt-2 font-bold">Scan the QR code at the counter</p>
                    <a class="customer-primary-button mt-4 gap-2" href="{{ route('scanner.show') }}"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M8 3H3v5M16 3h5v5M8 21H3v-5M16 21h5v-5" /></svg>Open scanner</a>
                </div>
            </section>

            <section class="px-5 pt-6" aria-labelledby="live-queue-heading">
                <div class="flex items-center justify-between gap-4"><div class="flex items-center gap-2"><span class="size-2 rounded-full bg-customer-green"></span><h2 id="live-queue-heading" class="text-xs font-bold tracking-[0.08em]">LIVE QUEUE</h2></div></div>
                <div class="mt-3 divide-y divide-customer-line/70 overflow-hidden rounded-[1.5rem] bg-white px-4">
                    @foreach ($presentation['liveQueues'] as $queue)
                        @php($business = $businesses[$loop->index] ?? null)
                        <div class="relative flex min-h-17 items-center gap-3 py-3">
                            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-customer-indigo/10 text-customer-indigo"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 10.5c0 5.25-8 10.5-8 10.5S4 15.75 4 10.5a8 8 0 1 1 16 0Z" /><circle cx="12" cy="10.5" r="2.25" /></svg></span>
                            <div class="min-w-0 grow"><p class="truncate text-sm font-bold">{{ $business?->name ?? $queue['name'] }}</p><p class="truncate text-xs text-customer-muted">Now serving {{ $queue['nowServing'] }}</p></div>
                            <div class="text-right"><p class="text-2xl leading-none font-bold tabular-nums">{{ str_pad((string) $queue['waiting'], 2, '0', STR_PAD_LEFT) }}</p><p class="text-[0.65rem] text-customer-muted">waiting</p></div>
                            @if ($business)<a class="absolute inset-0" href="{{ route('businesses.show', $business->id) }}"><span class="sr-only">View {{ $business->name }}</span></a>@endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
