@extends('layouts.app')

@section('title', $category['title'].' - QueueFlow')
@section('body-class', 'bg-[#f7f6f2] text-customer-navy')

@section('content')
    <div class="customer-page">
        <x-customer-navigation />

        <header class="customer-hero pb-10">
            <div class="relative z-10 flex items-center justify-between gap-3">
                <a class="customer-back-link" href="{{ route('places.index') }}" aria-label="Back to place categories"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m14.5 6-6 6 6 6" /></svg></a>
                <h1 class="truncate text-xl font-bold">{{ $category['title'] }}</h1>
                <span class="grid size-11 place-items-center rounded-full bg-white/15"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6" /><path d="m15 15 4 4" /></svg></span>
            </div>
        </header>

        <main class="px-5 pt-4 pb-7">
            <div class="flex gap-2 overflow-x-auto pb-1" aria-label="Presentation filters">
                <span class="shrink-0 rounded-full bg-customer-navy px-4 py-2 text-xs font-bold text-white">Near me</span>
                <span class="shrink-0 rounded-full bg-white px-4 py-2 text-xs font-bold">Open now</span>
                <span class="shrink-0 rounded-full bg-white px-4 py-2 text-xs font-bold">Shortest wait</span>
            </div>

            <section class="mt-4 grid gap-3" aria-label="Places">
                @foreach ($category['places'] as $place)
                    <article class="flex items-center gap-3 rounded-[1.4rem] bg-white p-4 shadow-[0_12px_30px_-24px_rgba(21,17,63,0.55)] ring-1 ring-customer-line/40">
                        <span @class(['grid size-14 shrink-0 place-items-center rounded-2xl text-base font-bold text-white', 'bg-customer-indigo' => $place['tone'] === 'indigo', 'bg-teal-600' => $place['tone'] === 'teal', 'bg-blue-500' => $place['tone'] === 'blue', 'bg-red-500' => $place['tone'] === 'red', 'bg-purple-600' => $place['tone'] === 'purple'])>{{ $place['initials'] }}</span>
                        <div class="min-w-0 grow"><h2 class="truncate text-sm font-bold">{{ $place['name'] }}</h2><p class="mt-1 truncate text-xs text-customer-muted">{{ $place['area'] }} &middot; {{ $place['distance'] }}</p><p class="mt-1 flex items-center gap-1 text-xs text-customer-muted"><span class="size-2 rounded-full bg-customer-green"></span>{{ $place['open'] ? 'Open now' : 'Closed' }}</p></div>
                        <div class="text-right"><p class="text-2xl leading-none font-bold tabular-nums">{{ str_pad((string) $place['waiting'], 2, '0', STR_PAD_LEFT) }}</p><p class="text-[0.65rem] text-customer-muted">waiting</p><span @class(['mt-2 block h-1 w-10 rounded-full', 'bg-customer-green' => $place['waiting'] <= 5, 'bg-amber-500' => $place['waiting'] > 5 && $place['waiting'] < 10, 'bg-red-500' => $place['waiting'] >= 10])></span></div>
                    </article>
                @endforeach
            </section>
            <p class="mt-4 text-center text-[0.68rem] text-customer-muted">Locations, distance and wait indicators are temporary presentation data.</p>
            <a class="customer-secondary-button mt-5" href="{{ route('home') }}">Browse real QueueFlow businesses</a>
        </main>
    </div>
@endsection
