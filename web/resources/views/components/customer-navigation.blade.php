@php
    $isHome = request()->routeIs('home');
    $isPlaces = request()->routeIs('places.*', 'businesses.show', 'branches.show', 'services.show');
    $isTicket = request()->routeIs('tickets.show', 'queue-entries.show');
    $isAccount = request()->routeIs('account.show');
    $isMore = request()->routeIs('more.show');
    $activeDestination = match (true) {
        $isPlaces => 'places',
        $isTicket => 'ticket',
        $isAccount => 'account',
        $isMore => 'more',
        default => 'home',
    };
@endphp

<div
    class="pointer-events-none fixed inset-x-0 z-50 mx-auto w-full max-w-[28rem] px-4 [bottom:calc(0.75rem+env(safe-area-inset-bottom))]"
    data-customer-navigation
    data-active-destination="{{ $activeDestination }}"
>
    <nav
        class="pointer-events-auto w-full rounded-[2rem] bg-white/95 p-1.5 shadow-[0_16px_40px_-18px_rgba(21,17,63,0.45)] ring-1 ring-customer-line/50 backdrop-blur-sm"
        aria-label="Customer navigation"
        data-customer-bottom-navigation
    >
        <div class="grid grid-cols-5">
            <a
                @class([
                    'flex min-h-14 flex-col items-center justify-center gap-0.5 rounded-[1.45rem] px-1 text-[0.68rem] font-medium transition focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-customer-indigo',
                    'bg-customer-indigo font-bold text-white' => $isHome,
                    'text-customer-muted hover:text-customer-indigo' => ! $isHome,
                ])
                href="{{ route('home') }}"
                @if ($isHome) aria-current="page" @endif
            >
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3.75 10.5 12 3.75l8.25 6.75v8.25a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5V10.5Z" />
                    <path d="M9 20.25v-6h6v6" />
                </svg>
                <span>Home</span>
            </a>

            <a
                @class([
                    'flex min-h-14 flex-col items-center justify-center gap-0.5 rounded-[1.45rem] px-1 text-[0.68rem] font-medium transition focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-customer-indigo',
                    'bg-customer-indigo font-bold text-white' => $isPlaces,
                    'text-customer-muted hover:text-customer-indigo' => ! $isPlaces,
                ])
                href="{{ route('places.index') }}"
                @if ($isPlaces) aria-current="page" @endif
            >
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20.25 10.5c0 5.25-8.25 10.5-8.25 10.5S3.75 15.75 3.75 10.5a8.25 8.25 0 1 1 16.5 0Z" />
                    <circle cx="12" cy="10.5" r="2.5" />
                </svg>
                <span>Places</span>
            </a>

            <a
                @class([
                    'flex min-h-14 flex-col items-center justify-center gap-0.5 rounded-[1.45rem] px-1 text-[0.68rem] font-medium transition focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-customer-indigo',
                    'bg-customer-indigo font-bold text-white' => $isTicket,
                    'text-customer-muted hover:text-customer-indigo' => ! $isTicket,
                ])
                href="{{ route('tickets.show') }}"
                @if ($isTicket) aria-current="page" @endif
            >
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4.5 5.25h15v4.125a2.625 2.625 0 0 0 0 5.25v4.125h-15v-4.125a2.625 2.625 0 0 0 0-5.25V5.25Z" />
                </svg>
                <span>Tickets</span>
            </a>

            <a
                @class([
                    'flex min-h-14 flex-col items-center justify-center gap-0.5 rounded-[1.45rem] px-1 text-[0.68rem] font-medium transition focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-customer-indigo',
                    'bg-customer-indigo font-bold text-white' => $isAccount,
                    'text-customer-muted hover:text-customer-indigo' => ! $isAccount,
                ])
                href="{{ route('account.show') }}"
                @if ($isAccount) aria-current="page" @endif
            >
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="8" r="3.25" />
                    <path d="M5.5 20.25c.5-4 2.67-6 6.5-6s6 2 6.5 6" />
                </svg>
                <span>Account</span>
            </a>

            <a
                @class([
                    'flex min-h-14 flex-col items-center justify-center gap-0.5 rounded-[1.45rem] px-1 text-[0.68rem] font-medium transition focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-customer-indigo',
                    'bg-customer-indigo font-bold text-white' => $isMore,
                    'text-customer-muted hover:text-customer-indigo' => ! $isMore,
                ])
                href="{{ route('more.show') }}"
                @if ($isMore) aria-current="page" @endif
            >
                <svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <circle cx="5" cy="12" r="1.5" />
                    <circle cx="12" cy="12" r="1.5" />
                    <circle cx="19" cy="12" r="1.5" />
                </svg>
                <span>More</span>
            </a>
        </div>
    </nav>
</div>
