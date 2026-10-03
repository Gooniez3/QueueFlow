@props(['pattern', 'dark' => false])

<div
    {{ $attributes->class([
        'grid aspect-square w-full grid-cols-[repeat(13,minmax(0,1fr))] gap-px rounded-xl p-2',
        'bg-white' => $dark,
        'bg-customer-canvas ring-1 ring-customer-line' => ! $dark,
    ]) }}
    role="img"
    aria-label="Demonstration QR presentation. Not scannable."
    data-demo-qr
>
    @foreach ($pattern as $row)
        @foreach (str_split($row) as $module)
            <span @class([
                'aspect-square',
                'bg-customer-navy' => $module === '1',
                'bg-transparent' => $module === '0',
            ])></span>
        @endforeach
    @endforeach
</div>
