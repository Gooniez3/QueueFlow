@props(['updated' => null])

@php
    $isHome = request()->routeIs('home', 'businesses.show', 'branches.show', 'services.show');
    $isTicket = request()->routeIs('tickets.show');
    $activeDestination = $isTicket ? 'ticket' : 'home';
@endphp

<header data-customer-navigation data-active-destination="{{ $activeDestination }}">
    <div class="flex items-center gap-4">
        <a class="inline-flex items-center gap-3 text-ink" href="{{ route('home') }}" aria-label="QueueFlow home">
            <span class="grid size-8 place-items-center rounded-lg bg-brand font-bold text-white">Q</span>
            <span class="font-semibold tracking-[-0.02em]">QueueFlow</span>
        </a>

        <nav class="ml-auto hidden items-center gap-7 text-sm font-medium md:flex" aria-label="Customer navigation">
            <a
                class="border-b-2 px-0.5 py-2 transition {{ $isHome ? 'border-brand text-brand' : 'border-transparent text-muted hover:border-line hover:text-ink' }}"
                href="{{ route('home') }}"
                @if ($isHome) aria-current="page" @endif
            >Home</a>
            <a
                class="border-b-2 px-0.5 py-2 transition {{ $isTicket ? 'border-brand text-brand' : 'border-transparent text-muted hover:border-line hover:text-ink' }}"
                href="{{ route('tickets.show') }}"
                @if ($isTicket) aria-current="page" @endif
            >My Ticket</a>
        </nav>

        @if ($updated)
            <p class="ml-auto shrink-0 text-xs text-muted md:ml-2 md:border-l md:border-line md:pl-6">Updated {{ strtolower($updated) }}</p>
        @endif
    </div>

    <nav
        class="fixed inset-x-3 bottom-2 z-50 mx-auto max-w-md rounded-[1.75rem] border border-line bg-[#fffdfa]/95 px-2 py-2.5 shadow-[0_18px_50px_-22px_rgba(7,23,19,0.4)] backdrop-blur-sm [bottom:calc(0.625rem+env(safe-area-inset-bottom))] md:hidden"
        aria-label="Customer navigation"
        data-customer-bottom-navigation
    >
        <div class="grid grid-cols-4">
            <a
                class="flex min-h-16 flex-col items-center justify-center gap-1 text-[0.68rem] font-medium transition {{ $isHome ? 'font-semibold text-brand' : 'text-muted hover:text-ink' }}"
                href="{{ route('home') }}"
                @if ($isHome) aria-current="page" @endif
            >
                <span class="grid size-9 place-items-center rounded-xl transition {{ $isHome ? 'bg-brand/10' : '' }}">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3.75 10.5 12 3.75l8.25 6.75v8.25a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5V10.5Z" />
                        <path d="M9 20.25v-6h6v6" />
                    </svg>
                </span>
                <span>Home</span>
            </a>

            <button
                class="flex min-h-16 cursor-not-allowed flex-col items-center justify-center gap-1 text-[0.68rem] font-medium text-muted opacity-45"
                type="button"
                aria-label="Explore, coming later"
                data-future-destination="explore"
                disabled
            >
                <span class="grid size-9 place-items-center rounded-xl">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="8.25" />
                        <path d="m14.75 9.25-1.6 3.9-3.9 1.6 1.6-3.9 3.9-1.6Z" />
                    </svg>
                </span>
                <span>Explore</span>
            </button>

            <a
                class="flex min-h-16 flex-col items-center justify-center gap-1 text-[0.68rem] font-medium transition {{ $isTicket ? 'font-semibold text-brand' : 'text-muted hover:text-ink' }}"
                href="{{ route('tickets.show') }}"
                @if ($isTicket) aria-current="page" @endif
            >
                <span class="grid size-9 place-items-center rounded-xl transition {{ $isTicket ? 'bg-brand/10' : '' }}">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5.25 4.5h13.5v4.125a2.25 2.25 0 0 0 0 4.5V19.5H5.25v-6.375a2.25 2.25 0 0 0 0-4.5V4.5Z" />
                        <path d="M9 8.25h6M9 12h4.5" />
                    </svg>
                </span>
                <span>My Ticket</span>
            </a>

            <button
                class="flex min-h-16 cursor-not-allowed flex-col items-center justify-center gap-1 text-[0.68rem] font-medium text-muted opacity-45"
                type="button"
                aria-label="More, coming later"
                data-future-destination="more"
                disabled
            >
                <span class="grid size-9 place-items-center rounded-xl">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                        <path d="M5.5 12h.01M12 12h.01M18.5 12h.01" />
                    </svg>
                </span>
                <span>More</span>
            </button>
        </div>
    </nav>
</header>
