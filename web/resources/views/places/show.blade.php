@extends('layouts.app')

@section('title', $category['title'].' - QueueFlow')
@section('body-class', 'bg-[#f7f6f2] text-customer-navy')

@section('content')
    <div class="customer-page">
        <x-customer-navigation />

        <header class="customer-hero pb-10">
            <div class="relative z-10 flex items-center justify-between gap-3">
                <a class="customer-back-link" href="{{ route('places.index') }}" aria-label="Back to places"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m14.5 6-6 6 6 6" /></svg></a>
                <h1 class="truncate text-xl font-bold">{{ $category['title'] }}</h1>
                <span class="grid size-11 place-items-center rounded-full bg-white/15" aria-hidden="true"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="10.5" cy="10.5" r="6" /><path d="m15 15 4 4" /></svg></span>
            </div>
        </header>

        <main class="px-5 pt-4 pb-7">
            <form class="flex items-center gap-3 rounded-full bg-white px-5 py-2 ring-1 ring-customer-line/40" method="GET" action="{{ route('places.show', $category['slug']) }}" role="search">
                <label class="sr-only" for="category-search">Search this category</label>
                <svg class="size-5 shrink-0 text-customer-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6" /><path d="m15 15 4 4" /></svg>
                <input class="min-h-11 min-w-0 grow border-0 bg-transparent text-sm outline-none placeholder:text-customer-muted/80 focus:ring-0" id="category-search" name="search" type="search" value="{{ $category['search'] }}" placeholder="Search businesses or services" />
                <button class="min-h-11 shrink-0 rounded-full bg-customer-indigo px-4 text-sm font-bold text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-customer-indigo" type="submit">Search</button>
            </form>

            <section class="mt-6 grid gap-4" aria-label="Businesses in {{ $category['title'] }}">
                @forelse ($category['businesses'] as $business)
                    <article class="rounded-[1.4rem] bg-white p-4 shadow-[0_12px_30px_-24px_rgba(21,17,63,0.55)] ring-1 ring-customer-line/40">
                        <a class="flex items-start gap-3" href="{{ route('businesses.show', $business['id']) }}">
                            <span class="customer-resource-icon shrink-0">{{ mb_strtoupper(mb_substr($business['name'], 0, 2)) }}</span>
                            <span class="min-w-0 grow"><span class="block break-words text-sm font-bold">{{ $business['name'] }}</span><span class="mt-1 block text-xs text-customer-muted">{{ $business['description'] ?: 'View locations and available services' }}</span></span>
                            <span class="customer-resource-action shrink-0" aria-hidden="true">&rsaquo;</span>
                        </a>
                        <div class="mt-4 border-t border-customer-line/70 pt-3">
                            @foreach ($business['branches'] as $branch)
                                <div class="py-2 first:pt-0 last:pb-0">
                                    <a class="block min-h-11 rounded-xl px-2 py-2 hover:bg-customer-canvas focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-customer-indigo" href="{{ route('branches.show', [$business['id'], $branch['id']]) }}">
                                        <span class="block text-sm font-semibold">{{ $branch['name'] }}</span>
                                        <span class="mt-1 block text-xs text-customer-muted">{{ $branch['address'] }}</span>
                                    </a>
                                    @if ($branch['services'] !== [])
                                        <div class="mt-1 flex flex-wrap gap-2 px-2">
                                            @foreach ($branch['services'] as $service)
                                                <a class="inline-flex min-h-9 items-center rounded-full bg-customer-canvas px-3 text-xs font-semibold text-customer-indigo focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-customer-indigo" href="{{ route('services.show', [$business['id'], $branch['id'], $service->id]) }}">{{ $service->name }}</a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </article>
                @empty
                    <div class="customer-empty-state text-center">
                        <p class="text-lg font-bold">No places match this category.</p>
                        <p class="mt-2 text-sm text-customer-muted">Try another category or search term.</p>
                        <a class="customer-text-link mt-3" href="{{ route('places.index') }}">Browse all places</a>
                    </div>
                @endforelse
            </section>
        </main>
    </div>
@endsection
