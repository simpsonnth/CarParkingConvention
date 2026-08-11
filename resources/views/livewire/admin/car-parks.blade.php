<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Car Parks</flux:heading>
            <flux:subheading>Live occupancy and registered demand by day</flux:subheading>
        </div>
        @can('car-parks.manage')
            <flux:button variant="primary" wire:click="create" class="w-full sm:w-auto">Add Car Park</flux:button>
        @endcan
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search car parks..."
            class="w-full min-w-0 sm:max-w-xs" />
        <p class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-zinc-500 dark:text-zinc-400">
            <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>OK</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-orange-500"></span>Double park</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-red-500"></span>Over max</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-px bg-sky-500"></span>Aim (½ overflow)</span>
        </p>
    </div>

    @if (($dropOffCoachTotal ?? 0) > 0)
        <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm dark:border-amber-600 dark:bg-amber-950/40">
            <p class="font-medium text-amber-950 dark:text-amber-50">
                {{ $dropOffCoachTotal }} drop-off {{ \Illuminate\Support\Str::plural('coach', $dropOffCoachTotal) }} (not counted in capacity)
            </p>
            <a href="{{ route('admin.coaches') }}" wire:navigate
                class="font-semibold text-amber-900 underline underline-offset-2 dark:text-amber-100">
                View coaches
            </a>
        </div>
    @endif

    <div class="space-y-4">
        @forelse ($carParks as $park)
            @php
                $overflow = $park->overflowCapacity();
                $parked = (int) $park->current_occupancy;
                $liveCapacity = $park->capacityForToday();
                $liveReading = \App\Support\CarParkCapacityReading::make($parked, $liveCapacity, $overflow);
                $dropOffTotal = (int) ($park->drop_off_coaches ?? 0);

                $days = [
                    [
                        'key' => 'live',
                        'label' => 'Live',
                        'mode' => 'live',
                        'reading' => $liveReading,
                        'drop_off' => 0,
                        'tooltip' => "{$parked} clocked in / {$liveCapacity} today".$liveReading->tooltipExtra(),
                    ],
                    [
                        'key' => 'friday',
                        'label' => 'Friday',
                        'mode' => 'day',
                        'reading' => \App\Support\CarParkCapacityReading::make((int) $park->assigned_friday, (int) $park->capacity_friday, $overflow),
                        'drop_off' => (int) ($park->drop_off_friday ?? 0),
                    ],
                    [
                        'key' => 'saturday',
                        'label' => 'Saturday',
                        'mode' => 'day',
                        'reading' => \App\Support\CarParkCapacityReading::make((int) $park->assigned_saturday, (int) $park->capacity_saturday, $overflow),
                        'drop_off' => (int) ($park->drop_off_saturday ?? 0),
                    ],
                    [
                        'key' => 'sunday',
                        'label' => 'Sunday',
                        'mode' => 'day',
                        'reading' => \App\Support\CarParkCapacityReading::make((int) $park->assigned_sunday, (int) $park->capacity_sunday, $overflow),
                        'drop_off' => (int) ($park->drop_off_sunday ?? 0),
                    ],
                ];

                foreach ($days as $i => $day) {
                    if (($day['key'] ?? '') === 'live') {
                        continue;
                    }
                    $r = $day['reading'];
                    $days[$i]['tooltip'] = "{$r->used} registered / {$r->capacity} capacity"
                        .$r->tooltipExtra()
                        .($day['drop_off'] > 0 ? " · {$day['drop_off']} drop-off" : '');
                }

                $worst = collect($days)->map(fn ($d) => $d['reading']->zone())->contains('critical')
                    ? 'critical'
                    : (collect($days)->map(fn ($d) => $d['reading']->zone())->contains('overflow') ? 'overflow' : 'ok');
            @endphp

            <article @class([
                'rounded-xl border bg-white shadow-sm dark:bg-zinc-800',
                'border-zinc-200 dark:border-zinc-700' => $worst === 'ok',
                'border-orange-300 dark:border-orange-700' => $worst === 'overflow',
                'border-red-300 dark:border-red-700' => $worst === 'critical',
            ])>
                <div class="flex flex-col gap-3 border-b border-zinc-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-zinc-700/80">
                    <div class="flex min-w-0 items-center gap-3">
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
                                @if ($dropOffTotal > 0)
                                    <span class="text-zinc-300 dark:text-zinc-600">·</span>
                                    <span class="font-medium text-amber-700 dark:text-amber-300">{{ $dropOffTotal }} drop-off</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        <flux:button href="{{ route('admin.car-parks.show', $park) }}" size="sm" variant="ghost" icon="eye" wire:navigate>
                            View
                        </flux:button>
                        @can('car-parks.manage')
                            <flux:button wire:click="edit({{ $park->id }})" size="sm" variant="ghost" icon="pencil">Edit</flux:button>
                            <flux:button wire:click="delete({{ $park->id }})" size="sm" variant="ghost" icon="trash"
                                class="text-red-600 hover:text-red-700 dark:text-red-400">Delete</flux:button>
                        @endcan
                    </div>
                </div>

                <div class="grid gap-2 p-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($days as $day)
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
                No car parks found.
            </div>
        @endforelse
    </div>

    @if ($carParks->total() > 0)
        @php
            $hasAnyOver = collect($capacityOverTotals)->contains(fn ($c) => ($c['over_base'] ?? 0) > 0 || ($c['over_hard'] ?? 0) > 0);
        @endphp
        @if ($hasAnyOver)
            <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900/50">
                <p class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Totals past base</p>
                <div class="mt-2 grid gap-3 sm:grid-cols-4">
                    @foreach (['live' => 'Live', 'friday' => 'Friday', 'saturday' => 'Saturday', 'sunday' => 'Sunday'] as $dayKey => $dayLabel)
                        @php
                            $cell = $capacityOverTotals[$dayKey] ?? ['over_base' => 0, 'over_hard' => 0];
                            $overBase = (int) ($cell['over_base'] ?? 0);
                            $overHard = (int) ($cell['over_hard'] ?? 0);
                        @endphp
                        <div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $dayLabel }}</p>
                            @if ($overHard > 0)
                                <p class="text-sm font-semibold text-red-700 dark:text-red-300">Over max +{{ $overHard }}</p>
                            @elseif ($overBase > 0)
                                <p class="text-sm font-semibold text-orange-700 dark:text-orange-300">Double park +{{ $overBase }}</p>
                            @else
                                <p class="text-sm text-zinc-400">—</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif

    <div>
        {{ $carParks->links() }}
    </div>

    <flux:modal wire:model="modalOpen" class="w-[calc(100vw-2rem)] max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $carParkId ? 'Edit Car Park' : 'Create Car Park' }}</flux:heading>
                <flux:subheading>Capacity, overflow, and park details.</flux:subheading>
            </div>

            <div class="space-y-4">
                <div class="space-y-2">
                    <label for="name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Name</label>
                    <input type="text" wire:model="name" id="name" placeholder="e.g. North Car Park"
                        class="block w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm placeholder-zinc-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" />
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="space-y-2">
                        <label for="capacityFriday" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Friday</label>
                        <input type="number" wire:model="capacityFriday" id="capacityFriday" placeholder="200" min="1"
                            class="block w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" />
                        @error('capacityFriday')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="space-y-2">
                        <label for="capacitySaturday" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Saturday</label>
                        <input type="number" wire:model="capacitySaturday" id="capacitySaturday" placeholder="200" min="1"
                            class="block w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" />
                        @error('capacitySaturday')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="space-y-2">
                        <label for="capacitySunday" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Sunday</label>
                        <input type="number" wire:model="capacitySunday" id="capacitySunday" placeholder="200" min="1"
                            class="block w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" />
                        @error('capacitySunday')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="overflowCapacity" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Double-park overflow</label>
                    <input type="number" wire:model="overflowCapacity" id="overflowCapacity" placeholder="0" min="0"
                        class="block w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" />
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Extra spaces beyond base capacity. Aim marker is half of this; red is past base + overflow.
                    </p>
                    @error('overflowCapacity')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label for="location" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Location</label>
                    <input type="text" wire:model="location" id="location" placeholder="Optional description"
                        class="block w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" />
                </div>

                <div class="space-y-2">
                    <label for="postcode" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Postcode</label>
                    <input type="text" wire:model="postcode" id="postcode" placeholder="e.g. Twickenham TW2 7PS"
                        class="block w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" />
                    @error('postcode')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <label for="latitude" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Latitude</label>
                        <input type="text" inputmode="decimal" wire:model="latitude" id="latitude" placeholder="e.g. 51.4495814"
                            class="block w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm font-mono dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" />
                        @error('latitude')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="space-y-2">
                        <label for="longitude" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Longitude</label>
                        <input type="text" inputmode="decimal" wire:model="longitude" id="longitude" placeholder="e.g. -0.3505310"
                            class="block w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm font-mono dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" />
                        @error('longitude')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 -mt-2">
                    Used for Visiting Guest handout “navigate back to your car” QR codes.
                </p>

                <div class="space-y-2">
                    <label for="color" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Pass colour</label>
                    <div class="flex items-center gap-3">
                        <input type="color" wire:model.live="color" id="color"
                            class="h-10 w-20 cursor-pointer rounded-lg border-2 border-zinc-200 bg-white p-1" />
                        <span class="text-sm font-mono text-zinc-500">{{ $color ?? '#000000' }}</span>
                    </div>
                </div>

                <x-travel-directions-editor id="car-park-travel-directions" />

                <div class="space-y-2">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Parking map</label>
                    <p class="text-xs text-zinc-500">Shown on printed tickets. JPG or PNG, max 10MB.</p>

                    @if ($existingMapImage && !$mapImage)
                        <div class="mb-2">
                            <img src="{{ $existingMapImage }}" alt="Current parking map"
                                class="max-h-40 w-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                        </div>
                    @endif

                    @if ($mapImage)
                        <div class="mb-2">
                            <img src="{{ $mapImage->temporaryUrl() }}" alt="Map preview"
                                class="max-h-40 w-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                        </div>
                    @endif

                    <flux:input type="file" wire:model="mapImage" accept="image/*" />
                    <div wire:loading wire:target="mapImage" class="text-xs text-zinc-500">Uploading map image…</div>
                    @error('mapImage')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('modalOpen', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled" wire:target="mapImage,save">
                    <span wire:loading.remove wire:target="mapImage">Save</span>
                    <span wire:loading wire:target="mapImage">Uploading…</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
