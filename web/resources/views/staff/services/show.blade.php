@extends('layouts.staff')

@section('title', $service->name)
@section('staff-area', 'Businesses')

@section('staff-topbar-breadcrumbs')
    <x-staff.topbar-breadcrumbs
        :items="[
            ['label' => 'Businesses', 'url' => route('staff.businesses.index')],
            ['label' => $business->name, 'url' => route('staff.businesses.show', $business->id)],
            ['label' => $branch->name, 'url' => route('staff.branches.show', [$business->id, $branch->id])],
            ['label' => $service->name],
        ]"
    />
@endsection

@section('staff-context')
    <div class="staff-context-chip" aria-label="Current business: {{ $business->name }}">
        <x-staff.icon class="size-4.5 text-staff-indigo" name="business" />
        <span class="max-w-36 truncate">{{ $business->name }}</span>
    </div>
    <div class="staff-context-chip" aria-label="Current branch: {{ $branch->name }}">
        <x-staff.icon class="size-4.5 text-staff-indigo" name="branch" />
        <span class="max-w-36 truncate">{{ $branch->name }}</span>
    </div>
@endsection

@section('staff-content')
    <div class="mb-5 lg:hidden">
        <x-staff.breadcrumbs
            :back-url="route('staff.branches.show', [$business->id, $branch->id])"
            :back-label="'Back to '.$branch->name"
            :items="[
                ['label' => 'Businesses', 'url' => route('staff.businesses.index')],
                ['label' => $business->name, 'url' => route('staff.businesses.show', $business->id)],
                ['label' => $branch->name, 'url' => route('staff.branches.show', [$business->id, $branch->id])],
                ['label' => $service->name],
            ]"
        />
    </div>

    <header>
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="staff-page-title mt-0">{{ $service->name }}</h1>
            <span class="staff-status-badge {{ $service->active ? 'border-staff-success/20 bg-staff-success-soft text-staff-success' : 'border-staff-line bg-[#e8e9f3] text-[#4a4d68]' }}">
                <span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>
                {{ $service->active ? 'ACTIVE' : 'INACTIVE' }}
            </span>
        </div>
        <p class="staff-page-copy">Service at {{ $branch->name }} &middot; {{ $business->name }}</p>
    </header>

    <section class="staff-card mt-7 p-5 sm:p-5.5" aria-labelledby="description-heading">
        <h2 id="description-heading" class="staff-information-label">DESCRIPTION</h2>
        <p class="mt-2 text-base leading-7 {{ $service->description ? '' : 'italic text-staff-muted' }}">
            {{ $service->description ?: 'A description has not been added for this service.' }}
        </p>
    </section>

    <section class="mt-5 grid gap-5 md:grid-cols-3" aria-label="Service details">
        <article class="staff-card p-5 sm:p-5.5">
            <p class="staff-information-label">DURATION</p>
            <div class="mt-3 flex items-center gap-3">
                <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-staff-indigo-soft text-staff-indigo">
                    <x-staff.icon class="size-5.5" name="clock" />
                </span>
                <p class="font-staff-display text-lg font-bold">{{ $service->durationMinutes }} minutes</p>
            </div>
        </article>

        <article class="staff-card p-5 sm:p-5.5">
            <p class="staff-information-label">BRANCH</p>
            <div class="mt-3 flex min-w-0 items-center gap-3">
                <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-staff-indigo-soft text-staff-indigo">
                    <x-staff.icon class="size-5.5" name="branch" />
                </span>
                <a class="min-w-0 break-words font-staff-display text-lg font-bold transition-colors hover:text-staff-indigo" href="{{ route('staff.branches.show', [$business->id, $branch->id]) }}">{{ $branch->name }}</a>
            </div>
        </article>

        <article class="staff-card p-5 sm:p-5.5">
            <p class="staff-information-label">STATUS</p>
            <div class="mt-3 flex items-center gap-3">
                <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-staff-indigo-soft text-staff-indigo" aria-hidden="true">
                    <svg class="size-5.5" viewBox="0 0 24 24" fill="none">
                        <path d="m5 12 4.5 4.5L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                <p class="font-staff-display text-lg font-bold">{{ $service->active ? 'Active' : 'Inactive' }}</p>
            </div>
        </article>
    </section>
@endsection
