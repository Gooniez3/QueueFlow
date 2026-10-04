@extends('layouts.staff')

@section('title', $business->name)
@section('staff-area', 'Businesses')

@section('staff-context')
    <div class="staff-context-chip" aria-label="Current business: {{ $business->name }}">
        <x-staff.icon class="size-4.5 text-staff-indigo" name="business" />
        <span class="max-w-44 truncate">{{ $business->name }}</span>
    </div>
@endsection

@section('staff-content')
    <header class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="staff-page-title mt-0">{{ $business->name }}</h1>
            <p class="staff-page-copy">Business overview</p>
        </div>
        <a class="staff-primary-button w-fit shrink-0" href="{{ route('staff.branches.create', $business->id) }}">
            <x-staff.icon class="size-4.5" name="plus" />
            Create branch
        </a>
    </header>

    <div class="mt-7 grid gap-5 xl:grid-cols-[minmax(0,1fr)_21.25rem] xl:items-start">
        <section class="staff-card overflow-hidden" aria-labelledby="branches-heading">
            <header class="flex min-h-18 flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-5.5">
                <h2 id="branches-heading" class="staff-card-title">
                    Branches
                    <span class="font-staff-sans font-medium text-staff-muted">&middot; {{ count($branches) }}</span>
                </h2>
                <a class="staff-primary-button staff-button-small" href="{{ route('staff.branches.create', $business->id) }}">
                    <x-staff.icon class="size-4" name="plus" />
                    Create branch
                </a>
            </header>

            @if ($branches === [])
                <div class="border-t border-[#eeeff8] px-5 py-10 sm:px-5.5">
                    <h3 class="font-staff-display text-base font-bold">No branches yet</h3>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-staff-muted">Add a branch to organize the services offered at each business location.</p>
                </div>
            @else
                <div>
                    @foreach ($branches as $branch)
                        <article class="staff-branch-row">
                            <span class="staff-branch-icon"><x-staff.icon class="size-5.5" name="branch" /></span>
                            <div class="min-w-0">
                                <h3 class="break-words font-bold text-staff-ink">{{ $branch->name }}</h3>
                                <p class="mt-1 break-words text-[0.8rem] leading-5 text-staff-muted">{{ $branch->address }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                                <span class="staff-amber-button staff-button-small cursor-not-allowed opacity-70" aria-disabled="true" title="Live Queues will be available in a later checkpoint">
                                    <x-staff.icon class="size-4" name="queues" />
                                    Live queues
                                </span>
                                <a class="staff-secondary-button staff-button-small" href="{{ route('staff.branches.show', [$business->id, $branch->id]) }}">
                                    Manage
                                    <x-staff.icon class="size-4" name="chevron-right" />
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <aside class="staff-card p-5.5" aria-labelledby="business-information-heading">
            <h2 id="business-information-heading" class="staff-card-title">Business information</h2>

            <dl class="mt-1">
                <div class="staff-information-row">
                    <dt class="staff-information-label">NAME</dt>
                    <dd class="mt-1 font-semibold">{{ $business->name }}</dd>
                </div>
                <div class="staff-information-row">
                    <dt class="staff-information-label">DESCRIPTION</dt>
                    <dd class="mt-1 font-semibold {{ $business->description ? '' : 'italic text-staff-muted' }}">
                        {{ $business->description ?: 'A description has not been added.' }}
                    </dd>
                </div>
                <div class="staff-information-row">
                    <dt class="staff-information-label">YOUR ROLE</dt>
                    <dd class="mt-2 flex flex-wrap gap-1.5">
                        @forelse ($businessRoles as $role)
                            <span @class([
                                'staff-role-badge',
                                'staff-role-owner' => $role === 'OWNER',
                                'staff-role-manager' => $role === 'MANAGER',
                                'staff-role-staff' => $role === 'STAFF',
                            ])>{{ $role }}</span>
                        @empty
                            <span class="text-sm font-medium text-staff-muted">Role unavailable</span>
                        @endforelse
                    </dd>
                </div>
            </dl>
        </aside>
    </div>
@endsection
