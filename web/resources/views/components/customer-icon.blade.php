@props(['name'])

<svg
    {{ $attributes->class('size-5') }}
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
>
    @switch($name)
        @case('heart')
            <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z" />
            @break

        @case('history')
            <path d="M3 12a9 9 0 1 0 3-6.7L3 8" />
            <path d="M3 3v5h5M12 7v5l3 2" />
            @break

        @case('bell')
            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" />
            @break

        @case('globe')
            <circle cx="12" cy="12" r="9" />
            <path d="M3 12h18M12 3c2.3 2.5 3.5 5.5 3.5 9S14.3 18.5 12 21c-2.3-2.5-3.5-5.5-3.5-9S9.7 5.5 12 3Z" />
            @break

        @case('help-circle')
            <circle cx="12" cy="12" r="9" />
            <path d="M9.7 9a2.5 2.5 0 1 1 3.6 2.3c-.8.4-1.3 1-1.3 1.7v.5M12 17h.01" />
            @break

        @case('trash')
            <path d="M4 7h16M9 7V4h6v3M6.5 7l.7 13h9.6l.7-13M10 11v5M14 11v5" />
            @break

        @case('info-circle')
            <circle cx="12" cy="12" r="9" />
            <path d="M12 11v5M12 8h.01" />
            @break

        @case('route')
            <circle cx="5" cy="18" r="2" />
            <circle cx="19" cy="6" r="2" />
            <path d="M7 18h3a2 2 0 0 0 2-2V8a2 2 0 0 1 2-2h3M14 10l-2-2-2 2" />
            @break

        @case('message-question')
            <path d="M21 12a8 8 0 0 1-8 8H6l-3 2v-6a8 8 0 1 1 18-4Z" />
            <path d="M9.8 9a2.3 2.3 0 1 1 3.3 2.1c-.7.4-1.1.9-1.1 1.6M12 16h.01" />
            @break

        @case('document-text')
            <path d="M6 3h8l4 4v14H6V3Z" />
            <path d="M14 3v5h4M9 13h6M9 17h6" />
            @break

        @case('shield-lock')
            <path d="M12 3 5 6v5c0 4.6 2.8 8.1 7 10 4.2-1.9 7-5.4 7-10V6l-7-3Z" />
            <rect x="9" y="10.5" width="6" height="5" rx="1" />
            <path d="M10.5 10.5V9a1.5 1.5 0 0 1 3 0v1.5" />
            @break

        @case('chevron-right')
            <path d="m9 18 6-6-6-6" />
            @break

        @case('hospital')
            <path d="M3 21h18M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16M9 7h6M12 4v6M9 13h2M13 13h2M9 17h2M13 17h2" />
            @break

        @case('landmark')
            <path d="m3 10 9-6 9 6M5 10h14M6 10v8M10 10v8M14 10v8M18 10v8M3 21h18M4 18h16" />
            @break

        @case('building-2')
            <path d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16M16 8h2a2 2 0 0 1 2 2v11M8 7h4M8 11h4M8 15h4M8 19h4M18 13h.01M18 17h.01" />
            @break

        @case('utensils')
            <path d="M7 3v7M4 3v4a3 3 0 0 0 6 0V3M7 10v11M17 3v18M17 3c2 2 3 4.5 3 7h-3" />
            @break

        @case('calendar-days')
            <rect x="3" y="4.5" width="18" height="16" rx="2" />
            <path d="M16 2.5v4M8 2.5v4M3 9.5h18M8 13h.01M12 13h.01M16 13h.01M8 17h.01M12 17h.01M16 17h.01" />
            @break

        @case('shopping-bag')
            <path d="M5 8h14l-1 13H6L5 8ZM9 8V6a3 3 0 0 1 6 0v2" />
            @break

        @default
            <path d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16M16 8h2a2 2 0 0 1 2 2v11M8 7h4M8 11h4M8 15h4M8 19h4" />
    @endswitch
</svg>
