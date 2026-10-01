@extends('layouts.staff')

@section('title', 'Staff workspace')

@section('staff-content')
    <section aria-labelledby="workspace-heading">
        <header class="border-b border-line/60 pb-6">
            <p class="staff-eyebrow">STAFF WORKSPACE</p>
            <h1 id="workspace-heading" class="staff-page-title">Staff workspace</h1>
            <p class="staff-page-copy">Manage the businesses, branches and services connected to your QueueFlow account.</p>
        </header>

        <a class="mt-8 grid min-w-0 grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-5 rounded-xl border border-line bg-surface p-5 text-ink transition-colors hover:border-brand/30 hover:bg-brand/[0.02] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand sm:p-7" href="{{ route('staff.businesses.index') }}">
            <span class="grid size-12 place-items-center rounded-xl bg-brand/7 text-brand" aria-hidden="true">
                <svg class="size-6" viewBox="0 0 24 24" fill="none">
                    <path d="M5 20V8.5A1.5 1.5 0 0 1 6.5 7H10v13M10 4.5A1.5 1.5 0 0 1 11.5 3h6A1.5 1.5 0 0 1 19 4.5V20M3 20h18M13 7h3M13 11h3M13 15h3M7 11h.01M7 15h.01" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
            <span class="min-w-0">
                <span class="block font-editorial text-xl font-semibold tracking-[-0.02em]">Manage businesses</span>
                <span class="mt-1 block text-sm leading-6 text-muted">View and create businesses, then add branches and services to each location.</span>
            </span>
            <span class="grid size-10 shrink-0 place-items-center rounded-full border border-line text-muted" aria-hidden="true">
                <svg class="size-5" viewBox="0 0 20 20" fill="none">
                    <path d="M4 10h11m-4.5-4.5L15 10l-4.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
        </a>

        <ul class="mt-8 grid gap-3 md:grid-cols-3" aria-label="Business management structure">
            <li class="flex min-w-0 items-center gap-3 rounded-xl border border-line bg-surface px-4 py-3.5">
                <span class="grid size-9 shrink-0 place-items-center rounded-lg border border-line text-brand" aria-hidden="true">
                    <svg class="size-4.5" viewBox="0 0 24 24" fill="none"><path d="M5 20V8.5A1.5 1.5 0 0 1 6.5 7H10v13M10 4.5A1.5 1.5 0 0 1 11.5 3h6A1.5 1.5 0 0 1 19 4.5V20M3 20h18M13 7h3M13 11h3M13 15h3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" /></svg>
                </span>
                <span class="min-w-0"><span class="block text-sm font-semibold">Businesses</span><span class="block text-xs text-muted">Your organizations</span></span>
            </li>
            <li class="flex min-w-0 items-center gap-3 rounded-xl border border-line bg-surface px-4 py-3.5">
                <span class="grid size-9 shrink-0 place-items-center rounded-lg border border-line text-brand" aria-hidden="true">
                    <svg class="size-4.5" viewBox="0 0 24 24" fill="none"><path d="M19 10c0 5-7 10-7 10S5 15 5 10a7 7 0 1 1 14 0Z" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="10" r="2.25" stroke="currentColor" stroke-width="1.6"/></svg>
                </span>
                <span class="min-w-0"><span class="block text-sm font-semibold">Branches</span><span class="block text-xs text-muted">Physical locations</span></span>
            </li>
            <li class="flex min-w-0 items-center gap-3 rounded-xl border border-line bg-surface px-4 py-3.5">
                <span class="grid size-9 shrink-0 place-items-center rounded-lg border border-line text-brand" aria-hidden="true">
                    <svg class="size-4.5" viewBox="0 0 24 24" fill="none"><path d="m14.5 6.5 3-3 3 3-3 3M13 8l-8.5 8.5a2.12 2.12 0 0 0 3 3L16 11M12.5 4.5a5.5 5.5 0 0 0 7 7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" /></svg>
                </span>
                <span class="min-w-0"><span class="block text-sm font-semibold">Services</span><span class="block text-xs text-muted">What you offer</span></span>
            </li>
        </ul>
    </section>
@endsection
