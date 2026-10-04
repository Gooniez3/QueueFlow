@extends('layouts.staff')

@section('title', 'Live queues')
@section('staff-area', 'Operations')

@section('staff-content')
    <header class="flex flex-col gap-4 border-b border-staff-line pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="staff-eyebrow">OPERATIONS</p>
            <h1 class="staff-page-title">Live queues</h1>
            <p class="staff-page-copy">Choose a branch to open its live queue workspace.</p>
        </div>
    </header>

    @if ($businesses === [])
        <section class="staff-empty-state" aria-labelledby="no-accessible-branches-heading">
            <span class="grid size-11 place-items-center rounded-full bg-staff-indigo-soft text-staff-indigo" aria-hidden="true">
                <x-staff.icon name="branch" />
            </span>
            <h2 id="no-accessible-branches-heading" class="staff-section-title mt-4">No accessible branches</h2>
            <p class="mt-2 max-w-xl text-sm leading-6 text-staff-muted">Your current QueueFlow memberships do not provide access to a branch with a live queue workspace.</p>
        </section>
    @else
        <div class="mt-6 grid gap-5">
            @foreach ($businesses as $businessContext)
                <section class="staff-card overflow-hidden" aria-labelledby="business-{{ $businessContext['business']->id }}-heading">
                    <header class="flex items-center gap-3 border-b border-[#eeeff8] px-5 py-4 sm:px-6">
                        <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-staff-indigo font-staff-display text-xs font-extrabold text-white" aria-hidden="true">
                            {{ mb_strtoupper(mb_substr($businessContext['business']->name, 0, 2)) }}
                        </span>
                        <div class="min-w-0">
                            <p class="staff-information-label">BUSINESS</p>
                            <h2 id="business-{{ $businessContext['business']->id }}-heading" class="mt-1 break-words font-staff-display text-lg font-extrabold">{{ $businessContext['business']->name }}</h2>
                        </div>
                    </header>

                    <div class="grid gap-3 p-4 sm:grid-cols-2 sm:p-5 xl:grid-cols-3">
                        @foreach ($businessContext['branches'] as $branch)
                            <a class="staff-live-branch-link" href="{{ route('staff.live-queues.index', [$businessContext['business']->id, $branch->id]) }}">
                                <span class="staff-branch-icon" aria-hidden="true"><x-staff.icon name="branch" /></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block break-words font-staff-display font-bold">{{ $branch->name }}</span>
                                    <span class="mt-1 block break-words text-xs leading-5 text-staff-muted">{{ $branch->address }}</span>
                                </span>
                                <x-staff.icon class="size-4 text-staff-muted" name="chevron-right" />
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @endif
@endsection
