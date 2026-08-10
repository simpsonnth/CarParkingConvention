@props([
    'reading', // App\Support\CarParkCapacityReading
    'okBarClass' => 'bg-emerald-500',
    'widthClass' => 'w-full min-w-[9rem]',
    'ariaLabel' => '',
    'showZoneLabels' => false,
])

@php
    /** @var \App\Support\CarParkCapacityReading $reading */
    $fill = $reading->fillPercent();
    $capacityMark = $reading->capacityMarkerPercent();
    $recommendedMark = $reading->recommendedMarkerPercent();
    $showZones = $reading->overflow > 0;
    $baseWidth = $showZones ? $capacityMark : 100;
    $aimWidth = $showZones ? max(0, $recommendedMark - $capacityMark) : 0;
    $maxWidth = $showZones ? max(0, 100 - $recommendedMark) : 0;
@endphp

<div {{ $attributes->class(['space-y-1', $widthClass]) }}>
    <div class="relative h-3 overflow-visible rounded-md bg-zinc-200 dark:bg-zinc-700"
        role="progressbar"
        aria-valuenow="{{ (int) round($fill) }}"
        aria-valuemin="0"
        aria-valuemax="100"
        @if ($ariaLabel !== '') aria-label="{{ $ariaLabel }}" @endif>

        @if ($showZones)
            {{-- Zone track: base | aim half | rest of overflow --}}
            <div class="absolute inset-0 flex overflow-hidden rounded-md">
                <div class="h-full bg-zinc-200 dark:bg-zinc-600" style="width: {{ $baseWidth }}%"></div>
                <div class="h-full bg-orange-200/80 dark:bg-orange-900/50" style="width: {{ $aimWidth }}%"></div>
                <div class="h-full bg-red-200/70 dark:bg-red-900/40" style="width: {{ $maxWidth }}%"></div>
            </div>
        @endif

        <div class="absolute inset-0 overflow-hidden rounded-md">
            <div class="h-full transition-all duration-500 {{ $reading->barColorClass($okBarClass) }}"
                style="width: {{ $fill }}%"></div>
        </div>

        @if ($showZones)
            <span class="pointer-events-none absolute top-1/2 z-10 h-4 w-0.5 -translate-x-1/2 -translate-y-1/2 rounded-full bg-zinc-900 dark:bg-white"
                style="left: {{ $capacityMark }}%"
                title="Base capacity {{ $reading->capacity }}"></span>
            <span class="pointer-events-none absolute top-1/2 z-10 h-4 w-0.5 -translate-x-1/2 -translate-y-1/2 rounded-full bg-sky-600 dark:bg-sky-300"
                style="left: {{ $recommendedMark }}%"
                title="Aim {{ $reading->recommendedLimit() }}"></span>
        @endif
    </div>

    @if ($showZones && $showZoneLabels)
        <div class="relative h-3 text-[10px] leading-none text-zinc-500 dark:text-zinc-400">
            <span class="absolute left-0">0</span>
            <span class="absolute -translate-x-1/2 font-medium text-zinc-700 dark:text-zinc-200"
                style="left: {{ $capacityMark }}%">{{ $reading->capacity }}</span>
            <span class="absolute -translate-x-1/2 font-medium text-sky-700 dark:text-sky-300"
                style="left: {{ $recommendedMark }}%">aim {{ $reading->recommendedLimit() }}</span>
            <span class="absolute right-0">max {{ $reading->hardLimit() }}</span>
        </div>
    @endif
</div>
