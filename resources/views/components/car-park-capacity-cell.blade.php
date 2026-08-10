@props([
    'reading', // App\Support\CarParkCapacityReading
    'mode' => 'day', // day|live
    'tooltip' => '',
    'ariaLabel' => '',
    'dropOff' => 0,
])

@php
    /** @var \App\Support\CarParkCapacityReading $reading */
    $ratioText = $mode === 'live'
        ? $reading->used.' in / '.$reading->capacity
        : $reading->used.' / '.$reading->capacity;
@endphp

<div {{ $attributes->class(['min-w-[10.5rem] space-y-1.5']) }}>
    <p @class([
        'text-lg font-semibold tabular-nums tracking-tight leading-none',
        $reading->ratioTextClass(),
    ])>{{ $ratioText }}</p>

    @if ($reading->overflow > 0)
        <p class="text-[11px] leading-snug text-zinc-500 dark:text-zinc-400">
            Aim {{ $reading->recommendedLimit() }}
            <span class="text-zinc-400 dark:text-zinc-500">·</span>
            Max {{ $reading->hardLimit() }}
        </p>
    @endif

    @if ($reading->shortStatusLabel())
        <span @class([
            'inline-flex max-w-full items-center rounded-md px-1.5 py-0.5 text-[11px] font-semibold ring-1 ring-inset',
            $reading->statusChipClass(),
        ])>
            {{ $reading->shortStatusLabel() }}
        </span>
    @endif

    @if ($dropOff > 0)
        <span class="block text-[11px] font-semibold text-amber-800 dark:text-amber-200">
            +{{ $dropOff }} drop-off
        </span>
    @endif

    <x-car-park-capacity-meter
        :reading="$reading"
        ok-bar-class="bg-emerald-500"
        :show-zone-labels="$reading->overflow > 0"
        :aria-label="$ariaLabel !== '' ? $ariaLabel : $tooltip"
    />
</div>
