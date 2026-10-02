@extends('layouts.staff')

@section('title', $business->name)

@section('staff-content')
    <x-staff.breadcrumbs
        :back-url="route('staff.businesses.index')"
        back-label="Back to businesses"
        :items="[
            ['label' => 'Businesses', 'url' => route('staff.businesses.index')],
            ['label' => $business->name],
        ]"
    />

    <header class="mt-3 flex flex-col gap-3 border-b border-line/60 pb-5 sm:mt-5 sm:gap-5 sm:pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="staff-eyebrow">BUSINESS</p>
            <h1 class="staff-page-title">{{ $business->name }}</h1>
            <p class="staff-page-copy {{ $business->description ? '' : 'italic' }}">{{ $business->description ?: 'A description has not been added for this business.' }}</p>
        </div>
        <a class="staff-primary-button w-fit shrink-0" href="{{ route('staff.branches.create', $business->id) }}" aria-label="Add branch">
            <span class="text-xl leading-none" aria-hidden="true">+</span>
            <span>Add branch</span>
        </a>
    </header>

    <section class="mt-6 sm:mt-8" aria-labelledby="branches-heading">
        <div class="flex items-baseline gap-3">
            <h2 id="branches-heading" class="staff-section-title">Branches</h2>
            <p class="text-sm text-muted">{{ count($branches) }} {{ count($branches) === 1 ? 'branch' : 'branches' }}</p>
        </div>

        @if ($branches === [])
            <div class="staff-empty-state">
                <h3 class="text-base font-semibold text-ink">No branches yet</h3>
                <p class="mt-2 max-w-xl text-sm leading-6 text-muted">Add a branch to organize the services offered at each business location.</p>
            </div>
        @else
            <div class="staff-resource-list">
                @foreach ($branches as $branch)
                    <a class="staff-resource-row" href="{{ route('staff.branches.show', [$business->id, $branch->id]) }}">
                        <div class="min-w-0">
                            <h3 class="break-words font-editorial text-lg font-semibold tracking-[-0.015em] text-ink sm:text-xl sm:tracking-[-0.02em]">{{ $branch->name }}</h3>
                            <p class="mt-0.5 text-sm leading-5 text-muted sm:mt-1 sm:leading-6">{{ $branch->address }}</p>
                        </div>
                        <span class="grid size-10 shrink-0 place-items-center text-muted" aria-hidden="true">
                            <svg class="size-4" viewBox="0 0 16 16" fill="none"><path d="m6 3 5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
@endsection
