@extends('layouts.app')

@section('title', $business->name.' - QueueFlow')
@section('body-class', 'bg-cream text-ink')

@section('content')
    <div class="mx-auto min-h-screen max-w-3xl px-5 pt-5 pb-[calc(7rem+env(safe-area-inset-bottom))] sm:px-6 sm:pt-8 md:pb-10">
        <x-customer-navigation />

        <header class="mt-10 border-b border-line pb-7 sm:mt-14">
            <a class="inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-brand" href="{{ route('home') }}"><span aria-hidden="true">&larr;</span> All businesses</a>
            <p class="mt-6 text-xs font-semibold tracking-[0.18em] text-brand">CHOOSE A LOCATION</p>
            <h1 class="mt-3 font-editorial text-4xl leading-tight tracking-[-0.035em] sm:text-5xl">{{ $business->name }}</h1>
            @if ($business->description)
                <p class="mt-3 text-sm leading-6 text-muted">{{ $business->description }}</p>
            @endif
        </header>

        <section class="py-7" aria-labelledby="branches-heading">
            <h2 id="branches-heading" class="text-lg font-semibold tracking-[-0.02em]">Locations</h2>

            <div class="mt-4 grid gap-3">
                @forelse ($branches as $branch)
                    <a
                        class="grid min-h-24 grid-cols-[minmax(0,1fr)_auto] items-center gap-4 rounded-2xl border border-line bg-white px-5 py-4 text-ink transition hover:border-brand/35 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
                        href="{{ route('branches.show', [$business->id, $branch->id]) }}"
                    >
                        <span class="min-w-0">
                            <span class="block font-editorial text-xl font-semibold tracking-[-0.02em]">{{ $branch->name }}</span>
                            <span class="mt-1 block text-sm leading-5 text-muted">{{ $branch->address }}</span>
                        </span>
                        <span class="text-xl text-brand" aria-hidden="true">&rsaquo;</span>
                    </a>
                @empty
                    <div class="rounded-2xl border border-line bg-white px-5 py-6">
                        <p class="font-semibold">No locations are available.</p>
                        <p class="mt-1 text-sm text-muted">Please check again later.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
