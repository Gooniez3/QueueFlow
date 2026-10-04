@props(['items' => []])

<nav class="staff-breadcrumb" aria-label="Staff page context">
    @foreach ($items as $item)
        @if (! $loop->first)
            <x-staff.icon class="size-3.5 opacity-50" name="chevron-right" />
        @endif

        @if ($item['url'] ?? null)
            <a class="truncate transition-colors hover:text-staff-indigo" href="{{ $item['url'] }}">{{ $item['label'] }}</a>
        @elseif ($loop->last)
            <strong>{{ $item['label'] }}</strong>
        @else
            <span class="truncate">{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
