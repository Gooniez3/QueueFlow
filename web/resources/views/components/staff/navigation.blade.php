@props(['label' => 'Staff navigation'])

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
    <span class="staff-nav-item staff-nav-item-disabled" aria-disabled="true">
        <x-staff.icon name="queues" />
        <span>Live Queues</span>
    </span>

    <p class="staff-nav-section">MANAGEMENT</p>
    <a
        class="staff-nav-item {{ request()->routeIs('staff.businesses.*') ? 'staff-nav-item-active' : '' }}"
        href="{{ route('staff.businesses.index') }}"
        @if (request()->routeIs('staff.businesses.*')) aria-current="page" @endif
    >
        <x-staff.icon name="business" />
        <span>Businesses</span>
    </a>
    <span
        class="staff-nav-item staff-nav-item-disabled {{ request()->routeIs('staff.branches.*') ? 'staff-nav-item-active' : '' }}"
        aria-disabled="true"
        @if (request()->routeIs('staff.branches.*')) aria-current="page" @endif
    >
        <x-staff.icon name="branch" />
        <span>Branches</span>
    </span>
    <span
        class="staff-nav-item staff-nav-item-disabled {{ request()->routeIs('staff.services.*') ? 'staff-nav-item-active' : '' }}"
        aria-disabled="true"
        @if (request()->routeIs('staff.services.*')) aria-current="page" @endif
    >
        <x-staff.icon name="service" />
        <span>Services</span>
    </span>

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
