@props([
    'reading', // App\Support\CarParkCapacityReading
    'label' => '',
    'mode' => 'day', // day|live
    'tooltip' => '',
    'ariaLabel' => '',
    'dropOff' => 0,
])

@php
    /** @var \App\Support\CarParkCapacityReading $reading */
    $usedSuffix = $mode === 'live' ? ' in' : '';
@endphp

<div {{ $attributes->class(['rounded-lg bg-zinc-50 px-3 py-3 dark:bg-zinc-900/50']) }}>
    @if ($label !== '')
        <p class="text-[11px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ $label }}</p>
    @endif

    <p class="mt-1">
        <span @class([
            'text-2xl font-semibold tabular-nums tracking-tight',
            $reading->ratioTextClass(),
        ])>{{ $reading->used }}{{ $usedSuffix }} / {{ $reading->capacity }}</span>
    </p>

    @if ($reading->shortStatusLabel())
        <p @class(['mt-1 text-xs font-medium', $reading->statusTextClass()])>
            {{ $reading->shortStatusLabel() }}
        </p>
    @elseif ($reading->overflow > 0)
        <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">
            max {{ $reading->hardLimit() }}
        </p>
    @else
        <p class="mt-1 text-xs text-transparent select-none" aria-hidden="true">—</p>
    @endif

    @if ($dropOff > 0)
        <p class="mt-0.5 text-xs font-medium text-amber-700 dark:text-amber-300">+{{ $dropOff }} drop-off</p>
    @endif

    <div class="mt-2.5" @if ($tooltip !== '') title="{{ $tooltip }}" @endif>
        <x-car-park-capacity-meter
            :reading="$reading"
            :aria-label="$ariaLabel !== '' ? $ariaLabel : $tooltip"
        />
    </div>
</div>
