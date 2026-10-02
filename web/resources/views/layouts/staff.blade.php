@extends('layouts.app')

@section('body-class', 'bg-cream text-ink')

@section('content')
    <div class="min-h-screen">
        <header class="border-b border-line/70 bg-surface" aria-label="Staff application header">
            <div class="staff-container flex min-w-0 flex-wrap items-center justify-between gap-x-4 gap-y-1 py-2 sm:min-h-16 sm:flex-nowrap sm:py-0">
                <div class="flex min-w-0 items-center gap-4 sm:gap-7">
                    <a class="inline-flex min-h-11 shrink-0 items-center gap-2.5 rounded-lg text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand" href="{{ route('staff.home') }}" aria-label="QueueFlow staff workspace">
                        <span class="grid size-9 place-items-center rounded-lg bg-brand text-white" aria-hidden="true">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="6.75" stroke="currentColor" stroke-width="1.7" />
                                <path d="M12 8.5v4l2.75 1.65" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                        <span class="font-editorial text-xl font-semibold tracking-[-0.025em]">QueueFlow</span>
                    </a>

                    <nav class="hidden self-stretch sm:flex sm:items-center sm:gap-1" aria-label="Staff navigation">
                        <a
                            class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand {{ request()->routeIs('staff.home') ? 'bg-brand/7 text-brand' : 'text-muted hover:bg-cream hover:text-ink' }}"
                            href="{{ route('staff.home') }}"
                            @if (request()->routeIs('staff.home')) aria-current="page" @endif
                        >Workspace</a>
                        <a
                            class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand {{ request()->routeIs('staff.businesses.*') ? 'bg-brand/7 text-brand' : 'text-muted hover:bg-cream hover:text-ink' }}"
                            href="{{ route('staff.businesses.index') }}"
                            @if (request()->routeIs('staff.businesses.*')) aria-current="page" @endif
                        >Businesses</a>
                    </nav>
                </div>

                <form class="shrink-0" method="POST" action="{{ route('staff.logout') }}">
                    @csrf
                    <button class="inline-flex min-h-11 items-center gap-2 rounded-lg px-2 text-sm font-medium text-muted transition-colors hover:bg-cream hover:text-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand" type="submit">
                        <svg class="size-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M8 4H4.5A1.5 1.5 0 0 0 3 5.5v9A1.5 1.5 0 0 0 4.5 16H8m4-3 3-3-3-3m3 3H7.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <span>Sign out</span>
                    </button>
                </form>

                <nav class="order-3 flex w-full min-w-0 gap-1 border-t border-line/70 pt-1 sm:hidden" aria-label="Staff navigation">
                    <a
                        class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand {{ request()->routeIs('staff.home') ? 'bg-brand/7 text-brand' : 'text-muted' }}"
                        href="{{ route('staff.home') }}"
                        @if (request()->routeIs('staff.home')) aria-current="page" @endif
                    >Workspace</a>
                    <a
                        class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand {{ request()->routeIs('staff.businesses.*') ? 'bg-brand/7 text-brand' : 'text-muted' }}"
                        href="{{ route('staff.businesses.index') }}"
                        @if (request()->routeIs('staff.businesses.*')) aria-current="page" @endif
                    >Businesses</a>
                </nav>
            </div>
        </header>

        <div class="staff-container staff-page">
            @if (session('status') && ! request()->routeIs('staff.home'))
                <div class="staff-alert-success" role="status">
                    <svg class="mt-1 size-4 shrink-0 text-brand" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="m4.5 10.5 3.25 3.25L15.5 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @yield('staff-content')
        </div>
    </div>
@endsection
