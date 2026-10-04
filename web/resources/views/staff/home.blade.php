@extends('layouts.staff')

@section('title', 'Overview')
@section('staff-area', 'Workspace')

@section('staff-content')
    @php
        $user = $authContext['user'];
        $memberships = $authContext['memberships'];
        $businessCount = count(array_unique(array_map(static fn ($membership): int => $membership->businessId, $memberships)));
        $branchScopeCount = count(array_filter($memberships, static fn ($membership): bool => $membership->branchId !== null));
    @endphp

    <section class="staff-resource-hero" aria-labelledby="workspace-heading">
        <div class="staff-resource-hero-content">
            <div class="staff-resource-identity">
                <span class="staff-resource-mark" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->firstName, 0, 1).mb_substr($user->lastName, 0, 1)) }}</span>
                <div class="min-w-0">
                    <p class="font-staff-display text-[0.68rem] font-bold tracking-[0.14em] text-staff-amber">STAFF WORKSPACE</p>
                    <h1 id="workspace-heading" class="staff-resource-title mt-2">Welcome back, {{ $user->firstName }}.</h1>
                    <p class="staff-resource-subtitle">Manage the QueueFlow organizations and locations available to your staff account.</p>
                </div>
            </div>
            <a class="staff-amber-button w-fit" href="{{ route('staff.businesses.index') }}">
                <x-staff.icon class="size-4.5" name="business" />
                Manage businesses
            </a>
        </div>

        <dl class="staff-hero-metrics">
            <div class="staff-hero-metric">
                <dt class="staff-hero-metric-label">BUSINESSES</dt>
                <dd class="staff-hero-metric-value">{{ $businessCount }}</dd>
            </div>
            <div class="staff-hero-metric">
                <dt class="staff-hero-metric-label">MEMBERSHIPS</dt>
                <dd class="staff-hero-metric-value">{{ count($memberships) }}</dd>
            </div>
            <div class="staff-hero-metric sm:col-span-2 lg:col-span-1">
                <dt class="staff-hero-metric-label">BRANCH-SCOPED ACCESS</dt>
                <dd class="staff-hero-metric-value">{{ $branchScopeCount }}</dd>
            </div>
        </dl>
    </section>

    <div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1.45fr)_minmax(18rem,0.55fr)]">
        <section class="staff-card p-5.5 sm:p-6" aria-labelledby="management-heading">
            <div class="staff-section-header">
                <div>
                    <p class="staff-eyebrow">MANAGEMENT</p>
                    <h2 id="management-heading" class="staff-section-title mt-2">Your QueueFlow workspace</h2>
                </div>
                <span class="grid size-11 place-items-center rounded-xl bg-staff-indigo-soft text-staff-indigo" aria-hidden="true">
                    <x-staff.icon class="size-5.5" name="business" />
                </span>
            </div>
            <p class="mt-4 max-w-2xl text-sm leading-6 text-staff-muted">View every business covered by your Spring-authorized memberships, then continue to its real branches and services.</p>
            <a class="staff-primary-button mt-5" href="{{ route('staff.businesses.index') }}">
                Open businesses
                <x-staff.icon class="size-4" name="chevron-right" />
            </a>
        </section>

        <aside class="staff-card p-5.5 sm:p-6" aria-labelledby="access-heading">
            <p class="staff-eyebrow">ACCOUNT</p>
            <h2 id="access-heading" class="staff-section-title mt-2">Access context</h2>
            <dl class="mt-4 divide-y divide-[#eeeff8]">
                <div class="py-3 first:pt-0">
                    <dt class="staff-information-label">SIGNED IN AS</dt>
                    <dd class="mt-1 break-words font-semibold">{{ $user->firstName }} {{ $user->lastName }}</dd>
                </div>
                <div class="py-3">
                    <dt class="staff-information-label">EMAIL</dt>
                    <dd class="mt-1 break-words font-semibold">{{ $user->email }}</dd>
                </div>
            </dl>
            <p class="mt-2 text-xs leading-5 text-staff-muted">Spring Boot remains the authorization authority for every management operation.</p>
        </aside>
    </div>
@endsection
