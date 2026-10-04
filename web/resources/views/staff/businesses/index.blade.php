@extends('layouts.staff')

@section('title', 'Businesses')
@section('staff-area', 'Management')

@section('staff-content')
    <section class="staff-resource-hero" aria-labelledby="businesses-heading">
        <div class="staff-resource-hero-content">
            <div class="staff-resource-identity">
                <span class="staff-resource-mark" aria-hidden="true">QF</span>
                <div class="min-w-0">
                    <p class="font-staff-display text-[0.68rem] font-bold tracking-[0.14em] text-staff-amber">MANAGEMENT</p>
                    <h1 id="businesses-heading" class="staff-resource-title mt-2">Businesses</h1>
                    <p class="staff-resource-subtitle">Everything you can access with your QueueFlow staff account.</p>
                </div>
            </div>
            <a class="staff-amber-button w-fit" href="{{ route('staff.businesses.create') }}"><x-staff.icon class="size-4.5" name="plus" />Create business</a>
        </div>
        <dl class="staff-hero-metrics">
            <div class="staff-hero-metric"><dt class="staff-hero-metric-label">BUSINESSES</dt><dd class="staff-hero-metric-value">{{ count($businesses) }}</dd></div>
            <div class="staff-hero-metric"><dt class="staff-hero-metric-label">BRANCHES</dt><dd class="staff-hero-metric-value">{{ $totalBranches }}</dd></div>
            <div class="staff-hero-metric sm:col-span-2 lg:col-span-1"><dt class="staff-hero-metric-label">SERVICES</dt><dd class="staff-hero-metric-value">{{ $totalServices }}</dd></div>
        </dl>
    </section>

    @if ($businesses === [])
        <section class="staff-empty-dashed mt-5" aria-labelledby="business-empty-heading">
            <span class="mx-auto grid size-11 place-items-center rounded-full bg-staff-indigo-soft text-staff-indigo" aria-hidden="true"><x-staff.icon name="business" /></span>
            <h2 id="business-empty-heading" class="mt-3 font-staff-display text-lg font-bold">Create your first business</h2>
            <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-staff-muted">Create a business to establish your owner membership, then add its branches and services.</p>
            <a class="staff-primary-button mt-5" href="{{ route('staff.businesses.create') }}">Create business</a>
        </section>
    @else
        <section class="mt-5" data-business-directory>
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center" aria-label="Filter businesses">
                <label class="flex min-h-12 min-w-0 flex-1 items-center gap-3 rounded-xl border border-staff-line bg-white px-4 lg:max-w-sm">
                    <x-staff.icon class="size-4.5 text-staff-muted" name="search" />
                    <span class="sr-only">Search businesses</span>
                    <input class="min-w-0 flex-1 border-0 bg-transparent text-sm outline-none placeholder:text-staff-muted" type="search" placeholder="Search businesses" data-business-search>
                </label>
                <div class="flex flex-wrap gap-2" role="group" aria-label="Filter businesses by role">
                    <button class="staff-filter-button staff-filter-button-active" type="button" data-business-role="ALL">All {{ count($businesses) }}</button>
                    <button class="staff-filter-button" type="button" data-business-role="OWNER">Owner {{ $roleCounts['OWNER'] }}</button>
                    <button class="staff-filter-button" type="button" data-business-role="MANAGER">Manager {{ $roleCounts['MANAGER'] }}</button>
                    <button class="staff-filter-button" type="button" data-business-role="STAFF">Staff {{ $roleCounts['STAFF'] }}</button>
                </div>
            </div>

            <p class="mt-6 hidden rounded-xl border border-staff-line bg-white px-5 py-6 text-center text-sm text-staff-muted" data-business-no-results>No businesses match the current filters.</p>

            <div class="staff-business-grid mt-5" aria-label="Managed businesses">
            @foreach ($businesses as $business)
                @php
                    $businessMemberships = $membershipsByBusinessId[$business->id] ?? [];
                    $businessSummary = $businessSummaries[$business->id];
                    $businessInitials = collect(preg_split('/\s+/', trim($business->name)))->filter()->take(2)->map(static fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
                    $businessRoles = collect($businessMemberships)->pluck('role')->unique()->values();
                @endphp
                <article class="staff-business-card" data-business-card data-business-name="{{ mb_strtolower($business->name) }}" data-business-roles="{{ $businessRoles->implode(' ') }}">
                    <div class="flex flex-1 flex-col p-5.5">
                        <div class="flex min-w-0 items-center gap-3.5">
                            <span class="grid size-13 shrink-0 place-items-center rounded-2xl bg-staff-indigo font-staff-display text-base font-extrabold text-white" aria-hidden="true">{{ $businessInitials }}</span>
                            <div class="min-w-0">
                                <h2 class="break-words font-staff-display text-lg font-extrabold tracking-[-0.02em]">{{ $business->name }}</h2>
                                <div class="mt-1.5 flex flex-wrap gap-1.5">
                                    @foreach ($businessMemberships as $membership)
                                        <span @class(['staff-role-badge', 'staff-role-owner' => $membership->role === 'OWNER', 'staff-role-manager' => $membership->role === 'MANAGER', 'staff-role-staff' => $membership->role === 'STAFF'])>{{ $membership->role }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <p class="mt-4 text-sm leading-6 {{ $business->description ? 'text-staff-muted' : 'italic text-staff-muted' }}">{{ $business->description ?: 'A description has not been added.' }}</p>
                        <dl class="mt-4 grid grid-cols-2 gap-2.5">
                            <div class="staff-card-muted-block"><dt class="text-xs text-staff-muted">Branches</dt><dd class="mt-1 font-staff-display text-xl font-extrabold">{{ $businessSummary['branchCount'] }}</dd></div>
                            <div class="staff-card-muted-block"><dt class="text-xs text-staff-muted">Services</dt><dd class="mt-1 font-staff-display text-xl font-extrabold">{{ $businessSummary['serviceCount'] }}</dd></div>
                        </dl>
                        @if ($businessSummary['branches'] !== [])
                            <div class="mt-4 flex flex-wrap gap-1.5" aria-label="Branches">
                                @foreach (array_slice($businessSummary['branches'], 0, 2) as $branch)
                                    <span class="inline-flex min-h-7 items-center gap-1.5 rounded-full bg-staff-canvas px-2.5 text-xs font-semibold text-staff-muted"><x-staff.icon class="size-3.5" name="branch" />{{ $branch->name }}</span>
                                @endforeach
                                @if (count($businessSummary['branches']) > 2)
                                    <span class="inline-flex min-h-7 items-center rounded-full bg-staff-canvas px-2.5 text-xs font-semibold text-staff-muted">+{{ count($businessSummary['branches']) - 2 }} more</span>
                                @endif
                            </div>
                        @endif
                    </div>
                    <div class="flex items-center justify-between gap-3 border-t border-[#eeeff8] px-5.5 py-3.5">
                        <span class="text-xs text-staff-muted">Access: {{ $businessRoles->map(static fn (string $role): string => ucfirst(strtolower($role)))->implode(', ') }}</span>
                        <a class="staff-primary-button staff-button-small" href="{{ route('staff.businesses.show', $business->id) }}">Manage<x-staff.icon class="size-4" name="chevron-right" /></a>
                    </div>
                </article>
            @endforeach
            <a class="staff-empty-dashed flex min-h-64 flex-col items-center justify-center text-staff-indigo" href="{{ route('staff.businesses.create') }}">
                <span class="grid size-11 place-items-center rounded-full bg-staff-indigo-soft" aria-hidden="true"><x-staff.icon name="plus" /></span>
                <span class="mt-3 font-bold">Create business</span>
            </a>
            </div>
        </section>
    @endif
@endsection

@push('scripts')
    <script>
        (() => {
            const directory = document.querySelector('[data-business-directory]');

            if (! directory) return;

            const search = directory.querySelector('[data-business-search]');
            const cards = [...directory.querySelectorAll('[data-business-card]')];
            const buttons = [...directory.querySelectorAll('[data-business-role]')];
            const empty = directory.querySelector('[data-business-no-results]');
            let role = 'ALL';

            const filter = () => {
                const query = search.value.trim().toLocaleLowerCase();
                let visible = 0;

                cards.forEach((card) => {
                    const matchesSearch = card.dataset.businessName.includes(query);
                    const matchesRole = role === 'ALL' || card.dataset.businessRoles.split(' ').includes(role);
                    const show = matchesSearch && matchesRole;
                    card.hidden = ! show;
                    if (show) visible++;
                });

                empty.classList.toggle('hidden', visible !== 0);
            };

            search.addEventListener('input', filter);
            buttons.forEach((button) => button.addEventListener('click', () => {
                role = button.dataset.businessRole;
                buttons.forEach((candidate) => candidate.classList.toggle('staff-filter-button-active', candidate === button));
                filter();
            }));
        })();
    </script>
@endpush
