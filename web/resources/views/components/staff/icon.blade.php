@props(['name'])

<svg
    {{ $attributes->class('size-5 shrink-0') }}
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
>
    @switch($name)
        @case('clock')
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v5l3 2" />
        @break

        @case('overview')
            <rect x="3" y="3" width="7" height="7" rx="1.5" />
            <rect x="14" y="3" width="7" height="7" rx="1.5" />
            <rect x="3" y="14" width="7" height="7" rx="1.5" />
            <rect x="14" y="14" width="7" height="7" rx="1.5" />
        @break

        @case('queues')
            <path d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01" />
            @break
        @case('search')
            <circle cx="11" cy="11" r="7" />
            <path d="m20 20-4-4" />
            @break

        @case('business')
            <path d="M4 20V5h10v15M14 10h6v10M8 9h2M8 13h2M8 17h2M4 20h16" />
        @break

        @case('branch')
            <path d="M12 21s7-6.2 7-11a7 7 0 0 0-14 0c0 4.8 7 11 7 11Z" />
            <circle cx="12" cy="10" r="2.5" />
        @break

        @case('service')
            <path d="M14.5 6.5a4 4 0 0 0-5 5L4 17l3 3 5.5-5.5a4 4 0 0 0 5-5L15 12l-2.5-.5L12 9Z" />
        @break

        @case('calendar')
            <rect x="4" y="5" width="16" height="15" rx="2" />
            <path d="M4 10h16M8 3v4M16 3v4" />
        @break

        @case('analytics')
            <path d="M5 20V10M12 20V4M19 20v-7" />
        @break

        @case('settings')
            <circle cx="12" cy="12" r="3" />
            <path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M18.4 5.6l-2.1 2.1M7.7 16.3l-2.1 2.1" />
        @break

        @case('logout')
            <path d="M10 4H5v16h5M16 8l4 4-4 4M20 12H9" />
        @break

        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16" />
        @break

        @case('chevron-right')
            <path d="m9 6 6 6-6 6" />
        @break

        @case('plus')
            <path d="M12 5v14M5 12h14" />
        @break

        @case('edit')
            <path d="M4 20h4L19 9l-4-4L4 16Z" />
        @break

        @case('check')
            <path d="m5 12 4.5 4.5L19 7" />
        @break

        @case('info')
            <circle cx="12" cy="12" r="9" />
            <path d="M12 11v5M12 8h.01" />
        @break

        @case('refresh')
            <path d="M20 11a8 8 0 1 0-2 6M20 4v7h-7" />
        @break

        @case('megaphone')
            <path d="M3 11v2l11 4V7L3 11Z" />
            <path d="M14 8a4 4 0 0 1 0 8M6 14l1.5 5h3" />
        @break

        @case('pause')
            <path d="M8 5v14M16 5v14" />
        @break

        @case('close')
            <path d="m6 6 12 12M18 6 6 18" />
        @break

        @case('play')
            <path d="m7 4 13 8-13 8V4Z" />
        @break

        @case('skip')
            <path d="m5 5 9 7-9 7V5ZM17 5v14" />
        @break
    @endswitch
</svg>
