@extends('layouts.app')

@section('title', 'Places - QueueFlow')
@section('body-class', 'bg-[#f7f6f2] text-customer-navy')

@section('content')
    <div class="customer-page">
        <x-customer-navigation />

        <header class="customer-hero pb-16">
            <div class="relative z-10"><h1 class="customer-page-title">Places</h1><p class="customer-page-copy">Pick a category to see live queues.</p></div>
        </header>

        <main class="relative z-10 -mt-7 px-5 pb-7">
            <form class="flex items-center gap-3 rounded-full bg-white px-5 py-2 shadow-[0_16px_35px_-24px_rgba(21,17,63,0.55)] ring-1 ring-customer-line/40" method="GET" action="{{ route('places.index') }}" role="search">
                <svg class="size-5 shrink-0 text-customer-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6" /><path d="m15 15 4 4" /></svg>
                <label class="sr-only" for="place-search">Search businesses, branches or services</label>
                <input class="min-h-11 min-w-0 grow border-0 bg-transparent text-sm text-customer-navy outline-none placeholder:text-customer-muted/80 focus:ring-0" id="place-search" name="search" type="search" value="{{ $search }}" placeholder="Search businesses or services" />
                <button class="min-h-11 shrink-0 rounded-full bg-customer-indigo px-4 text-sm font-bold text-white transition hover:bg-customer-indigo-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-customer-indigo" type="submit">Search</button>
            </form>

            <section class="mt-7" aria-labelledby="categories-heading">
                <h2 id="categories-heading" class="text-lg font-bold tracking-[-0.025em]">What do you need today?</h2>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    @forelse ($categories as $category)
                        <a class="relative flex min-h-36 flex-col items-center justify-center rounded-[1.4rem] bg-white px-3 py-4 text-center shadow-[0_12px_30px_-24px_rgba(21,17,63,0.55)] ring-1 ring-customer-line/40 transition hover:-translate-y-0.5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-customer-indigo" href="{{ route('places.show', $category['slug']) }}{{ $search !== '' ? '?search='.urlencode($search) : '' }}">
                            <span class="grid size-16 place-items-center rounded-full border-[7px] border-customer-indigo/15 bg-customer-indigo text-xl font-bold text-white" aria-hidden="true">{{ mb_strtoupper(mb_substr($category['name'], 0, 1)) }}</span>
                            <span class="mt-3 text-sm font-bold leading-4">{{ $category['name'] }}</span>
                            <span class="mt-1 text-xs text-customer-muted">{{ $category['count'] }} {{ $category['count'] === 1 ? 'place' : 'places' }}</span>
                            <span class="absolute top-3 right-3 grid size-7 place-items-center rounded-full bg-customer-canvas text-customer-indigo" aria-hidden="true">&rsaquo;</span>
                        </a>
                    @empty
                        <p class="col-span-2 rounded-2xl bg-white px-5 py-6 text-center text-sm text-customer-muted ring-1 ring-customer-line/40">No categories match this search.</p>
                    @endforelse
                </div>
            </section>

            <section class="mt-8 border-t border-customer-line/70 pt-7" aria-labelledby="all-places-heading">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <h2 id="all-places-heading" class="text-lg font-bold tracking-[-0.025em]">Browse places</h2>
                    </div>
                    <span class="shrink-0 text-xs text-customer-muted">{{ count($businesses) }} {{ count($businesses) === 1 ? 'place' : 'places' }}</span>
                </div>

                <div class="customer-resource-list">
                    @forelse ($businesses as $business)
                        <a class="customer-resource-row" href="{{ route('businesses.show', $business['id']) }}">
                            <span class="customer-resource-icon">{{ mb_strtoupper(mb_substr($business['name'], 0, 2)) }}</span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-bold">{{ $business['name'] }}</span>
                                <span class="mt-1 block truncate text-xs text-customer-muted">{{ $business['description'] ?: 'View locations and available services' }}</span>
                            </span>
                            <span class="customer-resource-action" aria-hidden="true">&rsaquo;</span>
                        </a>
                    @empty
                        <div class="customer-empty-state text-center">
                            <p class="text-lg font-bold">No places match your search.</p>
                            <p class="mt-2 text-sm text-customer-muted">Try another business, branch or service.</p>
                            <a class="customer-text-link mt-3" href="{{ route('places.index') }}">Clear search</a>
                        </div>
                    @endforelse
                </div>
            </section>
        </main>
    </div>
@endsection
