@extends('layouts.staff')

@section('title', $branch->name)

@section('staff-content')
    <a class="text-sm font-semibold text-brand" href="{{ route('staff.businesses.show', $business->id) }}">&larr; {{ $business->name }}</a>

    <div class="mt-8 flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold tracking-[0.18em] text-brand">BRANCH</p>
            <h1 class="mt-3 font-editorial text-4xl tracking-[-0.035em]">{{ $branch->name }}</h1>
            <p class="mt-3 text-sm leading-6 text-muted">{{ $branch->address }}</p>
            @if ($branch->latitude !== null && $branch->longitude !== null)
                <p class="mt-2 text-xs text-muted">{{ $branch->latitude }}, {{ $branch->longitude }}</p>
            @endif
        </div>
        <a class="inline-flex items-center justify-center rounded-xl bg-brand px-5 py-3 text-sm font-semibold text-white transition hover:bg-ink" href="{{ route('staff.services.create', [$business->id, $branch->id]) }}">Add service</a>
    </div>

    <section class="mt-10 border-t border-line pt-8">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-editorial text-3xl">Services</h2>
            <span class="text-sm text-muted">{{ count($services) }} total</span>
        </div>

        @if ($services === [])
            <div class="mt-5 rounded-2xl border border-dashed border-line bg-surface p-7 text-sm leading-6 text-muted">No services yet. Add the first service offered at this branch.</div>
        @else
            <div class="mt-5 divide-y divide-line border-y border-line">
                @foreach ($services as $service)
                    <a class="flex items-center justify-between gap-5 py-5 transition hover:text-brand" href="{{ route('staff.services.show', [$business->id, $branch->id, $service->id]) }}">
                        <div>
                            <h3 class="font-semibold">{{ $service->name }}</h3>
                            <p class="mt-1 text-sm text-muted">{{ $service->durationMinutes }} minutes</p>
                        </div>
                        <span class="text-xs font-semibold {{ $service->active ? 'text-brand' : 'text-muted' }}">{{ $service->active ? 'Active' : 'Inactive' }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
@endsection
