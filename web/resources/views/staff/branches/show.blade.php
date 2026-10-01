@extends('layouts.staff')

@section('title', $branch->name)

@section('staff-content')
    <x-staff.breadcrumbs
        :back-url="route('staff.businesses.show', $business->id)"
        :back-label="'Back to '.$business->name"
        :items="[
            ['label' => 'Businesses', 'url' => route('staff.businesses.index')],
            ['label' => $business->name, 'url' => route('staff.businesses.show', $business->id)],
            ['label' => $branch->name],
        ]"
    />

    <header class="mt-3 flex flex-col gap-3 border-b border-line/60 pb-5 sm:mt-5 sm:gap-5 sm:pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="staff-eyebrow">BRANCH</p>
            <h1 class="staff-page-title">{{ $branch->name }}</h1>
            <p class="staff-page-copy">{{ $branch->address }}</p>
            @if ($branch->latitude !== null && $branch->longitude !== null)
                <p class="mt-2 text-xs text-muted tabular-nums" aria-label="Coordinates">{{ $branch->latitude }}, {{ $branch->longitude }}</p>
            @endif
        </div>
        <a class="staff-primary-button w-fit shrink-0" href="{{ route('staff.services.create', [$business->id, $branch->id]) }}" aria-label="Add service">
            <span class="text-xl leading-none" aria-hidden="true">+</span>
            <span>Add service</span>
        </a>
    </header>

    <section class="mt-6 sm:mt-8" aria-labelledby="services-heading">
        <div class="flex items-baseline gap-3">
            <h2 id="services-heading" class="staff-section-title">Services</h2>
            <p class="text-sm text-muted">{{ count($services) }} {{ count($services) === 1 ? 'service' : 'services' }}</p>
        </div>

        @if ($services === [])
            <div class="staff-empty-state">
                <h3 class="text-base font-semibold text-ink">No services yet</h3>
                <p class="mt-2 max-w-xl text-sm leading-6 text-muted">Add the first service customers can receive at this branch.</p>
            </div>
        @else
            <div class="staff-resource-list">
                @foreach ($services as $service)
                    <a class="staff-resource-row" href="{{ route('staff.services.show', [$business->id, $branch->id, $service->id]) }}">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                                <h3 class="break-words font-editorial text-lg font-semibold tracking-[-0.015em] text-ink sm:text-xl sm:tracking-[-0.02em]">{{ $service->name }}</h3>
                                <span class="staff-status-badge {{ $service->active ? 'border-brand/20 bg-brand/5 text-brand' : 'border-line bg-cream text-muted' }}">
                                    <span class="size-1.5 rounded-full {{ $service->active ? 'bg-brand' : 'bg-muted' }}" aria-hidden="true"></span>
                                    {{ $service->active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            @if ($service->description)
                                <p class="mt-0.5 max-w-2xl text-sm leading-5 text-muted sm:mt-1">{{ $service->description }}</p>
                            @endif
                            <p class="mt-1.5 inline-flex items-center gap-1.5 text-xs font-medium text-muted sm:mt-2">
                                <svg class="size-4 text-brand" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="6.5" stroke="currentColor" stroke-width="1.4" /><path d="M10 6.5V10l2.5 1.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                {{ $service->durationMinutes }} min
                            </p>
                        </div>
                        <span class="grid size-10 shrink-0 place-items-center text-muted" aria-hidden="true">
                            <svg class="size-4" viewBox="0 0 16 16" fill="none"><path d="m6 3 5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
@endsection
