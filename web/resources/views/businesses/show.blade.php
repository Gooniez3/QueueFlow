@extends('layouts.app')

@section('title', $business->name.' - QueueFlow')
@section('body-class', 'bg-[#f7f6f2] text-customer-navy')

@section('content')
    <div class="customer-page">
        <x-customer-navigation />

        <header class="customer-hero pb-16">
            <div class="relative z-10 flex items-center gap-3">
                <a class="customer-back-link" href="{{ route('places.index') }}" aria-label="Back to all places">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m14.5 6-6 6 6 6" /></svg>
                </a>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-white/70">Choose a location</p>
                    <h1 class="truncate text-xl font-bold tracking-[-0.025em]">{{ $business->name }}</h1>
                </div>
            </div>
        </header>

        <section class="relative z-10 -mt-7 px-5 pb-7" aria-labelledby="branches-heading">
            <div class="customer-floating-panel mx-0 mt-0">
                <div class="flex items-center gap-3">
                    <span class="customer-resource-icon">{{ mb_strtoupper(mb_substr($business->name, 0, 2)) }}</span>
                    <div class="min-w-0">
                        <p class="font-bold">{{ $business->name }}</p>
                        @if ($business->description)
                            <p class="mt-1 text-xs leading-4 text-customer-muted">{{ $business->description }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <h2 id="branches-heading" class="mt-7 text-lg font-bold tracking-[-0.025em]">Available locations</h2>

            <div class="customer-resource-list">
                @forelse ($branches as $branch)
                    <a class="customer-resource-row" href="{{ route('branches.show', [$business->id, $branch->id]) }}">
                        <span class="grid size-12 shrink-0 place-items-center rounded-full bg-customer-indigo/10 text-customer-indigo">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M20.25 10.5c0 5.25-8.25 10.5-8.25 10.5S3.75 15.75 3.75 10.5a8.25 8.25 0 1 1 16.5 0Z" /><circle cx="12" cy="10.5" r="2.5" />
                            </svg>
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-bold">{{ $branch->name }}</span>
                            <span class="mt-1 block truncate text-xs text-customer-muted">{{ $branch->address }}</span>
                        </span>
                        <span class="customer-resource-action" aria-hidden="true">&rsaquo;</span>
                    </a>
                @empty
                    <div class="customer-empty-state text-center">
                        <p class="text-xl font-bold">No locations are available.</p>
                        <p class="mt-2 text-sm text-customer-muted">Please check again later.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
