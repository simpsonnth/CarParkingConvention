@props([
    'reading', // App\Support\CarParkCapacityReading
    'okBarClass' => 'bg-emerald-500',
    'ariaLabel' => '',
])

@php
    /** @var \App\Support\CarParkCapacityReading $reading */
    $fill = $reading->fillPercent();
    $capacityMark = $reading->capacityMarkerPercent();
    $recommendedMark = $reading->recommendedMarkerPercent();
    $showMarkers = $reading->overflow > 0;
@endphp

<div class="relative h-2 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-700"
    role="progressbar"
    aria-valuenow="{{ (int) round($fill) }}"
    aria-valuemin="0"
    aria-valuemax="100"
    @if ($ariaLabel !== '') aria-label="{{ $ariaLabel }}" @endif>
    <div class="h-full rounded-full transition-all duration-500 {{ $reading->barColorClass($okBarClass) }}"
        style="width: {{ $fill }}%"></div>

    @if ($showMarkers)
        <span class="pointer-events-none absolute inset-y-0 w-px bg-zinc-900/70 dark:bg-white/80"
            style="left: {{ $capacityMark }}%"
            title="Base {{ $reading->capacity }}"></span>
        <span class="pointer-events-none absolute inset-y-0 w-px bg-sky-500"
            style="left: {{ $recommendedMark }}%"
            title="Aim {{ $reading->recommendedLimit() }}"></span>
    @endif
</div>
