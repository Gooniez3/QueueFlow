@extends('layouts.app')

@section('title', $service->name.' - QueueFlow')
@section('body-class', 'bg-cream text-ink')

@section('content')
    <div class="mx-auto min-h-screen max-w-3xl px-5 pt-5 pb-[calc(7rem+env(safe-area-inset-bottom))] sm:px-6 sm:pt-8 md:pb-10">
        <x-customer-navigation />

        <header class="mt-10 border-b border-line pb-7 sm:mt-14">
            <a class="inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-brand" href="{{ route('branches.show', [$business->id, $branch->id]) }}"><span aria-hidden="true">&larr;</span> {{ $branch->name }}</a>
            <p class="mt-6 text-xs font-semibold tracking-[0.18em] text-brand">SERVICE</p>
            <h1 class="mt-3 font-editorial text-4xl leading-tight tracking-[-0.035em] sm:text-5xl">{{ $service->name }}</h1>
            <p class="mt-3 text-sm leading-6 text-muted">{{ $business->name }} <span class="px-1 text-line">&middot;</span> {{ $branch->name }}</p>
            @if ($service->description)
                <p class="mt-3 text-sm leading-6 text-muted">{{ $service->description }}</p>
            @endif
        </header>

        <section class="py-7" aria-labelledby="availability-heading">
            <h2 id="availability-heading" class="text-lg font-semibold tracking-[-0.02em]">Today&rsquo;s queue</h2>

            @if (! $service->active)
                <div class="mt-4 rounded-2xl border border-line bg-white px-5 py-6">
                    <p class="font-editorial text-2xl font-semibold">Service unavailable</p>
                    <p class="mt-2 text-sm leading-6 text-muted">This service is not currently accepting customers.</p>
                </div>
            @elseif ($queue === null)
                <div class="mt-4 rounded-2xl border border-line bg-white px-5 py-6">
                    <p class="font-editorial text-2xl font-semibold">No queue available today</p>
                    <p class="mt-2 text-sm leading-6 text-muted">Please check again later or choose another service.</p>
                </div>
            @elseif ($queue->status === 'OPEN')
                <div class="mt-4 rounded-2xl border border-brand/20 bg-white px-5 py-6">
                    <p class="text-xs font-semibold tracking-[0.16em] text-brand">QUEUE OPEN</p>
                    <p class="mt-3 font-editorial text-2xl font-semibold">Accepting customers</p>
                    <p class="mt-2 text-sm leading-6 text-muted">{{ $queue->name }} is open for {{ $service->name }}.</p>
                    <div class="mt-5 rounded-xl bg-cream px-4 py-3 text-sm font-medium text-muted" aria-disabled="true" data-join-placeholder>
                        Joining will be available soon.
                    </div>
                </div>
            @elseif ($queue->status === 'PAUSED')
                <div class="mt-4 rounded-2xl border border-amber/35 bg-white px-5 py-6">
                    <p class="text-xs font-semibold tracking-[0.16em] text-amber-ink">QUEUE PAUSED</p>
                    <p class="mt-3 font-editorial text-2xl font-semibold">Temporarily unavailable</p>
                    <p class="mt-2 text-sm leading-6 text-muted">This queue is paused. Please check again shortly.</p>
                </div>
            @else
                <div class="mt-4 rounded-2xl border border-line bg-white px-5 py-6">
                    <p class="text-xs font-semibold tracking-[0.16em] text-muted">QUEUE CLOSED</p>
                    <p class="mt-3 font-editorial text-2xl font-semibold">Closed for today</p>
                    <p class="mt-2 text-sm leading-6 text-muted">This queue is no longer accepting customers today.</p>
                </div>
            @endif
        </section>
    </div>
@endsection
