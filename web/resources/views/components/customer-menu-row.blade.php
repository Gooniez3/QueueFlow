@props(['label', 'icon', 'value' => null, 'danger' => false])

<div {{ $attributes->class(['flex min-h-14 items-center gap-3 px-4 py-3', 'text-red-700' => $danger]) }} aria-disabled="true">
    <span @class(['grid size-9 shrink-0 place-items-center rounded-xl', 'bg-red-50 text-red-600' => $danger, 'bg-customer-indigo/10 text-customer-indigo' => ! $danger]) aria-hidden="true"><x-customer-icon :name="$icon" /></span>
    <span class="min-w-0 grow text-sm font-semibold">{{ $label }}</span>
    @if ($value)<span class="shrink-0 text-xs text-customer-muted">{{ $value }}</span>@endif
    <x-customer-icon class="size-4 shrink-0 text-customer-muted/60" name="chevron-right" aria-hidden="true" />
</div>
