@extends('layouts.staff')

@section('title', $service->name)
@section('staff-area', 'Businesses')

@section('staff-topbar-breadcrumbs')
    <x-staff.topbar-breadcrumbs :items="[['label' => 'Businesses', 'url' => route('staff.businesses.index')], ['label' => $business->name, 'url' => route('staff.businesses.show', $business->id)], ['label' => $branch->name, 'url' => route('staff.branches.show', [$business->id, $branch->id])], ['label' => $service->name]]" />
@endsection

@section('staff-context')
    <div class="staff-context-chip" aria-label="Current business: {{ $business->name }}"><x-staff.icon class="size-4.5 text-staff-indigo" name="business" /><span class="max-w-36 truncate">{{ $business->name }}</span></div>
    <div class="staff-context-chip" aria-label="Current branch: {{ $branch->name }}"><x-staff.icon class="size-4.5 text-staff-indigo" name="branch" /><span class="max-w-36 truncate">{{ $branch->name }}</span></div>
@endsection

@section('staff-content')
    @php
        $serviceInitials = collect(preg_split('/\s+/', trim($service->name)))->filter()->take(2)->map(static fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
    @endphp

    <div class="mb-5 lg:hidden">
        <x-staff.breadcrumbs :back-url="route('staff.branches.show', [$business->id, $branch->id])" :back-label="'Back to '.$branch->name" :items="[['label' => 'Businesses', 'url' => route('staff.businesses.index')], ['label' => $business->name, 'url' => route('staff.businesses.show', $business->id)], ['label' => $branch->name, 'url' => route('staff.branches.show', [$business->id, $branch->id])], ['label' => $service->name]]" />
    </div>

    <section class="staff-resource-hero" aria-labelledby="service-heading">
        <div class="staff-resource-hero-content">
            <div class="staff-resource-identity">
                <span class="staff-resource-mark" aria-hidden="true">{{ $serviceInitials }}</span>
                <div class="min-w-0">
                    <p class="font-staff-display text-[0.68rem] font-bold tracking-[0.14em] text-staff-amber">SERVICE</p><h1 id="service-heading" class="staff-resource-title mt-2">{{ $service->name }}</h1><p class="staff-resource-subtitle">Service at {{ $branch->name }} &middot; {{ $business->name }}</p>
                    <span class="mt-3 inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[0.7rem] font-bold {{ $service->active ? 'bg-emerald-400/20 text-emerald-200' : 'bg-white/15 text-white' }}"><span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>{{ $service->active ? 'ACTIVE' : 'INACTIVE' }}</span>
                </div>
            </div>
            <div class="staff-resource-actions">
                <a class="staff-hero-secondary-button" href="{{ route('staff.services.edit', [$business->id, $branch->id, $service->id]) }}"><x-staff.icon class="size-4" name="edit" />Edit service</a>
                @if ($serviceQueue)
                    <a class="staff-amber-button" href="{{ route('staff.live-queues.index', ['businessId' => $business->id, 'branchId' => $branch->id, 'queue' => $serviceQueue->queueId]) }}"><x-staff.icon class="size-4" name="queues" />Open in live queues</a>
                @else
                    <span class="staff-amber-button cursor-not-allowed opacity-60" aria-disabled="true"><x-staff.icon class="size-4" name="queues" />Open in live queues</span>
                @endif
            </div>
        </div>
        <dl class="staff-hero-metrics lg:grid-cols-4">
            <div class="staff-hero-metric"><dt class="staff-hero-metric-label">DURATION</dt><dd class="staff-hero-metric-value">{{ $service->durationMinutes }} min</dd></div>
            <div class="staff-hero-metric"><dt class="staff-hero-metric-label">QUEUE PREFIX</dt><dd class="staff-hero-metric-value">{{ $serviceQueue?->ticketPrefix ?? '—' }}</dd></div>
            <div class="staff-hero-metric"><dt class="staff-hero-metric-label">WAITING NOW</dt><dd class="staff-hero-metric-value">{{ $dashboardUnavailable ? '—' : ($serviceQueue?->counts->waiting ?? 0) }}</dd></div>
            <div class="staff-hero-metric"><dt class="staff-hero-metric-label">QUEUE STATUS</dt><dd class="staff-hero-metric-value text-base sm:text-lg">{{ $serviceQueue ? ucfirst(strtolower($serviceQueue->status)) : ($dashboardUnavailable ? 'Unavailable' : 'No queue') }}</dd></div>
        </dl>
    </section>

    <div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1fr)_21.25rem] xl:items-start">
        <div class="grid gap-5">
            <section class="staff-card p-5.5 sm:p-6" aria-labelledby="description-heading"><h2 id="description-heading" class="staff-information-label">DESCRIPTION</h2><p class="mt-2 text-base leading-7 {{ $service->description ? '' : 'italic text-staff-muted' }}">{{ $service->description ?: 'A description has not been added for this service.' }}</p></section>
            @if ($serviceQueue)
                <section class="staff-card p-5.5 sm:p-6" aria-labelledby="queue-heading">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="flex items-center gap-3.5"><span class="inline-flex min-h-10 min-w-14 items-center justify-center rounded-xl bg-staff-ink px-3 font-staff-display text-base font-extrabold text-staff-amber">{{ $serviceQueue->ticketPrefix }}</span><div><h2 id="queue-heading" class="staff-section-title">{{ $serviceQueue->name }}</h2><p class="mt-1 text-xs text-staff-muted">Prefix {{ $serviceQueue->ticketPrefix }} &middot; {{ $dashboard->businessDate }}</p></div></div>
                        <span @class(['staff-status-badge', 'border-staff-success/20 bg-staff-success-soft text-staff-success' => $serviceQueue->status === 'OPEN', 'border-staff-warning/20 bg-staff-warning-soft text-staff-warning' => $serviceQueue->status === 'PAUSED', 'border-staff-line bg-[#e8e9f3] text-[#4a4d68]' => $serviceQueue->status === 'CLOSED'])>{{ $serviceQueue->status }}</span>
                    </div>
                    <dl class="mt-5 grid gap-3 sm:grid-cols-3">
                        <div class="staff-card-muted-block"><dt class="staff-information-label">WAITING</dt><dd class="mt-1 font-staff-display text-3xl font-extrabold text-staff-indigo">{{ $serviceQueue->counts->waiting }}</dd></div>
                        <div class="staff-card-muted-block"><dt class="staff-information-label">CALLED</dt><dd class="mt-1 font-staff-display text-3xl font-extrabold text-staff-warning">{{ $serviceQueue->counts->called }}</dd></div>
                        <div class="staff-card-muted-block"><dt class="staff-information-label">SERVING</dt><dd class="mt-1 font-staff-display text-3xl font-extrabold text-staff-success">{{ $serviceQueue->counts->serving }}</dd></div>
                    </dl>
                    @if ($serviceQueue->serving || $serviceQueue->called)
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            @if ($serviceQueue->serving)
                                <div class="rounded-2xl bg-staff-ink p-4.5 text-white"><p class="staff-information-label text-staff-sidebar-muted">NOW SERVING</p><p class="mt-1 font-staff-display text-3xl font-extrabold text-staff-amber">{{ $serviceQueue->serving->ticketNumber }}</p></div>
                            @endif
                            @if ($serviceQueue->called)
                                <div class="rounded-2xl bg-staff-warning-soft p-4.5"><p class="staff-information-label text-staff-warning">CALLED</p><p class="mt-1 font-staff-display text-3xl font-extrabold">{{ $serviceQueue->called->ticketNumber }}</p></div>
                            @endif
                        </div>
                    @endif
                    <a class="staff-secondary-button staff-button-small mt-4" href="{{ route('staff.live-queues.index', ['businessId' => $business->id, 'branchId' => $branch->id, 'queue' => $serviceQueue->queueId]) }}"><x-staff.icon class="size-4" name="queues" />Open in live queues</a>
                </section>
            @else
                <section class="staff-card p-5.5 sm:p-6" aria-labelledby="queue-heading"><div class="flex items-start gap-3.5"><span class="grid size-11 shrink-0 place-items-center rounded-xl bg-staff-indigo-soft text-staff-indigo" aria-hidden="true"><x-staff.icon name="queues" /></span><div><h2 id="queue-heading" class="staff-section-title">{{ $dashboardUnavailable ? 'Queue data unavailable' : ($serviceQueueAmbiguous ? 'Multiple queues need review' : 'No live queue today') }}</h2><p class="mt-2 max-w-2xl text-sm leading-6 text-staff-muted">{{ $dashboardUnavailable ? 'Queue information could not be loaded. Service details remain available.' : ($serviceQueueAmbiguous ? 'More than one service-specific queue was returned, so QueueFlow did not choose one automatically.' : 'No service-specific queue is open for this service today.') }}</p></div></div></section>
            @endif
            <section class="staff-card p-5.5 sm:p-6" aria-labelledby="service-details-heading">
                <h2 id="service-details-heading" class="staff-card-title">Service details</h2>
                <dl class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="staff-card-muted-block"><dt class="staff-information-label">DURATION</dt><dd class="mt-1 font-bold">{{ $service->durationMinutes }} minutes</dd></div>
                    <div class="staff-card-muted-block"><dt class="staff-information-label">STATUS</dt><dd class="mt-1 font-bold">{{ $service->active ? 'Active' : 'Inactive' }}</dd></div>
                    <div class="staff-card-muted-block"><dt class="staff-information-label">QUEUE PREFIX</dt><dd class="mt-1 font-bold">{{ $serviceQueue?->ticketPrefix ?? 'Not available' }}</dd></div>
                    <div class="staff-card-muted-block"><dt class="staff-information-label">BRANCH</dt><dd class="mt-1 break-words font-bold">{{ $branch->name }}</dd></div>
                    <div class="staff-card-muted-block"><dt class="staff-information-label">BUSINESS</dt><dd class="mt-1 break-words font-bold">{{ $business->name }}</dd></div>
                    <div class="staff-card-muted-block"><dt class="staff-information-label">QUEUE</dt><dd class="mt-1 break-words font-bold">{{ $serviceQueue?->name ?? 'No queue today' }}</dd></div>
                </dl>
            </section>
        </div>
        <aside class="grid gap-5">
            <section class="staff-card p-5.5" aria-labelledby="service-branch-heading"><p class="staff-information-label">BRANCH</p><div class="my-3 flex items-center gap-3"><span class="grid size-11 shrink-0 place-items-center rounded-full bg-staff-indigo-soft text-staff-indigo" aria-hidden="true"><x-staff.icon name="branch" /></span><div class="min-w-0"><h2 id="service-branch-heading" class="break-words font-bold">{{ $branch->name }}</h2><p class="mt-1 break-words text-xs text-staff-muted">{{ $branch->address }}</p></div></div><a class="staff-secondary-button staff-button-small" href="{{ route('staff.branches.show', [$business->id, $branch->id]) }}">View branch<x-staff.icon class="size-4" name="chevron-right" /></a></section>
            <section class="staff-card p-5.5" aria-labelledby="service-business-heading"><p class="staff-information-label">BUSINESS</p><div class="my-3 flex items-center gap-3"><span class="grid size-11 shrink-0 place-items-center rounded-xl bg-staff-indigo font-staff-display text-xs font-extrabold text-white" aria-hidden="true">{{ mb_strtoupper(mb_substr($business->name, 0, 2)) }}</span><h2 id="service-business-heading" class="break-words font-bold">{{ $business->name }}</h2></div><a class="staff-secondary-button staff-button-small" href="{{ route('staff.businesses.show', $business->id) }}">View business<x-staff.icon class="size-4" name="chevron-right" /></a></section>
        </aside>
    </div>
@endsection
