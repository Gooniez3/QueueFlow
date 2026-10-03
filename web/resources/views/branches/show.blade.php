@extends('layouts.app')

@section('title', $branch->name.' - QueueFlow')
@section('body-class', 'bg-[#f7f6f2] text-customer-navy')

@section('content')
    <div class="customer-page">
        <x-customer-navigation />

        <header class="customer-hero pb-16">
            <div class="relative z-10 flex items-center gap-3">
                <a class="customer-back-link" href="{{ route('businesses.show', $business->id) }}" aria-label="Back to {{ $business->name }}">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m14.5 6-6 6 6 6" /></svg>
                </a>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-white/70">Choose a service</p>
                    <h1 class="truncate text-xl font-bold tracking-[-0.025em]">{{ $branch->name }}</h1>
                </div>
            </div>
        </header>

        <section class="relative z-10 -mt-7 px-5 pb-7" aria-labelledby="services-heading">
            <div class="rounded-[1.5rem] bg-white p-5 shadow-[0_18px_45px_-28px_rgba(21,17,63,0.55)] ring-1 ring-customer-line/40">
                <div class="flex items-start gap-3">
                    <span class="grid size-11 shrink-0 place-items-center rounded-full bg-customer-indigo/10 text-customer-indigo">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.25 10.5c0 5.25-8.25 10.5-8.25 10.5S3.75 15.75 3.75 10.5a8.25 8.25 0 1 1 16.5 0Z" /><circle cx="12" cy="10.5" r="2.5" /></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="font-bold">{{ $branch->name }}</p>
                        <p class="mt-1 text-xs leading-4 text-customer-muted">{{ $business->name }} &middot; {{ $branch->address }}</p>
                    </div>
                </div>
            </div>

            <h2 id="services-heading" class="mt-7 text-lg font-bold tracking-[-0.025em]">Available services</h2>

            <div class="customer-resource-list">
                @forelse ($services as $service)
                    @if ($service->active)
                        <a class="customer-resource-row" href="{{ route('services.show', [$business->id, $branch->id, $service->id]) }}">
                            <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-customer-yellow text-customer-navy">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.25 7.5h13.5M5.25 12h13.5M5.25 16.5h8.25" /></svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-bold">{{ $service->name }}</span>
                                <span class="mt-1 block truncate text-xs text-customer-muted">{{ $service->description ?: $service->durationMinutes.' minute service' }}</span>
                                <span class="mt-1.5 block text-xs font-bold text-customer-indigo">{{ $service->durationMinutes }} min</span>
                            </span>
                            <span class="customer-resource-action" aria-hidden="true">&rsaquo;</span>
                        </a>
                    @else
                        <div class="grid min-h-20 min-w-0 grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-3 rounded-[1.15rem] bg-white/65 px-4 py-3 text-customer-muted ring-1 ring-customer-line/45">
                            <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-customer-line/45 text-customer-muted">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><path d="M5.25 7.5h13.5M5.25 12h13.5M5.25 16.5h8.25" /></svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-bold text-customer-navy">{{ $service->name }}</span>
                                <span class="mt-1 block truncate text-xs">{{ $service->description ?: $service->durationMinutes.' minute service' }}</span>
                                <span class="mt-1.5 block text-xs font-bold">{{ $service->durationMinutes }} min</span>
                            </span>
                            <span class="shrink-0 rounded-full bg-customer-line/50 px-2.5 py-1 text-[0.65rem] font-bold text-customer-muted">Unavailable</span>
                        </div>
                    @endif
                @empty
                    <div class="customer-empty-state text-center">
                        <p class="text-xl font-bold">No services are available.</p>
                        <p class="mt-2 text-sm text-customer-muted">Please check again later.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
