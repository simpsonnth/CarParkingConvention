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

    @if ($showExpected)
        {{-- Logged-in board: rows share the full viewport height --}}
        <div class="flex min-h-0 flex-1 flex-col gap-1">
            @forelse ($parkCards as $card)
                @php
                    $park = $card['park'];
                    $overflow = $card['overflow'];
                    $worst = $card['worst'];
                    $dropOffTotal = $card['drop_off_total'];
                @endphp
                <article @class([
                    'flex min-h-0 flex-1 overflow-hidden rounded-lg border bg-white dark:bg-zinc-800',
                    'border-zinc-200 dark:border-zinc-700' => $worst === 'ok',
                    'border-orange-300 dark:border-orange-700' => $worst === 'overflow',
                    'border-red-300 dark:border-red-700' => $worst === 'critical',
                ])>
                    <div class="flex w-36 shrink-0 flex-col justify-center border-r border-zinc-100 px-2.5 dark:border-zinc-700/80 lg:w-44">
                        <div class="flex items-center gap-2">
                            <div class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $park->color }}"></div>
                            <h2 class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $park->name }}</h2>
                        </div>
                        <p class="mt-0.5 truncate text-[10px] text-zinc-500 dark:text-zinc-400">
                            @if ($overflow > 0)
                                Overflow {{ $overflow }} · Aim +{{ intdiv($overflow, 2) }}
                            @endif
                            @if ($dropOffTotal > 0)
                                · {{ $dropOffTotal }} drop-off
                            @endif
                        </p>
                    </div>
                    <div class="grid min-h-0 flex-1 grid-cols-2 gap-1 p-1 lg:grid-cols-4">
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
                <div class="flex flex-1 items-center justify-center text-zinc-500">No car parks configured yet.</div>
            @endforelse
        </div>
    @else
        {{-- Guest: dense full-width live table — all parks visible without tall empty cards --}}
        <div class="min-h-0 flex-1 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="h-full w-full table-fixed border-collapse text-left">
                <thead class="bg-zinc-100 text-[11px] uppercase tracking-wide text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                    <tr>
                        <th class="w-[28%] px-3 py-2 font-medium">Car park</th>
                        <th class="px-3 py-2 font-medium">Live (clocked in)</th>
                        <th class="hidden w-[34%] px-3 py-2 font-medium sm:table-cell">Fill</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($parkCards as $card)
                        @php
                            $park = $card['park'];
                            $overflow = $card['overflow'];
                            $live = $card['days'][0]['reading'];
                        @endphp
                        <tr class="bg-white dark:bg-zinc-800">
                            <td class="px-3 py-2 align-middle">
                                <div class="flex items-center gap-2">
                                    <div class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $park->color }}"></div>
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $park->name }}</div>
                                        @if ($overflow > 0)
                                            <div class="truncate text-[10px] text-zinc-500 dark:text-zinc-400">
                                                Overflow {{ $overflow }} · Aim +{{ intdiv($overflow, 2) }} · Max {{ $live->hardLimit() }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-2 align-middle">
                                <div @class(['text-xl font-semibold tabular-nums', $live->ratioTextClass()])>
                                    {{ $live->used }} in / {{ $live->capacity }}
                                </div>
                                @if ($live->shortStatusLabel())
                                    <div @class(['text-xs font-medium', $live->statusTextClass()])>{{ $live->shortStatusLabel() }}</div>
                                @endif
                            </td>
                            <td class="hidden px-3 py-2 align-middle sm:table-cell">
                                <x-car-park-capacity-meter
                                    :reading="$live"
                                    :aria-label="$park->name.' live'"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-3 py-8 text-center text-zinc-500">No car parks configured yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
