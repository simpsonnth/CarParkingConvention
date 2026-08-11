<div class="flex flex-col gap-4 lg:h-full lg:min-h-0 lg:flex-1 lg:gap-2" wire:poll.30s>
    <header class="shrink-0 space-y-3">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 space-y-1">
                <a href="{{ route('home') }}" class="inline-block text-sm text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200">← Home</a>
                <h1 class="text-xl font-semibold tracking-tight text-zinc-900 dark:text-white lg:text-lg">
                    Car Park Current Capacities
                </h1>
            </div>

            <div class="flex shrink-0 flex-wrap justify-end gap-2">
                @auth
                    @can('car-parks.view')
                        <a href="{{ route('admin.car-parks') }}" wire:navigate
                            class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-800 dark:border-zinc-600 dark:text-zinc-100 lg:rounded-md lg:px-2.5 lg:py-1 lg:text-xs">Admin</a>
                    @endcan
                    @can('dashboard.view')
                        <a href="{{ route('dashboard') }}"
                            class="rounded-lg bg-zinc-900 px-3 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900 lg:rounded-md lg:px-2.5 lg:py-1 lg:text-xs">Dashboard</a>
                    @endcan
                @else
                    <a href="{{ route('login') }}"
                        class="rounded-lg bg-zinc-900 px-3 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900 lg:rounded-md lg:px-2.5 lg:py-1 lg:text-xs">Log in</a>
                @endauth
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 text-xs text-zinc-500 dark:text-zinc-400">
            <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>OK</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-orange-500"></span>Double park</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-red-500"></span>Over max</span>
            @if ($showExpected)
                <span class="inline-flex items-center gap-1.5"><span class="h-3 w-0.5 bg-sky-500"></span>Aim</span>
            @endif
            <span class="text-zinc-400">· 30s refresh</span>
        </div>

        @unless ($showExpected)
            <p class="rounded-lg bg-sky-50 px-3 py-2 text-xs text-sky-900 dark:bg-sky-950/50 dark:text-sky-100">
                Live only —
                <a href="{{ route('login') }}" class="font-semibold underline underline-offset-2">Log in</a>
                for Fri–Sun expected demand
            </p>
        @endunless
    </header>

    <div class="flex flex-col gap-4 lg:min-h-0 lg:flex-1 lg:gap-1.5">
        @forelse ($parkCards as $card)
            @php
                $park = $card['park'];
                $overflow = $card['overflow'];
                $worst = $card['worst'];
                $dropOffTotal = $card['drop_off_total'];
                $dayCount = count($card['days']);
            @endphp
            <article @class([
                'overflow-hidden rounded-2xl border bg-white shadow-sm dark:bg-zinc-800 lg:flex lg:min-h-0 lg:flex-1 lg:rounded-xl',
                'border-zinc-200 dark:border-zinc-700' => $worst === 'ok',
                'border-orange-300 dark:border-orange-700' => $worst === 'overflow',
                'border-red-300 dark:border-red-700' => $worst === 'critical',
            ])>
                <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-700/80 lg:flex lg:w-44 lg:shrink-0 lg:flex-col lg:justify-center lg:border-b-0 lg:border-r lg:px-3 lg:py-2 xl:w-52">
                    <div class="flex items-center gap-2.5">
                        <div class="h-3.5 w-3.5 shrink-0 rounded-full ring-1 ring-zinc-200 dark:ring-zinc-600 lg:h-3 lg:w-3"
                            style="background-color: {{ $park->color }}"></div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white lg:truncate lg:text-sm xl:text-base">{{ $park->name }}</h2>
                    </div>
                    <p class="mt-1 text-sm leading-snug text-zinc-500 dark:text-zinc-400 lg:mt-0.5 lg:truncate lg:text-[10px] xl:text-[11px]">
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

                {{-- Phone / tablet: full-width day rows. Large screens: board cells --}}
                <div class="lg:hidden">
                    <ul class="divide-y divide-zinc-100 dark:divide-zinc-700/80">
                        @foreach ($card['days'] as $day)
                            @php
                                /** @var \App\Support\CarParkCapacityReading $reading */
                                $reading = $day['reading'];
                                $usedSuffix = $day['mode'] === 'live' ? ' in' : '';
                            @endphp
                            <li class="px-4 py-3" wire:key="park-{{ $park->id }}-{{ $day['key'] }}-mobile">
                                <div class="mb-1.5 flex items-baseline justify-between gap-3">
                                    <span class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ $day['label'] }}</span>
                                    <span @class([
                                        'text-lg font-semibold tabular-nums tracking-tight',
                                        $reading->ratioTextClass(),
                                    ])>{{ $reading->used }}{{ $usedSuffix }} / {{ $reading->capacity }}</span>
                                </div>

                                @if ($reading->shortStatusLabel() || ($day['drop_off'] ?? 0) > 0)
                                    <div class="mb-2 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs">
                                        @if ($reading->shortStatusLabel())
                                            <span @class(['font-medium', $reading->statusTextClass()])>
                                                {{ $reading->shortStatusLabel() }}
                                            </span>
                                        @endif
                                        @if (($day['drop_off'] ?? 0) > 0)
                                            <span class="font-medium text-amber-700 dark:text-amber-300">+{{ $day['drop_off'] }} drop-off</span>
                                        @endif
                                    </div>
                                @endif

                                <div @if (($day['tooltip'] ?? '') !== '') title="{{ $day['tooltip'] }}" @endif>
                                    <x-car-park-capacity-meter
                                        :reading="$reading"
                                        :aria-label="$park->name.' '.$day['label']"
                                        thick
                                    />
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div @class([
                    'hidden lg:grid lg:min-h-0 lg:flex-1 lg:gap-1.5 lg:p-1.5',
                    'lg:grid-cols-1' => $dayCount === 1,
                    'lg:grid-cols-4' => $dayCount > 1,
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
                            class="lg:min-h-0 lg:flex-1"
                            wire:key="park-{{ $park->id }}-{{ $day['key'] }}-desktop"
                        />
                    @endforeach
                </div>
            </article>
        @empty
            <div class="flex flex-1 items-center justify-center rounded-xl border border-dashed border-zinc-300 px-4 py-12 text-center text-zinc-500 dark:border-zinc-600">
                No car parks configured yet.
            </div>
        @endforelse
    </div>
</div>
