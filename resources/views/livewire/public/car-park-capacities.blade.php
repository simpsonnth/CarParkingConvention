<div class="flex min-h-0 flex-1 flex-col gap-2" wire:poll.30s>
    <div class="flex shrink-0 flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                <a href="{{ route('home') }}" class="text-xs text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200">
                    ← Home
                </a>
                <h1 class="text-lg font-semibold tracking-tight text-zinc-900 dark:text-white sm:text-xl">
                    Car Park Current Capacities
                </h1>
            </div>
            <p class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-zinc-500 dark:text-zinc-400">
                <span class="inline-flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>OK</span>
                <span class="inline-flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-orange-500"></span>Double park</span>
                <span class="inline-flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>Over max</span>
                @if ($showExpected)
                    <span class="inline-flex items-center gap-1"><span class="h-2 w-px bg-sky-500"></span>Aim</span>
                @endif
                <span class="text-zinc-400 dark:text-zinc-500">Auto-refresh 30s</span>
                @unless ($showExpected)
                    <span class="text-sky-800 dark:text-sky-200">
                        Live only —
                        <a href="{{ route('login') }}" class="font-semibold underline underline-offset-2">Log in</a>
                        for expected demand
                    </span>
                @endunless
            </p>
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-1.5">
            @auth
                @can('car-parks.view')
                    <a href="{{ route('admin.car-parks') }}" wire:navigate
                        class="inline-flex items-center justify-center rounded-md border border-zinc-300 px-3 py-1.5 text-xs font-semibold text-zinc-800 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-100 dark:hover:bg-zinc-800">
                        Admin parks
                    </a>
                @endcan
                @can('dashboard.view')
                    <a href="{{ route('dashboard') }}"
                        class="inline-flex items-center justify-center rounded-md bg-zinc-900 px-3 py-1.5 text-xs font-semibold text-white hover:opacity-90 dark:bg-white dark:text-zinc-900">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('home') }}"
                        class="inline-flex items-center justify-center rounded-md bg-zinc-900 px-3 py-1.5 text-xs font-semibold text-white hover:opacity-90 dark:bg-white dark:text-zinc-900">
                        Home
                    </a>
                @endcan
            @else
                <a href="{{ route('login') }}"
                    class="inline-flex items-center justify-center rounded-md bg-zinc-900 px-3 py-1.5 text-xs font-semibold text-white hover:opacity-90 dark:bg-white dark:text-zinc-900">
                    Log in
                </a>
            @endauth
        </div>
    </div>

    <div class="flex min-h-0 flex-1 flex-col gap-1.5">
        @forelse ($parkCards as $card)
            @php
                /** @var \App\Models\CarPark $park */
                $park = $card['park'];
                $overflow = $card['overflow'];
                $worst = $card['worst'];
                $dropOffTotal = $card['drop_off_total'];
                $dayCount = max(1, count($card['days']));
            @endphp
            <article @class([
                'flex min-h-0 flex-1 flex-col overflow-hidden rounded-lg border bg-white dark:bg-zinc-800 sm:flex-row',
                'border-zinc-200 dark:border-zinc-700' => $worst === 'ok',
                'border-orange-300 dark:border-orange-700' => $worst === 'overflow',
                'border-red-300 dark:border-red-700' => $worst === 'critical',
            ])>
                <div class="flex w-full shrink-0 items-center gap-2 border-b border-zinc-100 px-3 py-2 sm:w-44 sm:border-b-0 sm:border-r dark:border-zinc-700/80 lg:w-52">
                    <div class="h-3 w-3 shrink-0 rounded-full ring-1 ring-zinc-200 dark:ring-zinc-600"
                        style="background-color: {{ $park->color }}"></div>
                    <div class="min-w-0">
                        <h2 class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $park->name }}</h2>
                        <p class="truncate text-[10px] leading-tight text-zinc-500 dark:text-zinc-400">
                            @if ($overflow > 0)
                                Overflow {{ $overflow }} · Aim +{{ intdiv($overflow, 2) }}
                            @else
                                {{ $park->location ?: 'No overflow' }}
                            @endif
                            @if ($showExpected && $dropOffTotal > 0)
                                · <span class="font-medium text-amber-700 dark:text-amber-300">{{ $dropOffTotal }} drop-off</span>
                            @endif
                        </p>
                    </div>
                </div>

                <div @class([
                    'grid min-h-0 flex-1 gap-1 p-1.5',
                    'grid-cols-2 lg:grid-cols-4' => $showExpected && $dayCount > 1,
                    'grid-cols-1' => ! $showExpected || $dayCount === 1,
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
            <div class="flex flex-1 items-center justify-center rounded-lg border border-dashed border-zinc-300 text-zinc-500 dark:border-zinc-600">
                No car parks configured yet.
            </div>
        @endforelse
    </div>
</div>
