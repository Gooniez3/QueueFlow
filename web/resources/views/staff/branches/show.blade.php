@extends('layouts.staff')

@section('title', $branch->name)
@section('staff-area', 'Businesses')

@section('staff-topbar-breadcrumbs')
    <x-staff.topbar-breadcrumbs
        :items="[
            ['label' => 'Businesses', 'url' => route('staff.businesses.index')],
            ['label' => $business->name, 'url' => route('staff.businesses.show', $business->id)],
            ['label' => $branch->name],
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
            :back-url="route('staff.businesses.show', $business->id)"
            :back-label="'Back to '.$business->name"
            :items="[
                ['label' => 'Businesses', 'url' => route('staff.businesses.index')],
                ['label' => $business->name, 'url' => route('staff.businesses.show', $business->id)],
                ['label' => $branch->name],
            ]"
        />
    </div>

    <header class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="staff-page-title mt-0">{{ $branch->name }}</h1>
            <p class="staff-page-copy">Branch of {{ $business->name }}</p>
        </div>
        <a class="staff-primary-button w-fit shrink-0" href="{{ route('staff.services.create', [$business->id, $branch->id]) }}">
            <x-staff.icon class="size-4.5" name="plus" />
            Create service
        </a>
    </header>

    <section class="staff-card mt-7 p-5 sm:p-5.5" aria-labelledby="branch-information-heading">
        <h2 id="branch-information-heading" class="sr-only">Branch information</h2>
        <dl class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3 xl:gap-10">
            <div>
                <dt class="staff-information-label">ADDRESS</dt>
                <dd class="mt-1 break-words font-semibold">{{ $branch->address }}</dd>
            </div>
            <div>
                <dt class="staff-information-label">TIMEZONE</dt>
                <dd class="mt-1 break-words font-semibold">{{ $branch->timezone }}</dd>
            </div>
            <div>
                <dt class="staff-information-label">BUSINESS</dt>
                <dd class="mt-1 break-words font-semibold">
                    <a class="transition-colors hover:text-staff-indigo" href="{{ route('staff.businesses.show', $business->id) }}">{{ $business->name }}</a>
                </dd>
            </div>
        </dl>
    </section>

    <section class="staff-card mt-5 overflow-hidden" aria-labelledby="services-heading">
        <header class="flex min-h-18 items-center px-5 py-4 sm:px-5.5">
            <h2 id="services-heading" class="staff-card-title">
                Services
                <span class="font-staff-sans font-medium text-staff-muted">&middot; {{ count($services) }}</span>
            </h2>
        </header>

        @if ($services === [])
            <div class="border-t border-[#eeeff8] px-5 py-10 sm:px-5.5">
                <h3 class="font-staff-display text-base font-bold">No services yet</h3>
                <p class="mt-2 max-w-xl text-sm leading-6 text-staff-muted">Create the first service customers can receive at this branch.</p>
            </div>
        @else
            <div class="hidden grid-cols-[minmax(0,2fr)_minmax(7rem,0.7fr)_minmax(7rem,0.7fr)_6.5rem] gap-4 bg-[#f8f8fd] px-5.5 py-3 font-staff-display text-[0.68rem] font-semibold tracking-[0.12em] text-staff-muted md:grid">
                <span>SERVICE</span>
                <span>DURATION</span>
                <span>STATUS</span>
                <span class="sr-only">Action</span>
            </div>

            <div>
                @foreach ($services as $service)
                    <article class="grid gap-4 border-t border-[#eeeff8] px-5 py-4 md:grid-cols-[minmax(0,2fr)_minmax(7rem,0.7fr)_minmax(7rem,0.7fr)_6.5rem] md:items-center md:px-5.5">
                        <div class="min-w-0">
                            <h3 class="break-words font-bold text-staff-ink">{{ $service->name }}</h3>
                            <p class="mt-1 break-words text-[0.8rem] leading-5 text-staff-muted {{ $service->description ? '' : 'italic' }}">
                                {{ $service->description ?: 'No description added.' }}
                            </p>
                        </div>
                        <div>
                            <span class="staff-information-label md:hidden">DURATION</span>
                            <p class="mt-1 font-staff-display text-sm font-bold md:mt-0">{{ $service->durationMinutes }} min</p>
                        </div>
                        <div>
                            <span class="staff-information-label md:hidden">STATUS</span>
                            <p class="mt-1 md:mt-0">
                                <span class="staff-status-badge {{ $service->active ? 'border-staff-success/20 bg-staff-success-soft text-staff-success' : 'border-staff-line bg-[#e8e9f3] text-[#4a4d68]' }}">
                                    <span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>
                                    {{ $service->active ? 'Active' : 'Inactive' }}
                                </span>
                            </p>
                        </div>
                        <a class="staff-secondary-button staff-button-small w-fit md:justify-self-end" href="{{ route('staff.services.show', [$business->id, $branch->id, $service->id]) }}">
                            View
                            <x-staff.icon class="size-4" name="chevron-right" />
                        </a>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
