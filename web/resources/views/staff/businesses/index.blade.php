@extends('layouts.staff')

@section('title', 'Businesses')
@section('staff-area', 'Management')

@section('staff-content')
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="staff-page-title mt-0">Businesses</h1>
            <p class="staff-page-copy">Businesses you can access with your staff account.</p>
        </div>

        @if ($businesses !== [])
            <a class="staff-primary-button w-fit shrink-0" href="{{ route('staff.businesses.create') }}">
                <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                Create business
            </a>
        @endif
    </header>

    @if ($businesses === [])
        <section class="staff-empty-state mt-7" aria-labelledby="business-empty-heading">
            <div class="max-w-2xl">
                <p class="staff-eyebrow">GET STARTED</p>
                <h2 id="business-empty-heading" class="mt-3 font-staff-display text-2xl font-bold tracking-[-0.025em] text-staff-ink">Create your first business</h2>
                <p class="mt-3 text-sm leading-6 text-staff-muted">You do not have a business membership yet. Create a business to establish your owner membership, then add its branches and services.</p>
                <a class="staff-primary-button mt-6" href="{{ route('staff.businesses.create') }}">Create business</a>
            </div>
        </section>
    @else
        <section class="staff-card mt-7 overflow-hidden" aria-label="Managed businesses">
            <div class="hidden min-h-11 grid-cols-[minmax(0,2.4fr)_minmax(8rem,1fr)_9rem] items-center gap-4 rounded-t-[1.2rem] bg-[#f8f8fd] px-5 font-staff-display text-[0.68rem] font-semibold tracking-[0.12em] text-staff-muted md:grid">
                <span>BUSINESS</span>
                <span>YOUR ROLE</span>
                <span aria-hidden="true"></span>
            </div>

            @foreach ($businesses as $business)
                @php
                    $businessMemberships = $membershipsByBusinessId[$business->id] ?? [];
                    $businessInitials = collect(preg_split('/\s+/', trim($business->name)))
                        ->filter()
                        ->take(2)
                        ->map(static fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))
                        ->implode('');
                @endphp
                <div class="grid min-w-0 gap-4 border-t border-[#eeeff8] px-5 py-4 first:border-t-0 md:min-h-20 md:grid-cols-[minmax(0,2.4fr)_minmax(8rem,1fr)_9rem] md:items-center md:first:border-t">
                    <div class="flex min-w-0 items-center gap-3.5">
                        <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-staff-indigo font-staff-display text-xs font-extrabold text-white" aria-hidden="true">{{ $businessInitials }}</span>
                        <div class="min-w-0">
                            <h2 class="break-words font-bold text-staff-ink">{{ $business->name }}</h2>
                            <p class="mt-0.5 truncate text-[0.8rem] {{ $business->description ? 'text-staff-muted' : 'italic text-staff-muted' }}">{{ $business->description ?: 'A description has not been added.' }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($businessMemberships as $membership)
                            <span @class([
                                'staff-role-badge',
                                'staff-role-owner' => $membership->role === 'OWNER',
                                'staff-role-manager' => $membership->role === 'MANAGER',
                                'staff-role-staff' => $membership->role === 'STAFF',
                            ])>{{ $membership->role }}</span>
                        @endforeach
                    </div>

                    <a class="staff-secondary-button w-fit md:justify-self-end" href="{{ route('staff.businesses.show', $business->id) }}">
                        Manage
                        <x-staff.icon class="size-4" name="chevron-right" />
                    </a>
                </div>
            @endforeach
        </section>
    @endif
@endsection
