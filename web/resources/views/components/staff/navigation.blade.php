@props(['label' => 'Staff navigation'])

@php
    $liveQueuesActive = request()->routeIs('staff.live-queues.*');
    $activeManagementSection = match (true) {
        request()->routeIs('staff.services.*') => 'services',
        request()->routeIs('staff.branches.*') => 'branches',
        request()->routeIs('staff.businesses.*') => 'businesses',
        default => null,
    };
@endphp

<nav {{ $attributes->class('staff-navigation') }} aria-label="{{ $label }}">
    <a
        class="staff-nav-item {{ request()->routeIs('staff.home') ? 'staff-nav-item-active' : '' }}"
        href="{{ route('staff.home') }}"
        @if (request()->routeIs('staff.home')) aria-current="page" @endif
    >
        <x-staff.icon name="overview" />
        <span>Overview</span>
    </a>

    <p class="staff-nav-section">OPERATIONS</p>
    <a
        class="staff-nav-item {{ $liveQueuesActive ? 'staff-nav-item-active' : '' }}"
        href="{{ route('staff.live-queues.gateway') }}"
        @if ($liveQueuesActive) aria-current="page" @endif
    >
        <x-staff.icon name="queues" />
        <span>Live Queues</span>
    </a>

    <p class="staff-nav-section">MANAGEMENT</p>
    <a
        class="staff-nav-item {{ $activeManagementSection === 'businesses' ? 'staff-nav-item-active' : '' }}"
        href="{{ route('staff.businesses.index') }}"
        @if ($activeManagementSection === 'businesses') aria-current="page" @endif
    >
        <x-staff.icon name="business" />
        <span>Businesses</span>
    </a>
    <a
        class="staff-nav-item {{ $activeManagementSection === 'branches' ? 'staff-nav-item-active' : '' }}"
        href="{{ route('staff.branches.index') }}"
        @if ($activeManagementSection === 'branches') aria-current="page" @endif
    >
        <x-staff.icon name="branch" />
        <span>Branches</span>
    </a>
    <a
        class="staff-nav-item {{ $activeManagementSection === 'services' ? 'staff-nav-item-active' : '' }}"
        href="{{ route('staff.services.index') }}"
        @if ($activeManagementSection === 'services') aria-current="page" @endif
    >
        <x-staff.icon name="service" />
        <span>Services</span>
    </a>

    <p class="staff-nav-section">PLANNING</p>
    <span class="staff-nav-item staff-nav-item-disabled" aria-disabled="true">
        <x-staff.icon name="calendar" />
        <span>Pre-Queue / Appointments</span>
        <span class="staff-soon-badge">Soon</span>
    </span>

    <p class="staff-nav-section">INSIGHTS</p>
    <span class="staff-nav-item staff-nav-item-disabled" aria-disabled="true">
        <x-staff.icon name="analytics" />
        <span>Analytics</span>
        <span class="staff-soon-badge">Soon</span>
    </span>

    <p class="staff-nav-section">SYSTEM</p>
    <span class="staff-nav-item staff-nav-item-disabled" aria-disabled="true">
        <x-staff.icon name="settings" />
        <span>Settings</span>
    </span>
</nav>
