@extends('layouts.app')

@section('title', $service->name.' - QueueFlow')
@section('body-class', 'bg-[#f7f6f2] text-customer-navy')

@section('content')
    <div class="customer-page">
        <x-customer-navigation />

        <header class="customer-hero pb-16">
            <div class="relative z-10 flex items-center gap-3">
                <a class="customer-back-link" href="{{ route('branches.show', [$business->id, $branch->id]) }}" aria-label="Back to {{ $branch->name }}">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m14.5 6-6 6 6 6" /></svg>
                </a>
                <h1 class="text-xl font-bold tracking-[-0.025em]">Join queue</h1>
            </div>
        </header>

        <div class="relative z-10 -mt-7 px-5 pb-7">
            <section class="rounded-[1.5rem] bg-white p-5 shadow-[0_18px_45px_-28px_rgba(21,17,63,0.55)] ring-1 ring-customer-line/40" aria-label="Selected place">
                <div class="flex items-center gap-3">
                    <span class="customer-resource-icon">{{ mb_strtoupper(mb_substr($business->name, 0, 2)) }}</span>
                    <div class="min-w-0 grow">
                        <p class="truncate font-bold">{{ $business->name }}</p>
                        <p class="mt-1 truncate text-xs text-customer-muted">{{ $branch->name }} &middot; {{ $branch->address }}</p>
                    </div>
                    @if ($queue?->status === 'OPEN')
                        <span class="shrink-0 rounded-full bg-customer-green/10 px-3 py-1.5 text-[0.68rem] font-bold text-customer-green">Open</span>
                    @endif
                </div>
            </section>

            <section class="mt-7" aria-labelledby="availability-heading">
                <p class="text-xs font-bold tracking-[0.08em]">SELECTED SERVICE</p>

                <div class="mt-3 rounded-[1.15rem] bg-white p-4 shadow-[0_10px_30px_-24px_rgba(21,17,63,0.55)] ring-2 ring-customer-indigo">
                    <div class="flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <h2 id="availability-heading" class="font-bold">{{ $service->name }}</h2>
                            <p class="mt-1 text-xs text-customer-muted">{{ $service->description ?: $service->durationMinutes.' minute service' }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-3">
                            <span class="text-xs font-bold text-customer-indigo">{{ $service->durationMinutes }} min</span>
                            <span class="grid size-7 place-items-center rounded-full bg-customer-indigo text-white" aria-hidden="true">&#10003;</span>
                        </div>
                    </div>
                </div>

                @if (! $service->active)
                    <div class="customer-empty-state">
                        <p class="text-xs font-bold tracking-[0.1em] text-customer-muted">UNAVAILABLE</p>
                        <p class="mt-3 text-xl font-bold">Service unavailable</p>
                        <p class="mt-2 text-sm leading-5 text-customer-muted">This service is not currently accepting customers.</p>
                        <a class="customer-text-link mt-4" href="{{ route('branches.show', [$business->id, $branch->id]) }}">Choose another service <span aria-hidden="true">&rsaquo;</span></a>
                    </div>
                @elseif ($queue === null)
                    <div class="customer-empty-state">
                        <p class="text-xs font-bold tracking-[0.1em] text-customer-muted">TODAY&rsquo;S QUEUE</p>
                        <p class="mt-3 text-xl font-bold">No queue available today</p>
                        <p class="mt-2 text-sm leading-5 text-customer-muted">Please check again later or choose another service.</p>
                        <a class="customer-text-link mt-4" href="{{ route('branches.show', [$business->id, $branch->id]) }}">Choose another service <span aria-hidden="true">&rsaquo;</span></a>
                    </div>
                @elseif ($queue->status === 'OPEN')
                    <div class="mt-3 grid gap-2" aria-label="Presentation service alternatives">
                        @foreach ($presentation['alternatives'] as $alternative)
                            <div class="flex items-center justify-between gap-3 rounded-[1.15rem] bg-white p-4 shadow-[0_10px_30px_-24px_rgba(21,17,63,0.55)] ring-1 ring-customer-line/40 opacity-75">
                                <div><p class="text-sm font-bold">{{ $alternative['name'] }}</p><p class="mt-1 text-xs text-customer-muted">{{ $alternative['waiting'] }} waiting</p></div>
                                <div class="flex items-center gap-3"><span class="text-xs font-bold">{{ $alternative['estimatedWaitMinutes'] === 0 ? 'No wait' : $alternative['estimatedWaitMinutes'].' min' }}</span><span class="size-7 rounded-full border-2 border-customer-line"></span></div>
                            </div>
                        @endforeach
                        <p class="text-center text-[0.65rem] text-customer-muted">Alternative service rows are presentation previews.</p>
                    </div>

                    <div class="mt-5 flex items-center justify-between gap-4"><div><p class="text-sm font-bold">Party size</p><p class="text-xs text-customer-muted">Number of people</p></div><div class="flex items-center gap-4"><span class="grid size-10 place-items-center rounded-full border border-customer-line text-xl text-customer-muted">&minus;</span><strong>1</strong><span class="grid size-10 place-items-center rounded-full border border-customer-line text-xl text-customer-muted">&plus;</span></div></div>

                    <div class="mt-5 rounded-[1.25rem] bg-customer-navy p-5 text-white">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-[0.65rem] font-bold tracking-[0.13em] text-white/65">QUEUE OPEN</p>
                                <p class="mt-1 text-lg font-bold">Accepting customers</p>
                            </div>
                            <span class="size-2 rounded-full bg-customer-green" aria-hidden="true"></span>
                        </div>
                        <p class="mt-2 text-sm leading-5 text-white/65">{{ $queue->name }} is open for {{ $service->name }}.</p>
                        <div class="mt-4 flex items-end justify-between gap-4 border-t border-white/15 pt-4"><div><p class="text-[0.65rem] font-bold tracking-[0.12em] text-white/55">YOUR NUMBER &middot; PREVIEW</p><p class="mt-1 text-3xl font-bold text-customer-yellow">{{ $presentation['number'] }}</p></div><div class="text-right text-xs"><p><strong>{{ $presentation['peopleAhead'] }}</strong> people ahead</p><p class="mt-1"><strong>{{ $presentation['estimatedWaitMinutes'] }} min</strong> estimated wait</p></div></div>
                    </div>

                    <form class="mt-7" method="POST" action="{{ route('queue-entries.store', $queue->id) }}" data-customer-join-form>
                        @csrf
                        <input type="hidden" name="businessId" value="{{ $business->id }}">
                        <input type="hidden" name="branchId" value="{{ $branch->id }}">
                        <input type="hidden" name="serviceId" value="{{ $service->id }}">
                        <p class="mb-3 text-center text-xs text-customer-muted">The preview is not your issued number. Spring will issue and securely save the real ticket.</p>
                        <button class="customer-primary-button" type="submit">Get my ticket</button>
                        <span class="sr-only">Join queue</span>
                    </form>
                @elseif ($queue->status === 'PAUSED')
                    <div class="customer-empty-state">
                        <p class="text-xs font-bold tracking-[0.1em] text-amber-700">QUEUE PAUSED</p>
                        <p class="mt-3 text-xl font-bold">Temporarily unavailable</p>
                        <p class="mt-2 text-sm leading-5 text-customer-muted">This queue is paused. Please check again shortly.</p>
                        <a class="customer-text-link mt-4" href="{{ route('branches.show', [$business->id, $branch->id]) }}">Choose another service <span aria-hidden="true">&rsaquo;</span></a>
                    </div>
                @else
                    <div class="customer-empty-state">
                        <p class="text-xs font-bold tracking-[0.1em] text-customer-muted">QUEUE CLOSED</p>
                        <p class="mt-3 text-xl font-bold">Closed for today</p>
                        <p class="mt-2 text-sm leading-5 text-customer-muted">This queue is no longer accepting customers today.</p>
                        <a class="customer-text-link mt-4" href="{{ route('branches.show', [$business->id, $branch->id]) }}">Choose another service <span aria-hidden="true">&rsaquo;</span></a>
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection
