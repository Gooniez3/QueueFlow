@extends('layouts.staff')

@section('title', $business->name)
@section('staff-area', 'Businesses')

@section('staff-topbar-breadcrumbs')
    <x-staff.topbar-breadcrumbs :items="[['label' => 'Businesses', 'url' => route('staff.businesses.index')], ['label' => $business->name]]" />
@endsection

@section('staff-context')
    <div class="staff-context-chip" aria-label="Current business: {{ $business->name }}"><x-staff.icon class="size-4.5 text-staff-indigo" name="business" /><span class="max-w-44 truncate">{{ $business->name }}</span></div>
@endsection

@section('staff-content')
    @php
        $businessInitials = collect(preg_split('/\s+/', trim($business->name)))->filter()->take(2)->map(static fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
    @endphp

    <div class="mb-5 lg:hidden">
        <x-staff.breadcrumbs :back-url="route('staff.businesses.index')" back-label="Back to businesses" :items="[['label' => 'Businesses', 'url' => route('staff.businesses.index')], ['label' => $business->name]]" />
    </div>

    <section class="staff-resource-hero" aria-labelledby="business-heading">
        <div class="staff-resource-hero-content">
            <div class="staff-resource-identity">
                <span class="staff-resource-mark" aria-hidden="true">{{ $businessInitials }}</span>
                <div class="min-w-0">
                    <p class="font-staff-display text-[0.68rem] font-bold tracking-[0.14em] text-staff-amber">BUSINESS</p>
                    <h1 id="business-heading" class="staff-resource-title mt-2">{{ $business->name }}</h1>
                    <p class="staff-resource-subtitle {{ $business->description ? '' : 'italic' }}">{{ $business->description ?: 'A description has not been added.' }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($businessRoles as $role)
                            <span class="rounded-full bg-white/15 px-2.5 py-1 text-[0.68rem] font-bold tracking-[0.04em] text-white">{{ $role }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="staff-resource-actions">
                <a class="staff-hero-secondary-button" href="{{ route('staff.businesses.edit', $business->id) }}"><x-staff.icon class="size-4" name="edit" />Edit business</a>
                <a class="staff-amber-button" href="{{ route('staff.branches.create', $business->id) }}"><x-staff.icon class="size-4" name="plus" />Create branch</a>
            </div>
        </div>
        <dl class="staff-hero-metrics">
            <div class="staff-hero-metric"><dt class="staff-hero-metric-label">BRANCHES</dt><dd class="staff-hero-metric-value">{{ count($branches) }}</dd></div>
            <div class="staff-hero-metric"><dt class="staff-hero-metric-label">SERVICES</dt><dd class="staff-hero-metric-value">{{ $serviceCount }}</dd></div>
            <div class="staff-hero-metric"><dt class="staff-hero-metric-label">ACCESS ROLES</dt><dd class="staff-hero-metric-value">{{ count($businessRoles) }}</dd></div>
        </dl>
    </section>

    <div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1fr)_21.25rem] xl:items-start">
        <section aria-labelledby="branches-heading">
            <div class="staff-section-header mb-4">
                <div><h2 id="branches-heading" class="staff-section-title">Branches</h2><p class="mt-1 text-sm text-staff-muted">Locations belonging to {{ $business->name }}.</p></div>
            </div>
            @if ($branches === [])
                <div class="staff-empty-dashed"><h3 class="font-staff-display text-base font-bold">No branches yet</h3><p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-staff-muted">Add a branch to organize the services offered at each business location.</p></div>
            @else
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach ($branches as $branch)
                        @php($branchServices = $servicesByBranchId[$branch->id])
                        <article class="staff-card flex min-h-52 flex-col p-5.5">
                            <div class="flex items-start gap-3.5"><span class="grid size-11 shrink-0 place-items-center rounded-xl bg-staff-indigo-soft text-staff-indigo" aria-hidden="true"><x-staff.icon name="branch" /></span><div class="min-w-0"><h3 class="break-words font-staff-display text-base font-extrabold">{{ $branch->name }}</h3><p class="mt-1 break-words text-[0.8rem] leading-5 text-staff-muted">{{ $branch->address }}</p></div></div>
                            <div class="mt-4 flex flex-wrap items-center gap-1.5">
                                <span class="inline-flex min-h-7 items-center rounded-full bg-staff-canvas px-2.5 text-xs font-semibold text-staff-muted">{{ count($branchServices) }} {{ Str::plural('service', count($branchServices)) }}</span>
                                @foreach (array_slice($branchServices, 0, 2) as $service)
                                    <span class="inline-flex min-h-7 items-center rounded-full bg-staff-indigo-soft px-2.5 text-xs font-semibold text-staff-indigo">{{ $service->name }}</span>
                                @endforeach
                                @if (count($branchServices) > 2)
                                    <span class="inline-flex min-h-7 items-center rounded-full bg-staff-canvas px-2.5 text-xs font-semibold text-staff-muted">+{{ count($branchServices) - 2 }} more</span>
                                @endif
                            </div>
                            <div class="mt-auto pt-5"><a class="staff-secondary-button staff-button-small" href="{{ route('staff.branches.show', [$business->id, $branch->id]) }}">View branch<x-staff.icon class="size-4" name="chevron-right" /></a></div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <aside class="grid gap-5">
            <section class="staff-card p-5.5" aria-labelledby="business-information-heading">
                <h2 id="business-information-heading" class="staff-card-title">Business information</h2>
                <dl class="mt-2 divide-y divide-[#eeeff8]">
                    <div class="py-3"><dt class="staff-information-label">NAME</dt><dd class="mt-1 font-semibold">{{ $business->name }}</dd></div>
                    <div class="py-3"><dt class="staff-information-label">DESCRIPTION</dt><dd class="mt-1 font-semibold {{ $business->description ? '' : 'italic text-staff-muted' }}">{{ $business->description ?: 'Not added.' }}</dd></div>
                </dl>
            </section>
        </aside>
    </div>
@endsection
