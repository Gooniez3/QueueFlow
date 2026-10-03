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
    @endswitch
</svg>
