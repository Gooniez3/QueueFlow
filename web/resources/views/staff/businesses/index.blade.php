@extends('layouts.staff')

@section('title', 'Your businesses')

@section('staff-content')
    <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold tracking-[0.18em] text-brand">BUSINESS MANAGEMENT</p>
            <h1 class="mt-3 font-editorial text-4xl tracking-[-0.035em]">Your businesses</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-muted">Only businesses represented by your current QueueFlow memberships appear here.</p>
        </div>

        <a class="inline-flex items-center justify-center rounded-xl bg-brand px-5 py-3 text-sm font-semibold text-white transition hover:bg-ink" href="{{ route('staff.businesses.create') }}">
            Create business
        </a>
    </div>

    @if ($businesses === [])
        <section class="mt-10 rounded-2xl border border-line bg-surface p-7 sm:p-10">
            <h2 class="font-editorial text-2xl">Create your first business</h2>
            <p class="mt-3 max-w-xl text-sm leading-6 text-muted">You do not have a business membership yet. Create a business to become its initial owner and begin adding branches and services.</p>
            <a class="mt-6 inline-flex text-sm font-semibold text-brand underline decoration-brand/30 underline-offset-4" href="{{ route('staff.businesses.create') }}">Create Business</a>
        </section>
    @else
        <div class="mt-10 grid gap-4 md:grid-cols-2">
            @foreach ($businesses as $business)
                <a class="rounded-2xl border border-line bg-surface p-6 transition hover:border-brand/40 hover:shadow-[0_18px_45px_-35px_rgba(7,23,19,0.45)]" href="{{ route('staff.businesses.show', $business->id) }}">
                    <h2 class="font-editorial text-2xl">{{ $business->name }}</h2>
                    <p class="mt-3 text-sm leading-6 text-muted">{{ $business->description ?: 'No description provided.' }}</p>
                    <span class="mt-5 inline-flex text-sm font-semibold text-brand">Open business &rarr;</span>
                </a>
            @endforeach
        </div>
    @endif
@endsection
