@extends('layouts.staff')

@section('title', $business->name)

@section('staff-content')
    <a class="text-sm font-semibold text-brand" href="{{ route('staff.businesses.index') }}">&larr; Your businesses</a>

    <div class="mt-8 flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold tracking-[0.18em] text-brand">BUSINESS</p>
            <h1 class="mt-3 font-editorial text-4xl tracking-[-0.035em]">{{ $business->name }}</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-muted">{{ $business->description ?: 'No description provided.' }}</p>
        </div>
        <a class="inline-flex items-center justify-center rounded-xl bg-brand px-5 py-3 text-sm font-semibold text-white transition hover:bg-ink" href="{{ route('staff.branches.create', $business->id) }}">Add branch</a>
    </div>

    <section class="mt-10 border-t border-line pt-8">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-editorial text-3xl">Branches</h2>
            <span class="text-sm text-muted">{{ count($branches) }} total</span>
        </div>

        @if ($branches === [])
            <div class="mt-5 rounded-2xl border border-dashed border-line bg-surface p-7 text-sm leading-6 text-muted">No branches yet. Add a branch to organize services by location.</div>
        @else
            <div class="mt-5 divide-y divide-line border-y border-line">
                @foreach ($branches as $branch)
                    <a class="flex flex-col gap-2 py-5 transition hover:text-brand sm:flex-row sm:items-center sm:justify-between" href="{{ route('staff.branches.show', [$business->id, $branch->id]) }}">
                        <div>
                            <h3 class="font-semibold">{{ $branch->name }}</h3>
                            <p class="mt-1 text-sm text-muted">{{ $branch->address }}</p>
                        </div>
                        <span class="text-sm font-semibold text-brand">Manage branch &rarr;</span>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
@endsection
