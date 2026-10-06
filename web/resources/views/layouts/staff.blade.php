@extends('layouts.app')

@section('body-class', 'bg-staff-canvas text-staff-ink')

@section('content')
    @php
        $staffUser = $authContext['user'] ?? null;
        $staffMemberships = $authContext['memberships'] ?? [];
        $staffName = $staffUser === null
            ? 'Staff member'
            : trim($staffUser->firstName.' '.$staffUser->lastName);
        $staffEmail = $staffUser?->email;
        $staffInitials = $staffUser === null
            ? 'QF'
            : mb_strtoupper(mb_substr($staffUser->firstName, 0, 1).mb_substr($staffUser->lastName, 0, 1));
        $staffRole = count($staffMemberships) === 1
            ? $staffMemberships[0]->role
            : null;
    @endphp

    <div class="staff-app">
        <aside class="staff-sidebar" aria-label="QueueFlow staff portal">
            <a class="staff-brand" href="{{ route('staff.home') }}" aria-label="QueueFlow staff portal overview">
                <span class="staff-brand-mark"><x-staff.icon class="size-5.5" name="clock" /></span>
                <span>
                    <span class="staff-brand-name">QueueFlow</span>
                    <span class="staff-brand-caption">STAFF PORTAL</span>
                </span>
            </a>

            <x-staff.navigation class="min-h-0 flex-1 overflow-y-auto" />

            <div class="staff-sidebar-account">
                <span class="staff-avatar">{{ $staffInitials }}</span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-[0.8rem] font-bold text-white">{{ $staffName }}</span>
                    <span class="mt-0.5 block truncate text-xs text-staff-sidebar-muted">{{ $staffEmail }}</span>
                    @if ($staffRole)
                        <span class="mt-1 block text-[0.65rem] font-bold tracking-[0.08em] text-staff-amber">{{ $staffRole }}</span>
                    @endif
                </span>
                <form method="POST" action="{{ route('staff.logout') }}">
                    @csrf
                    <button class="staff-sidebar-logout" type="submit" aria-label="Sign out">
                        <x-staff.icon name="logout" />
                    </button>
                </form>
            </div>
        </aside>

        <div class="staff-workspace">
            <header class="staff-mobile-header">
                <details class="staff-mobile-nav">
                    <summary class="staff-mobile-menu-button" aria-label="Open staff navigation">
                        <x-staff.icon class="size-6" name="menu" />
                    </summary>
                    <div class="staff-mobile-menu-panel">
                        <div class="flex items-center gap-3 border-b border-white/10 px-4 py-4">
                            <span class="staff-brand-mark"><x-staff.icon name="clock" /></span>
                            <span>
                                <span class="staff-brand-name">QueueFlow</span>
                                <span class="staff-brand-caption">STAFF PORTAL</span>
                            </span>
                        </div>
                        <x-staff.navigation class="max-h-[calc(100dvh-13rem)] overflow-y-auto px-3 py-3" label="Mobile staff navigation" />
                        <div class="staff-mobile-account">
                            <span class="staff-avatar">{{ $staffInitials }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-bold text-white">{{ $staffName }}</span>
                                <span class="block truncate text-xs text-staff-sidebar-muted">{{ $staffEmail }}</span>
                            </span>
                            @if ($staffRole)
                                <span class="staff-role-badge staff-role-badge-dark">{{ $staffRole }}</span>
                            @endif
                            <form method="POST" action="{{ route('staff.logout') }}">
                                @csrf
                                <button class="staff-sidebar-logout" type="submit" aria-label="Sign out">
                                    <x-staff.icon name="logout" />
                                </button>
                            </form>
                        </div>
                    </div>
                </details>

                <span class="staff-mobile-title">@yield('title', 'QueueFlow')</span>
                <span class="staff-avatar staff-avatar-small" aria-label="Signed in as {{ $staffName }}">{{ $staffInitials }}</span>
            </header>

            <header class="staff-topbar" aria-label="Staff page context">
                @hasSection('staff-topbar-breadcrumbs')
                    @yield('staff-topbar-breadcrumbs')
                @else
                    <div class="staff-breadcrumb">
                        <span>@yield('staff-area', 'Workspace')</span>
                        <x-staff.icon class="size-3.5 opacity-50" name="chevron-right" />
                        <strong>@yield('title', 'QueueFlow')</strong>
                    </div>
                @endif
                <div class="flex min-w-0 items-center gap-2.5">
                    @hasSection('staff-context')
                        @yield('staff-context')
                    @endif
                    <div class="staff-context-chip border-0 bg-staff-canvas" aria-label="Current date">
                        <x-staff.icon class="size-4.5 text-staff-muted" name="calendar" />
                        <time datetime="{{ now()->toDateString() }}">{{ now()->format('D, j M Y') }}</time>
                    </div>
                    <div class="staff-account-chip" aria-label="Signed in staff member">
                        <span class="staff-avatar staff-avatar-small">{{ $staffInitials }}</span>
                        <span class="max-w-48 truncate font-semibold">{{ $staffName }}</span>
                        @if ($staffRole)
                            <span class="staff-role-badge staff-role-badge-dark">{{ $staffRole }}</span>
                        @endif
                    </div>
                </div>
            </header>

            <div class="staff-content">
                @if (session('status') && ! request()->routeIs('staff.home'))
                    <div class="staff-alert-success" role="status">
                        <svg class="mt-0.5 size-4 shrink-0 text-staff-success" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="m4.5 10.5 3.25 3.25L15.5 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                @if (session('error'))
                    <div class="staff-alert-error mb-6" role="alert">{{ session('error') }}</div>
                @endif

                @yield('staff-content')
            </div>
        </div>
    </div>
@endsection
