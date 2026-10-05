@extends('layouts.staff')

@section('title', 'Services')
@section('staff-area', 'Management')

@section('staff-content')
    <section class="staff-resource-hero" aria-labelledby="services-heading">
        <div class="staff-resource-hero-content">
            <div class="staff-resource-identity">
                <span class="staff-resource-mark" aria-hidden="true"><x-staff.icon class="size-6" name="service" /></span>
                <div class="min-w-0">
                    <p class="font-staff-display text-[0.68rem] font-bold tracking-[0.14em] text-staff-amber">MANAGEMENT</p>
                    <h1 id="services-heading" class="staff-resource-title mt-2">Services</h1>
                    <p class="staff-resource-subtitle">Services offered across the branches available to you.</p>
                </div>
            </div>
        </div>
        <dl class="staff-hero-metrics">
            <div class="staff-hero-metric"><dt class="staff-hero-metric-label">SERVICES</dt><dd class="staff-hero-metric-value">{{ $serviceCount }}</dd></div>
            <div class="staff-hero-metric"><dt class="staff-hero-metric-label">ACTIVE</dt><dd class="staff-hero-metric-value">{{ $activeServiceCount }}</dd></div>
            <div class="staff-hero-metric sm:col-span-2 lg:col-span-1"><dt class="staff-hero-metric-label">INACTIVE</dt><dd class="staff-hero-metric-value">{{ $inactiveServiceCount }}</dd></div>
        </dl>
    </section>

    @if ($serviceCount === 0)
        <section class="staff-empty-dashed mt-5" aria-labelledby="services-empty-heading">
            <span class="mx-auto grid size-11 place-items-center rounded-full bg-staff-indigo-soft text-staff-indigo" aria-hidden="true"><x-staff.icon name="service" /></span>
            <h2 id="services-empty-heading" class="mt-3 font-staff-display text-lg font-bold">No accessible services</h2>
            <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-staff-muted">No services are available through your current QueueFlow branch memberships.</p>
        </section>
    @else
        <div class="mt-5 grid gap-5">
            @foreach ($businesses as $businessContext)
                @foreach ($businessContext['branches'] as $branch)
                    @php($branchServices = $businessContext['servicesByBranchId'][$branch->id] ?? [])
                    @if ($branchServices !== [])
                        <section class="staff-card overflow-hidden" aria-labelledby="services-branch-{{ $branch->id }}">
                            <header class="flex flex-col gap-1 border-b border-[#eeeff8] px-5 py-4 sm:px-6">
                                <p class="staff-information-label">{{ $businessContext['business']->name }}</p>
                                <h2 id="services-branch-{{ $branch->id }}" class="break-words font-staff-display text-lg font-extrabold">{{ $branch->name }}</h2>
                            </header>

                            <div class="grid gap-3 p-4 sm:grid-cols-2 sm:p-5 xl:grid-cols-3">
                                @foreach ($branchServices as $service)
                                    <a class="staff-live-branch-link items-start" href="{{ route('staff.services.show', [$businessContext['business']->id, $branch->id, $service->id]) }}">
                                        <span class="staff-branch-icon" aria-hidden="true"><x-staff.icon name="service" /></span>
                                        <span class="min-w-0 flex-1">
                                            <span class="flex flex-wrap items-center gap-2">
                                                <span class="break-words font-staff-display font-bold">{{ $service->name }}</span>
                                                <span @class(['staff-status-badge', 'border-staff-success/20 bg-staff-success-soft text-staff-success' => $service->active, 'border-staff-line bg-[#e8e9f3] text-[#4a4d68]' => ! $service->active])>{{ $service->active ? 'Active' : 'Inactive' }}</span>
                                            </span>
                                            <span class="mt-2 inline-flex items-center gap-1.5 text-xs font-semibold text-staff-muted"><x-staff.icon class="size-3.5" name="clock" />{{ $service->durationMinutes }} minutes</span>
                                        </span>
                                        <x-staff.icon class="mt-1 size-4 text-staff-muted" name="chevron-right" />
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif
                @endforeach
            @endforeach
        </div>
    @endif
@endsection
