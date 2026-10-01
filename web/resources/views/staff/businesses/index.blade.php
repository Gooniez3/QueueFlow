@extends('layouts.staff')

@section('title', 'Your businesses')

@section('staff-content')
    <header class="flex flex-col gap-4 border-b border-line/60 pb-5 sm:gap-5 sm:pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="staff-eyebrow">BUSINESS MANAGEMENT</p>
            <h1 class="staff-page-title">Your businesses</h1>
            <p class="staff-page-copy">Manage the businesses connected to your QueueFlow account.</p>
        </div>

        @if ($businesses !== [])
            <a class="staff-primary-button w-fit shrink-0" href="{{ route('staff.businesses.create') }}">
                <span class="text-xl leading-none" aria-hidden="true">+</span>
                Create business
            </a>
        @endif
    </header>

    @if ($businesses === [])
        <section class="staff-empty-state" aria-labelledby="business-empty-heading">
            <div class="max-w-2xl">
                <p class="staff-eyebrow">GET STARTED</p>
                <h2 id="business-empty-heading" class="mt-3 font-editorial text-2xl tracking-[-0.025em] text-ink">Create your first business</h2>
                <p class="mt-3 text-sm leading-6 text-muted">You do not have a business membership yet. Create a business to establish your owner membership, then add its branches and services.</p>
                <a class="staff-primary-button mt-6" href="{{ route('staff.businesses.create') }}">Create business</a>
            </div>
        </section>
    @else
        <section class="mt-6 sm:mt-8" aria-label="Managed businesses">
            <div class="staff-resource-list mt-0">
                @foreach ($businesses as $business)
                    <a class="staff-resource-row" href="{{ route('staff.businesses.show', $business->id) }}">
                        <div class="min-w-0">
                            <h2 class="break-words font-editorial text-lg font-semibold tracking-[-0.015em] text-ink sm:text-xl sm:tracking-[-0.02em]">{{ $business->name }}</h2>
                            <p class="mt-0.5 max-w-2xl text-sm leading-5 sm:mt-1 sm:leading-6 {{ $business->description ? 'text-muted' : 'italic text-muted/80' }}">
                                {{ $business->description ?: 'A description has not been added.' }}
                            </p>
                        </div>
                        <span class="grid size-10 shrink-0 place-items-center text-muted" aria-hidden="true">
                            <svg class="size-4" viewBox="0 0 16 16" fill="none"><path d="m6 3 5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
@endsection
