@props([
    'backUrl',
    'backLabel',
    'items' => [],
])

<nav class="flex min-w-0 flex-col items-start gap-3" aria-label="Page hierarchy">
    <a class="staff-back-control group" href="{{ $backUrl }}">
        <span class="grid size-8 shrink-0 place-items-center rounded-full border border-staff-line bg-staff-surface transition-colors group-hover:border-staff-indigo/30" aria-hidden="true">
            <svg class="size-4" viewBox="0 0 20 20" fill="none">
                <path d="M15.5 10H4.5M9 4.5 3.5 10 9 15.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </span>
        <span class="min-w-0 break-words">{{ $backLabel }}</span>
    </a>

    <ol class="hidden min-w-0 flex-wrap items-center gap-2 text-sm sm:flex" aria-label="Breadcrumb">
        @foreach ($items as $item)
            <li class="inline-flex items-center gap-2">
                @if (! $loop->first)
                    <svg class="size-3.5 text-staff-line" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                        <path d="m6 3 5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                @endif

                @if ($item['url'] ?? null)
                    <a class="rounded-sm font-medium text-staff-muted transition-colors hover:text-staff-indigo focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-staff-indigo" href="{{ $item['url'] }}">
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="font-medium text-staff-ink" aria-current="page">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
