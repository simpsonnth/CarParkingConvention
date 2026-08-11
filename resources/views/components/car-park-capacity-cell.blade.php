@props([
    'reading', // App\Support\CarParkCapacityReading
    'label' => '',
    'mode' => 'day', // day|live
    'tooltip' => '',
    'ariaLabel' => '',
    'dropOff' => 0,
    'compact' => false,
])

@php
    /** @var \App\Support\CarParkCapacityReading $reading */
    $usedSuffix = $mode === 'live' ? ' in' : '';
@endphp

<div {{ $attributes->class([
    'rounded-lg bg-zinc-50 dark:bg-zinc-900/50',
    'px-3 py-3' => ! $compact,
    'flex min-h-0 flex-col justify-center px-3 py-2.5 md:flex-1 md:px-2 md:py-1.5' => $compact,
]) }}>
    @if ($label !== '')
        <p @class([
            'font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400',
            'text-[11px]' => ! $compact,
            'text-[11px] leading-none md:text-[10px]' => $compact,
        ])>{{ $label }}</p>
    @endif

    <p @class(['mt-1' => ! $compact, 'mt-1 md:mt-0.5' => $compact])>
        <span @class([
            'font-semibold tabular-nums tracking-tight',
            'text-2xl' => ! $compact,
            'text-xl sm:text-xl lg:text-2xl' => $compact,
            $reading->ratioTextClass(),
        ])>{{ $reading->used }}{{ $usedSuffix }} / {{ $reading->capacity }}</span>
    </p>

    @if ($reading->shortStatusLabel())
        <p @class([
            'font-medium',
            'mt-1 text-xs' => ! $compact,
            'mt-0.5 text-[11px] leading-tight' => $compact,
            $reading->statusTextClass(),
        ])>
            {{ $reading->shortStatusLabel() }}
        </p>
    @elseif ($reading->overflow > 0 && ! $compact)
        <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">
            max {{ $reading->hardLimit() }}
        </p>
    @elseif (! $compact)
        <p class="mt-1 text-xs text-transparent select-none" aria-hidden="true">—</p>
    @endif

    @if ($dropOff > 0)
        <p @class([
            'font-medium text-amber-700 dark:text-amber-300',
            'mt-0.5 text-xs' => ! $compact,
            'text-[10px] leading-tight' => $compact,
        ])>+{{ $dropOff }} drop-off</p>
    @endif

    <div @class(['mt-2.5' => ! $compact, 'mt-auto pt-2' => $compact]) @if ($tooltip !== '') title="{{ $tooltip }}" @endif>
        <x-car-park-capacity-meter
            :reading="$reading"
            :aria-label="$ariaLabel !== '' ? $ariaLabel : $tooltip"
            :thick="$compact"
        />
    </div>
</div>
