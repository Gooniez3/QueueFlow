@extends('layouts.staff')

@section('title', $service->name)

@section('staff-content')
    <x-staff.breadcrumbs
        :back-url="route('staff.branches.show', [$business->id, $branch->id])"
        :back-label="'Back to '.$branch->name"
        :items="[
            ['label' => 'Businesses', 'url' => route('staff.businesses.index')],
            ['label' => $business->name, 'url' => route('staff.businesses.show', $business->id)],
            ['label' => $branch->name, 'url' => route('staff.branches.show', [$business->id, $branch->id])],
            ['label' => $service->name],
        ]"
    />

    <article class="mt-3 max-w-5xl sm:mt-5" aria-labelledby="service-heading">
        <header class="border-b border-line/60 pb-5 sm:pb-6">
            <div>
                <p class="staff-eyebrow">SERVICE</p>
                <div class="mt-2 flex flex-wrap items-center gap-2.5 sm:mt-2.5 sm:gap-3">
                    <h1 id="service-heading" class="break-words font-editorial text-2xl leading-[1.1] tracking-[-0.03em] text-ink sm:text-4xl sm:leading-[1.08] sm:tracking-[-0.035em]">{{ $service->name }}</h1>
                    <span class="staff-status-badge {{ $service->active ? 'border-brand/20 bg-brand/5 text-brand' : 'border-line bg-surface text-muted' }}">
                        <span class="size-1.5 rounded-full {{ $service->active ? 'bg-brand' : 'bg-muted' }}" aria-hidden="true"></span>
                        {{ $service->active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
        </header>

        <p class="border-b border-line/60 py-5 text-sm leading-6 text-ink sm:py-7 sm:text-base sm:leading-7 {{ $service->description ? '' : 'italic text-muted' }}">{{ $service->description ?: 'A description has not been added for this service.' }}</p>

        <section class="mt-5 sm:mt-7" aria-labelledby="service-details-heading">
            <h2 id="service-details-heading" class="staff-eyebrow">DETAILS</h2>
            <dl class="mt-3 grid max-w-3xl overflow-hidden rounded-xl border border-line bg-surface sm:mt-4 sm:grid-cols-2">
                <div class="p-4 sm:p-6">
                    <dt class="text-xs font-semibold tracking-[0.14em] text-muted">DURATION</dt>
                    <dd class="mt-2 flex items-center gap-2 text-base font-semibold text-ink">
                        <svg class="size-5 text-brand" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="6.5" stroke="currentColor" stroke-width="1.4" /><path d="M10 6.5V10l2.5 1.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" /></svg>
                        {{ $service->durationMinutes }} minutes
                    </dd>
                </div>
                <div class="border-t border-line p-4 sm:border-t-0 sm:border-l sm:p-6">
                    <dt class="text-xs font-semibold tracking-[0.14em] text-muted">BRANCH</dt>
                    <dd class="mt-2 flex min-w-0 items-center gap-2 break-words text-base font-semibold text-ink">
                        <svg class="size-5 shrink-0 text-brand" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M15 8.5c0 3.7-5 7.5-5 7.5s-5-3.8-5-7.5a5 5 0 1 1 10 0Z" stroke="currentColor" stroke-width="1.4" /><circle cx="10" cy="8.5" r="1.6" stroke="currentColor" stroke-width="1.4" /></svg>
                        {{ $branch->name }}
                    </dd>
                </div>
            </dl>
        </section>
    </article>
@endsection
