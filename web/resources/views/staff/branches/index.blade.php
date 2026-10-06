@extends('layouts.staff')

@section('title', 'Branches')
@section('staff-area', 'Management')

@section('staff-content')
    <section class="staff-resource-hero" aria-labelledby="branches-heading">
        <div class="staff-resource-hero-content">
            <div class="staff-resource-identity">
                <span class="staff-resource-mark" aria-hidden="true"><x-staff.icon class="size-6" name="branch" /></span>
                <div class="min-w-0">
                    <p class="font-staff-display text-[0.68rem] font-bold tracking-[0.14em] text-staff-amber">MANAGEMENT</p>
                    <h1 id="branches-heading" class="staff-resource-title mt-2">Branches</h1>
                    <p class="staff-resource-subtitle">Locations available through your QueueFlow staff memberships.</p>
                </div>
            </div>
        </div>
        <dl class="staff-hero-metrics">
            <div class="staff-hero-metric"><dt class="staff-hero-metric-label">BRANCHES</dt><dd class="staff-hero-metric-value">{{ $branchCount }}</dd></div>
            <div class="staff-hero-metric"><dt class="staff-hero-metric-label">BUSINESSES</dt><dd class="staff-hero-metric-value">{{ $businessCount }}</dd></div>
            <div class="staff-hero-metric sm:col-span-2 lg:col-span-1"><dt class="staff-hero-metric-label">TIMEZONES</dt><dd class="staff-hero-metric-value">{{ $timezoneCount }}</dd></div>
        </dl>
    </section>

    @if ($branchCount === 0)
        <section class="staff-empty-dashed mt-5" aria-labelledby="branches-empty-heading">
            <span class="mx-auto grid size-11 place-items-center rounded-full bg-staff-indigo-soft text-staff-indigo" aria-hidden="true"><x-staff.icon name="branch" /></span>
            <h2 id="branches-empty-heading" class="mt-3 font-staff-display text-lg font-bold">No accessible branches</h2>
            <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-staff-muted">Your current QueueFlow memberships do not provide access to a branch.</p>
        </section>
    @else
        <div class="mt-5 grid gap-5">
            @foreach ($businesses as $businessContext)
                @if ($businessContext['branches'] !== [])
                    <section class="staff-card overflow-hidden" aria-labelledby="branches-business-{{ $businessContext['business']->id }}">
                        <header class="flex items-center gap-3 border-b border-[#eeeff8] px-5 py-4 sm:px-6">
                            <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-staff-indigo font-staff-display text-xs font-extrabold text-white" aria-hidden="true">{{ mb_strtoupper(mb_substr($businessContext['business']->name, 0, 2)) }}</span>
                            <div class="min-w-0">
                                <p class="staff-information-label">BUSINESS</p>
                                <h2 id="branches-business-{{ $businessContext['business']->id }}" class="mt-1 break-words font-staff-display text-lg font-extrabold">{{ $businessContext['business']->name }}</h2>
                            </div>
                        </header>

                        <div class="grid gap-3 p-4 sm:grid-cols-2 sm:p-5 xl:grid-cols-3">
                            @foreach ($businessContext['branches'] as $branch)
                                <a class="staff-live-branch-link items-start" href="{{ route('staff.branches.show', [$businessContext['business']->id, $branch->id]) }}">
                                    <span class="staff-branch-icon" aria-hidden="true"><x-staff.icon name="branch" /></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block break-words font-staff-display font-bold">{{ $branch->name }}</span>
                                        <span class="mt-1 block break-words text-xs leading-5 text-staff-muted">{{ $branch->address }}</span>
                                        <span class="mt-2 inline-flex items-center gap-1.5 text-xs font-semibold text-staff-indigo"><x-staff.icon class="size-3.5" name="clock" />{{ $branch->timezone }}</span>
                                    </span>
                                    <x-staff.icon class="mt-1 size-4 text-staff-muted" name="chevron-right" />
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif
            @endforeach
        </div>
    @endif
@endsection
