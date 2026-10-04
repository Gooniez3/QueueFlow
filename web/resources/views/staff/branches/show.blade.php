@extends('layouts.staff')

@section('title', $branch->name)
@section('staff-area', 'Businesses')

@section('staff-topbar-breadcrumbs')
    <x-staff.topbar-breadcrumbs :items="[['label' => 'Businesses', 'url' => route('staff.businesses.index')], ['label' => $business->name, 'url' => route('staff.businesses.show', $business->id)], ['label' => $branch->name]]" />
@endsection

@section('staff-context')
    <div class="staff-context-chip" aria-label="Current business: {{ $business->name }}"><x-staff.icon class="size-4.5 text-staff-indigo" name="business" /><span class="max-w-36 truncate">{{ $business->name }}</span></div>
    <div class="staff-context-chip" aria-label="Current branch: {{ $branch->name }}"><x-staff.icon class="size-4.5 text-staff-indigo" name="branch" /><span class="max-w-36 truncate">{{ $branch->name }}</span></div>
@endsection

@section('staff-content')
    @php
        $branchInitials = collect(preg_split('/\s+/', trim($branch->name)))->filter()->take(2)->map(static fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
        $dashboardQueues = $dashboard?->queues ?? [];
        $waitingNow = array_sum(array_map(static fn ($queue): int => $queue->counts->waiting, $dashboardQueues));
        $openQueueCount = count(array_filter($dashboardQueues, static fn ($queue): bool => $queue->status === 'OPEN'));
        $calledNow = array_sum(array_map(static fn ($queue): int => $queue->counts->called, $dashboardQueues));
        $servingNow = array_sum(array_map(static fn ($queue): int => $queue->counts->serving, $dashboardQueues));
    @endphp

    <div class="mb-5 lg:hidden">
        <x-staff.breadcrumbs :back-url="route('staff.businesses.show', $business->id)" :back-label="'Back to '.$business->name" :items="[['label' => 'Businesses', 'url' => route('staff.businesses.index')], ['label' => $business->name, 'url' => route('staff.businesses.show', $business->id)], ['label' => $branch->name]]" />
    </div>

    <section class="staff-resource-hero" aria-labelledby="branch-heading">
        <div class="staff-resource-hero-content">
            <div class="staff-resource-identity">
                <span class="staff-resource-mark" aria-hidden="true">{{ $branchInitials }}</span>
                <div class="min-w-0"><p class="font-staff-display text-[0.68rem] font-bold tracking-[0.14em] text-staff-amber">BRANCH</p><h1 id="branch-heading" class="staff-resource-title mt-2">{{ $branch->name }}</h1><p class="staff-resource-subtitle">Branch of {{ $business->name }} &middot; {{ $branch->address }}</p></div>
            </div>
            <div class="staff-resource-actions">
                <a class="staff-hero-secondary-button" href="{{ route('staff.branches.edit', [$business->id, $branch->id]) }}"><x-staff.icon class="size-4" name="edit" />Edit branch</a>
                <a class="staff-amber-button" href="{{ route('staff.services.create', [$business->id, $branch->id]) }}"><x-staff.icon class="size-4" name="plus" />Create service</a>
                <a class="staff-hero-secondary-button" href="{{ route('staff.live-queues.index', [$business->id, $branch->id]) }}"><x-staff.icon class="size-4" name="queues" />Open live queues</a>
            </div>
        </div>
        <dl class="staff-hero-metrics lg:grid-cols-4">
            <div class="staff-hero-metric"><dt class="staff-hero-metric-label">SERVICES</dt><dd class="staff-hero-metric-value">{{ count($services) }}</dd></div>
            <div class="staff-hero-metric"><dt class="staff-hero-metric-label">QUEUES TODAY</dt><dd class="staff-hero-metric-value">{{ $dashboardUnavailable ? '—' : count($dashboardQueues) }}</dd></div>
            <div class="staff-hero-metric"><dt class="staff-hero-metric-label">WAITING NOW</dt><dd class="staff-hero-metric-value">{{ $dashboardUnavailable ? '—' : $waitingNow }}</dd></div>
            <div class="staff-hero-metric"><dt class="staff-hero-metric-label">OPEN QUEUES</dt><dd class="staff-hero-metric-value">{{ $dashboardUnavailable ? '—' : $openQueueCount }}</dd></div>
        </dl>
    </section>

    <div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1fr)_21.25rem] xl:items-start">
        <section aria-labelledby="services-heading">
            <div class="mb-4"><h2 id="services-heading" class="staff-section-title">Services</h2><p class="mt-1 text-sm text-staff-muted">Services available at this branch.</p></div>
            @if ($services === [])
                <div class="staff-empty-dashed"><h3 class="font-staff-display text-base font-bold">No services yet</h3><p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-staff-muted">Create the first service customers can receive at this branch.</p></div>
            @else
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach ($services as $service)
                        @php($serviceQueueContext = $serviceQueues[$service->id])
                        @php($serviceQueue = $serviceQueueContext['queue'])
                        <article class="staff-card flex min-h-52 flex-col p-5.5">
                            <div class="flex items-start gap-3.5"><span class="grid size-11 shrink-0 place-items-center rounded-xl bg-staff-indigo-soft text-staff-indigo" aria-hidden="true"><x-staff.icon name="service" /></span><div class="min-w-0"><h3 class="break-words font-staff-display text-base font-extrabold">{{ $service->name }}</h3><p class="mt-1 break-words text-[0.8rem] leading-5 text-staff-muted {{ $service->description ? '' : 'italic' }}">{{ $service->description ?: 'No description added.' }}</p></div></div>
                            <div class="mt-4 flex flex-wrap items-center gap-2"><span class="rounded-full bg-staff-canvas px-2.5 py-1 text-xs font-semibold text-staff-muted">{{ $service->durationMinutes }} min</span><span class="staff-status-badge {{ $service->active ? 'border-staff-success/20 bg-staff-success-soft text-staff-success' : 'border-staff-line bg-[#e8e9f3] text-[#4a4d68]' }}"><span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>{{ $service->active ? 'Active' : 'Inactive' }}</span></div>
                            @if ($serviceQueue)
                                <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-[#eeeff8] pt-3">
                                    <span class="inline-flex min-h-7 items-center rounded-lg bg-staff-ink px-2.5 font-staff-display text-xs font-extrabold text-staff-amber">{{ $serviceQueue->ticketPrefix }}</span>
                                    <span @class(['staff-status-badge', 'border-staff-success/20 bg-staff-success-soft text-staff-success' => $serviceQueue->status === 'OPEN', 'border-staff-warning/20 bg-staff-warning-soft text-staff-warning' => $serviceQueue->status === 'PAUSED', 'border-staff-line bg-[#e8e9f3] text-[#4a4d68]' => $serviceQueue->status === 'CLOSED'])>{{ ucfirst(strtolower($serviceQueue->status)) }}</span>
                                    <span class="text-xs font-semibold text-staff-muted">{{ $serviceQueue->counts->waiting }} waiting</span>
                                </div>
                            @elseif ($serviceQueueContext['ambiguous'])
                                <p class="mt-3 border-t border-[#eeeff8] pt-3 text-xs text-staff-warning">Multiple service queues returned; no queue was selected.</p>
                            @endif
                            <div class="mt-auto pt-5"><a class="staff-secondary-button staff-button-small" href="{{ route('staff.services.show', [$business->id, $branch->id, $service->id]) }}">View service<x-staff.icon class="size-4" name="chevron-right" /></a></div>
                        </article>
                    @endforeach
                </div>
            @endif
            @if ($services === [])
                <a class="staff-empty-dashed mt-4 flex min-h-24 flex-col items-center justify-center text-staff-indigo" href="{{ route('staff.services.create', [$business->id, $branch->id]) }}"><span class="grid size-10 place-items-center rounded-full bg-staff-indigo-soft" aria-hidden="true"><x-staff.icon name="plus" /></span><span class="mt-2 font-bold">Create service</span></a>
            @endif
        </section>

        <aside class="grid gap-5">
            <section class="staff-card overflow-hidden" aria-labelledby="queues-heading">
                <div class="bg-staff-ink p-5.5 text-white">
                    <p class="staff-information-label text-staff-sidebar-muted">TODAY'S QUEUES</p>
                    <h2 id="queues-heading" class="mt-2 font-staff-display text-xl font-extrabold">{{ $dashboardUnavailable ? 'Queue data unavailable' : ($dashboardQueues === [] ? 'No queues today' : $waitingNow.' waiting now') }}</h2>
                    @if (! $dashboardUnavailable && $dashboardQueues !== [])
                        <p class="mt-2 text-sm text-staff-sidebar-muted">{{ $calledNow }} called &middot; {{ $servingNow }} serving</p>
                    @else
                        <p class="mt-2 text-sm leading-6 text-staff-sidebar-muted">{{ $dashboardUnavailable ? 'Queue information could not be loaded. Management details remain available.' : 'No queue has been opened for this branch today.' }}</p>
                    @endif
                    <a class="mt-4 inline-flex min-h-11 items-center gap-2 rounded-xl bg-white/10 px-4 text-sm font-bold text-white transition-colors hover:bg-white/20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white" href="{{ route('staff.live-queues.index', [$business->id, $branch->id]) }}"><x-staff.icon class="size-4" name="queues" />Open live queues</a>
                </div>
                @if ($dashboardQueues !== [])
                    <div class="divide-y divide-[#eeeff8]">
                        @foreach ($dashboardQueues as $queue)
                            <article class="p-4.5">
                                <div class="flex items-start gap-3">
                                    <span class="inline-flex min-h-8 min-w-12 items-center justify-center rounded-lg bg-staff-ink px-2 font-staff-display text-sm font-extrabold text-staff-amber">{{ $queue->ticketPrefix }}</span>
                                    <div class="min-w-0 flex-1"><h3 class="break-words font-bold">{{ $queue->name }}</h3><p class="mt-1 text-xs text-staff-muted">{{ $queue->service?->name ?? 'Shared branch queue' }} &middot; {{ $queue->counts->waiting }} waiting</p></div>
                                    <span @class(['staff-status-badge', 'border-staff-success/20 bg-staff-success-soft text-staff-success' => $queue->status === 'OPEN', 'border-staff-warning/20 bg-staff-warning-soft text-staff-warning' => $queue->status === 'PAUSED', 'border-staff-line bg-[#e8e9f3] text-[#4a4d68]' => $queue->status === 'CLOSED'])>{{ $queue->status }}</span>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
            <section class="staff-card p-5.5" aria-labelledby="branch-information-heading">
                <h2 id="branch-information-heading" class="staff-card-title">Branch information</h2>
                <dl class="mt-2 divide-y divide-[#eeeff8]">
                    <div class="py-3"><dt class="staff-information-label">ADDRESS</dt><dd class="mt-1 break-words font-semibold">{{ $branch->address }}</dd></div>
                    <div class="py-3"><dt class="staff-information-label">TIMEZONE</dt><dd class="mt-1 break-words font-semibold">{{ $branch->timezone }}</dd></div>
                    <div class="py-3"><dt class="staff-information-label">BUSINESS</dt><dd class="mt-1 break-words font-semibold"><a class="hover:text-staff-indigo" href="{{ route('staff.businesses.show', $business->id) }}">{{ $business->name }}</a></dd></div>
                </dl>
            </section>
        </aside>
    </div>
@endsection
