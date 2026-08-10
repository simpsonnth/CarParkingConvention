<div class="mx-auto max-w-5xl space-y-6" wire:poll.30s>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <a href="{{ route('home') }}" class="text-sm text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200">
                ← Home
            </a>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white sm:text-3xl">
                Car Park Current Capacities
            </h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                @if ($showExpected)
                    Live clock-ins plus registered demand for Fri–Sun.
                @else
                    Live clock-ins only. Log in to see expected (registered) demand by day.
                @endif
            </p>
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-2">
            @auth
                @can('car-parks.view')
                    <a href="{{ route('admin.car-parks') }}" wire:navigate
                        class="inline-flex items-center justify-center rounded-lg border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-100 dark:hover:bg-zinc-800">
                        Admin parks
                    </a>
                @endcan
                @can('dashboard.view')
                    <a href="{{ route('dashboard') }}"
                        class="inline-flex items-center justify-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:opacity-90 dark:bg-white dark:text-zinc-900">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('home') }}"
                        class="inline-flex items-center justify-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:opacity-90 dark:bg-white dark:text-zinc-900">
                        Home
                    </a>
                @endcan
            @else
                <a href="{{ route('login') }}"
                    class="inline-flex items-center justify-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-90 dark:bg-white dark:text-zinc-900">
                    Log in
                </a>
            @endauth
        </div>
    </div>

    <p class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-zinc-500 dark:text-zinc-400">
        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>OK</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-orange-500"></span>Double park</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-red-500"></span>Over max</span>
        @if ($showExpected)
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-px bg-sky-500"></span>Aim (½ overflow)</span>
        @endif
        <span class="text-zinc-400 dark:text-zinc-500">Updates every 30 seconds</span>
    </p>

    @unless ($showExpected)
        <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800/60 dark:text-zinc-300">
            You’re viewing live occupancy only.
            <a href="{{ route('login') }}" class="font-semibold text-zinc-900 underline underline-offset-2 dark:text-white">Log in</a>
            to also see expected registrations by day.
        </div>
    @endunless

    <div class="space-y-4">
        @forelse ($parkCards as $card)
            @php
                /** @var \App\Models\CarPark $park */
                $park = $card['park'];
                $overflow = $card['overflow'];
                $worst = $card['worst'];
                $dropOffTotal = $card['drop_off_total'];
            @endphp
            <article @class([
                'rounded-xl border bg-white shadow-sm dark:bg-zinc-800',
                'border-zinc-200 dark:border-zinc-700' => $worst === 'ok',
                'border-orange-300 dark:border-orange-700' => $worst === 'overflow',
                'border-red-300 dark:border-red-700' => $worst === 'critical',
            ])>
                <div class="flex items-center gap-3 border-b border-zinc-100 px-4 py-3 dark:border-zinc-700/80">
                    <div class="h-3.5 w-3.5 shrink-0 rounded-full ring-1 ring-zinc-200 dark:ring-zinc-600"
                        style="background-color: {{ $park->color }}"></div>
                    <div class="min-w-0">
                        <h2 class="truncate text-base font-semibold text-zinc-900 dark:text-white">{{ $park->name }}</h2>
                        <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $park->location ?: 'No location' }}
                            @if ($overflow > 0)
                                <span class="text-zinc-300 dark:text-zinc-600">·</span>
                                Overflow {{ $overflow }}
                                <span class="text-zinc-300 dark:text-zinc-600">·</span>
                                Aim +{{ intdiv($overflow, 2) }}
                            @endif
                            @if ($showExpected && $dropOffTotal > 0)
                                <span class="text-zinc-300 dark:text-zinc-600">·</span>
                                <span class="font-medium text-amber-700 dark:text-amber-300">{{ $dropOffTotal }} drop-off</span>
                            @endif
                        </p>
                    </div>
                </div>

                <div @class([
                    'grid gap-2 p-3',
                    'sm:grid-cols-2 lg:grid-cols-4' => $showExpected,
                    'sm:grid-cols-1' => ! $showExpected,
                ])>
                    @foreach ($card['days'] as $day)
                        <x-car-park-capacity-cell
                            :reading="$day['reading']"
                            :label="$day['label']"
                            :mode="$day['mode']"
                            :drop-off="$day['drop_off']"
                            :tooltip="$day['tooltip']"
                            :aria-label="$park->name.' '.$day['label']"
                        />
                    @endforeach
                </div>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-zinc-300 px-6 py-12 text-center text-zinc-500 dark:border-zinc-600">
                No car parks configured yet.
            </div>
        @endforelse
    </div>
</div>
