@props([
    'reading', // App\Support\CarParkCapacityReading
    'okBarClass' => 'bg-emerald-500',
    'ariaLabel' => '',
    'thick' => false,
])

@php
    /** @var \App\Support\CarParkCapacityReading $reading */
    $fill = $reading->fillPercent();
    $capacityMark = $reading->capacityMarkerPercent();
    $recommendedMark = $reading->recommendedMarkerPercent();
    $showMarkers = $reading->overflow > 0;
@endphp

<div @class([
        'relative w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-700',
        'h-2' => ! $thick,
        'h-3.5' => $thick,
    ])
    role="progressbar"
    aria-valuenow="{{ (int) round($fill) }}"
    aria-valuemin="0"
    aria-valuemax="100"
    @if ($ariaLabel !== '') aria-label="{{ $ariaLabel }}" @endif>
    <div class="h-full rounded-full transition-all duration-500 {{ $reading->barColorClass($okBarClass) }}"
        style="width: {{ $fill }}%"></div>

    @if ($showMarkers)
        <span class="pointer-events-none absolute inset-y-0 w-0.5 bg-zinc-900/70 dark:bg-white/80"
            style="left: {{ $capacityMark }}%"
            title="Base {{ $reading->capacity }}"></span>
        <span class="pointer-events-none absolute inset-y-0 w-0.5 bg-sky-500"
            style="left: {{ $recommendedMark }}%"
            title="Aim {{ $reading->recommendedLimit() }}"></span>
    @endif
</div>
