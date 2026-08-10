<div class="flex h-full min-h-0 flex-1 flex-col gap-2" wire:poll.30s>
    <div class="flex shrink-0 items-center justify-between gap-3">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                <a href="{{ route('home') }}" class="text-xs text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200">← Home</a>
                <h1 class="text-base font-semibold tracking-tight text-zinc-900 dark:text-white sm:text-lg">
                    Car Park Current Capacities
                </h1>
                <span class="text-[11px] text-zinc-500 dark:text-zinc-400">
                    <span class="inline-flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>OK</span>
                    <span class="ms-2 inline-flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-orange-500"></span>Double park</span>
                    <span class="ms-2 inline-flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>Over max</span>
                    @if ($showExpected)
                        <span class="ms-2 inline-flex items-center gap-1"><span class="h-2 w-px bg-sky-500"></span>Aim</span>
                    @endif
                    <span class="ms-2 text-zinc-400">· 30s refresh</span>
                </span>
            </div>
            @unless ($showExpected)
                <p class="mt-0.5 text-[11px] text-sky-800 dark:text-sky-200">
                    Live only —
                    <a href="{{ route('login') }}" class="font-semibold underline underline-offset-2">Log in</a>
                    for Fri–Sun expected demand
                </p>
            @endunless
        </div>

        <div class="flex shrink-0 gap-1.5">
            @auth
                @can('car-parks.view')
                    <a href="{{ route('admin.car-parks') }}" wire:navigate
                        class="rounded-md border border-zinc-300 px-2.5 py-1 text-xs font-semibold text-zinc-800 dark:border-zinc-600 dark:text-zinc-100">Admin</a>
                @endcan
                @can('dashboard.view')
                    <a href="{{ route('dashboard') }}"
                        class="rounded-md bg-zinc-900 px-2.5 py-1 text-xs font-semibold text-white dark:bg-white dark:text-zinc-900">Dashboard</a>
                @endcan
            @else
                <a href="{{ route('login') }}"
                    class="rounded-md bg-zinc-900 px-2.5 py-1 text-xs font-semibold text-white dark:bg-white dark:text-zinc-900">Log in</a>
            @endauth
        </div>
    </div>

    {{-- Dashboard board: each park is a row; guests get Live only, signed-in get Live+Fri/Sat/Sun --}}
    <div class="flex min-h-0 flex-1 flex-col gap-1.5">
        @forelse ($parkCards as $card)
            @php
                $park = $card['park'];
                $overflow = $card['overflow'];
                $worst = $card['worst'];
                $dropOffTotal = $card['drop_off_total'];
                $dayCount = count($card['days']);
            @endphp
            <article @class([
                'flex min-h-0 flex-1 overflow-hidden rounded-xl border bg-white shadow-sm dark:bg-zinc-800',
                'border-zinc-200 dark:border-zinc-700' => $worst === 'ok',
                'border-orange-300 dark:border-orange-700' => $worst === 'overflow',
                'border-red-300 dark:border-red-700' => $worst === 'critical',
            ])>
                <div class="flex w-32 shrink-0 flex-col justify-center border-r border-zinc-100 px-3 dark:border-zinc-700/80 sm:w-40 lg:w-48">
                    <div class="flex items-center gap-2">
                        <div class="h-3 w-3 shrink-0 rounded-full ring-1 ring-zinc-200 dark:ring-zinc-600"
                            style="background-color: {{ $park->color }}"></div>
                        <h2 class="truncate text-sm font-semibold text-zinc-900 dark:text-white sm:text-base">{{ $park->name }}</h2>
                    </div>
                    <p class="mt-0.5 truncate text-[10px] text-zinc-500 dark:text-zinc-400 sm:text-[11px]">
                        @if ($overflow > 0)
                            Overflow {{ $overflow }} · Aim +{{ intdiv($overflow, 2) }}
                        @else
                            {{ $park->location ?: 'No overflow set' }}
                        @endif
                        @if ($showExpected && $dropOffTotal > 0)
                            · {{ $dropOffTotal }} drop-off
                        @endif
                    </p>
                </div>

                <div @class([
                    'grid min-h-0 flex-1 gap-1.5 p-1.5',
                    'grid-cols-1' => $dayCount === 1,
                    'grid-cols-2 lg:grid-cols-4' => $dayCount > 1,
                ])>
                    @foreach ($card['days'] as $day)
                        <x-car-park-capacity-cell
                            :reading="$day['reading']"
                            :label="$day['label']"
                            :mode="$day['mode']"
                            :drop-off="$day['drop_off']"
                            :tooltip="$day['tooltip']"
                            :aria-label="$park->name.' '.$day['label']"
                            compact
                        />
                    @endforeach
                </div>
            </article>
        @empty
            <div class="flex flex-1 items-center justify-center rounded-xl border border-dashed border-zinc-300 text-zinc-500 dark:border-zinc-600">
                No car parks configured yet.
            </div>
        @endforelse
    </div>
</div>
