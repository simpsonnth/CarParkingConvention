@props([
    'reading', // App\Support\CarParkCapacityReading
    'okBarClass' => 'bg-yellow-400 dark:bg-yellow-500',
    'widthClass' => 'w-28',
    'ariaLabel' => '',
])

@php
    /** @var \App\Support\CarParkCapacityReading $reading */
    $fill = $reading->fillPercent();
    $capacityMark = $reading->capacityMarkerPercent();
    $recommendedMark = $reading->recommendedMarkerPercent();
    $showMarkers = $reading->overflow > 0;
@endphp

<div {{ $attributes->class(['relative h-2 overflow-visible rounded-full bg-zinc-100 dark:bg-zinc-700', $widthClass]) }}
    role="progressbar"
    aria-valuenow="{{ (int) round($fill) }}"
    aria-valuemin="0"
    aria-valuemax="100"
    @if ($ariaLabel !== '') aria-label="{{ $ariaLabel }}" @endif>
    <div class="absolute inset-0 overflow-hidden rounded-full">
        <div class="h-full transition-all duration-500 {{ $reading->barColorClass($okBarClass) }}"
            style="width: {{ $fill }}%"></div>
    </div>

    @if ($showMarkers)
        {{-- Base capacity (end of normal spaces) --}}
        <span class="pointer-events-none absolute top-1/2 z-10 h-3 w-0.5 -translate-x-1/2 -translate-y-1/2 bg-zinc-700 dark:bg-zinc-200"
            style="left: {{ $capacityMark }}%"
            title="Base capacity {{ $reading->capacity }}"></span>

        {{-- Recommended: half of overflow --}}
        <span class="pointer-events-none absolute top-1/2 z-10 h-3.5 w-0.5 -translate-x-1/2 -translate-y-1/2 bg-sky-600 dark:bg-sky-400"
            style="left: {{ $recommendedMark }}%"
            title="Recommended max {{ $reading->recommendedLimit() }} (half overflow)"></span>
    @endif
</div>
