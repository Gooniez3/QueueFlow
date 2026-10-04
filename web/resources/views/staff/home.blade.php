@extends('layouts.staff')

@section('title', 'Overview')
@section('staff-area', 'Workspace')

@section('staff-content')
    <section aria-labelledby="workspace-heading">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="staff-eyebrow">STAFF WORKSPACE</p>
                <h1 id="workspace-heading" class="staff-page-title">Overview</h1>
                <p class="staff-page-copy">Staff workspace for managing the businesses, branches and services connected to your QueueFlow account.</p>
            </div>
        </header>

        <div class="mt-6 grid max-w-4xl gap-5 sm:mt-7">
            <a class="staff-card group grid min-w-0 grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-4 p-5 text-staff-ink transition-colors hover:border-staff-indigo/35 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-staff-indigo sm:p-6" href="{{ route('staff.businesses.index') }}">
                <span class="grid size-11 place-items-center rounded-xl bg-staff-indigo-soft text-staff-indigo" aria-hidden="true">
                    <x-staff.icon class="size-5.5" name="business" />
                </span>
                <span class="min-w-0">
                    <span class="block font-staff-display text-base font-bold">Manage businesses</span>
                    <span class="mt-1 block text-sm leading-6 text-staff-muted">View businesses you can access, then continue to their branches and services.</span>
                </span>
                <span class="grid size-10 shrink-0 place-items-center rounded-xl border border-staff-line text-staff-muted transition-colors group-hover:border-staff-indigo/30 group-hover:text-staff-indigo" aria-hidden="true">
                    <x-staff.icon class="size-4.5" name="chevron-right" />
                </span>
            </a>

            <p class="text-sm leading-6 text-staff-muted">Your access remains scoped to the memberships provided by QueueFlow.</p>
        </div>
    </section>
@endsection
