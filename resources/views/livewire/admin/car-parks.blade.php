<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <flux:heading size="xl">Car Parks</flux:heading>
        @can('car-parks.manage')
            <flux:button variant="primary" wire:click="create" class="w-full sm:w-auto">Add Car Park</flux:button>
        @endcan
    </div>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search car parks..."
            class="w-full min-w-0 sm:max-w-xs" />
    </div>

    <p class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-zinc-500 dark:text-zinc-400">
        <span class="inline-flex items-center gap-1.5">
            <span class="h-2 w-2 shrink-0 rounded-full bg-green-500" aria-hidden="true"></span>
            Clocked in (live)
        </span>
        <span class="inline-flex items-center gap-1.5">
            <span class="h-2 w-2 shrink-0 rounded-full bg-yellow-400 dark:bg-yellow-500" aria-hidden="true"></span>
            Registered for that day
        </span>
        <span class="inline-flex items-center gap-1.5">
            <span class="h-2 w-2 shrink-0 rounded-full bg-orange-500" aria-hidden="true"></span>
            Double park (overflow)
        </span>
        <span class="inline-flex items-center gap-1.5">
            <span class="h-2 w-2 shrink-0 rounded-full bg-red-500" aria-hidden="true"></span>
            Over overflow limit
        </span>
        <span class="inline-flex items-center gap-1.5">
            <span class="h-3 w-0.5 shrink-0 bg-zinc-700 dark:bg-zinc-200" aria-hidden="true"></span>
            Base capacity
        </span>
        <span class="inline-flex items-center gap-1.5">
            <span class="h-3.5 w-0.5 shrink-0 bg-sky-600 dark:bg-sky-400" aria-hidden="true"></span>
            Aim (half overflow)
        </span>
        <span class="inline-flex items-center gap-1.5">
            <span class="h-2 w-2 shrink-0 rounded-full bg-amber-500" aria-hidden="true"></span>
            Drop-off coaches (not counted)
        </span>
    </p>

    @if (($dropOffCoachTotal ?? 0) > 0)
        <div class="rounded-xl border-2 border-amber-400 bg-amber-50 px-4 py-3 dark:border-amber-500 dark:bg-amber-950">
            <p class="text-sm font-bold text-amber-950 dark:text-amber-50">
                {{ $dropOffCoachTotal }} {{ \Illuminate\Support\Str::plural('coach', $dropOffCoachTotal) }} not staying at Twickenham
            </p>
            <p class="mt-1 text-sm text-amber-900 dark:text-amber-100">
                Drop-off only coaches are excluded from Fri / Sat / Sun capacity counts below — they do not take a parking space.
                <a href="{{ route('admin.coaches') }}" wire:navigate
                    class="font-bold text-amber-950 underline decoration-amber-700 underline-offset-2 hover:decoration-amber-950 dark:text-white dark:decoration-amber-200 dark:hover:decoration-white">
                    View coaches
                </a>
            </p>
        </div>
    @endif

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700 -mx-4 sm:mx-0">
        <table class="w-full min-w-[720px] text-left text-sm text-zinc-500 dark:text-zinc-400">
            <thead class="bg-zinc-50 text-xs uppercase text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                <tr>
                    <th class="px-6 py-3">Name</th>
                    <th class="px-4 py-3">Live</th>
                    <th class="px-4 py-3">Fri</th>
                    <th class="px-4 py-3">Sat</th>
                    <th class="px-4 py-3">Sun</th>
                    <th class="px-6 py-3 text-end">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                @forelse ($carParks as $park)
                    @php
                        $overflow = $park->overflowCapacity();
                        $parked = (int) $park->current_occupancy;
                        $liveCapacity = $park->capacityForToday();
                        $liveReading = \App\Support\CarParkCapacityReading::make($parked, $liveCapacity, $overflow);
                        $liveFree = max(0, $liveCapacity - $parked);
                        $liveTooltip = "{$parked} clocked in · {$liveFree} spaces free (today's limit {$liveCapacity})"
                            .$liveReading->tooltipExtra();
                        $dropOffTotal = (int) ($park->drop_off_coaches ?? 0);

                        $dayColumns = [
                            'friday' => [
                                'label' => 'Friday',
                                'assigned' => (int) $park->assigned_friday,
                                'capacity' => (int) $park->capacity_friday,
                                'drop_off' => (int) ($park->drop_off_friday ?? 0),
                            ],
                            'saturday' => [
                                'label' => 'Saturday',
                                'assigned' => (int) $park->assigned_saturday,
                                'capacity' => (int) $park->capacity_saturday,
                                'drop_off' => (int) ($park->drop_off_saturday ?? 0),
                            ],
                            'sunday' => [
                                'label' => 'Sunday',
                                'assigned' => (int) $park->assigned_sunday,
                                'capacity' => (int) $park->capacity_sunday,
                                'drop_off' => (int) ($park->drop_off_sunday ?? 0),
                            ],
                        ];
                    @endphp
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 rounded-full border border-zinc-200 dark:border-zinc-600 shadow-sm"
                                    style="background-color: {{ $park->color }}"></div>
                                <span class="text-[10px] text-zinc-400 font-mono">{{ $park->color }}</span>
                                <div>
                                    <div class="font-medium text-zinc-900 dark:text-white">{{ $park->name }}</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $park->location ?? 'No location' }}
                                    </div>
                                    @if ($overflow > 0)
                                        <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                            Overflow {{ $overflow }} · aim +{{ intdiv($overflow, 2) }} past base
                                        </div>
                                    @endif
                                    @if ($dropOffTotal > 0)
                                        <div class="mt-1 text-xs font-semibold text-amber-800 dark:text-amber-200">
                                            {{ $dropOffTotal }} drop-off {{ \Illuminate\Support\Str::plural('coach', $dropOffTotal) }} (not counted)
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <flux:tooltip :content="$liveTooltip" position="top">
                                <div class="cursor-help space-y-1.5">
                                    <flux:badge color="{{ $liveReading->badgeColor() }}">
                                        {{ $parked }} in / {{ $liveCapacity }}
                                        @if ($overflow > 0)
                                            <span class="opacity-70">(+{{ $overflow }})</span>
                                        @endif
                                    </flux:badge>
                                    @if ($liveReading->statusLabel())
                                        <span class="block text-xs font-medium {{ $liveReading->statusTextClass() }}">
                                            {{ $liveReading->statusLabel() }}
                                        </span>
                                    @endif
                                    <x-car-park-capacity-meter
                                        :reading="$liveReading"
                                        ok-bar-class="bg-green-500"
                                        :aria-label="$park->name.' live occupancy: '.$liveTooltip"
                                    />
                                </div>
                            </flux:tooltip>
                        </td>
                        @foreach ($dayColumns as $day)
                            @php
                                $assigned = $day['assigned'];
                                $dayCapacity = $day['capacity'];
                                $dayReading = \App\Support\CarParkCapacityReading::make($assigned, $dayCapacity, $overflow);
                                $dayFree = max(0, $dayCapacity - $assigned);
                                $dropOff = $day['drop_off'];
                                $dayTooltip = "{$assigned} registered for {$day['label']} · {$dayFree} spaces free"
                                    .$dayReading->tooltipExtra()
                                    .($dropOff > 0 ? " · {$dropOff} drop-off coach(es) not counted" : '');
                            @endphp
                            <td class="px-4 py-4">
                                <flux:tooltip :content="$dayTooltip" position="top">
                                    <div class="cursor-help space-y-1.5">
                                        <flux:badge color="{{ $dayReading->badgeColor() }}">
                                            {{ $assigned }} / {{ $dayCapacity }}
                                            @if ($overflow > 0)
                                                <span class="opacity-70">(+{{ $overflow }})</span>
                                            @endif
                                        </flux:badge>
                                        @if ($dayReading->statusLabel())
                                            <span class="block text-xs font-medium {{ $dayReading->statusTextClass() }}">
                                                {{ $dayReading->statusLabel() }}
                                            </span>
                                        @endif
                                        @if ($dropOff > 0)
                                            <span class="block text-xs font-semibold text-amber-800 dark:text-amber-200">
                                                +{{ $dropOff }} drop-off
                                            </span>
                                        @endif
                                        <x-car-park-capacity-meter
                                            :reading="$dayReading"
                                            :aria-label="$park->name.' '.$day['label'].' demand: '.$dayTooltip"
                                        />
                                    </div>
                                </flux:tooltip>
                            </td>
                        @endforeach
                        <td class="px-6 py-4 text-end">
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />

                                <flux:menu>
                                    <flux:menu.item href="{{ route('admin.car-parks.show', $park) }}" icon="eye">View
                                        Details</flux:menu.item>
                                    @can('car-parks.manage')
                                        <flux:menu.item wire:click="edit({{ $park->id }})" icon="pencil">Edit</flux:menu.item>
                                        <flux:menu.item wire:click="delete({{ $park->id }})" icon="trash" variant="danger">
                                            Delete</flux:menu.item>
                                    @endcan
                                </flux:menu>
                            </flux:dropdown>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-zinc-500">
                            No car parks found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if ($carParks->total() > 0)
                <tfoot class="border-t-2 border-zinc-200 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-900/80">
                    <tr>
                        <td class="px-6 py-4 text-sm font-semibold text-zinc-900 dark:text-white">
                            Total over capacity
                        </td>
                        @foreach (['live', 'friday', 'saturday', 'sunday'] as $dayKey)
                            @php
                                $cell = $capacityOverTotals[$dayKey] ?? ['over_base' => 0, 'over_hard' => 0];
                                $overBase = (int) ($cell['over_base'] ?? 0);
                                $overHard = (int) ($cell['over_hard'] ?? 0);
                            @endphp
                            <td class="px-4 py-4">
                                @if ($overHard > 0)
                                    <span class="block text-sm font-semibold text-red-600 dark:text-red-400">
                                        Over overflow by {{ $overHard }}
                                    </span>
                                    @if ($overBase > $overHard)
                                        <span class="block text-xs font-medium text-orange-600 dark:text-orange-400">
                                            Double park +{{ $overBase }}
                                        </span>
                                    @endif
                                @elseif ($overBase > 0)
                                    <span class="text-sm font-semibold text-orange-600 dark:text-orange-400">
                                        Double park +{{ $overBase }}
                                    </span>
                                @else
                                    <span class="text-sm text-zinc-400">—</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="px-6 py-4"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    <div>
        {{ $carParks->links() }}
    </div>

    <flux:modal wire:model="modalOpen" class="w-[calc(100vw-2rem)] max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $carParkId ? 'Edit Car Park' : 'Create Car Park' }}</flux:heading>
                <flux:subheading>Manage car park details, per-day capacity, and double-park overflow.</flux:subheading>
            </div>

            <div class="space-y-4">
                <div class="space-y-2">
                    <label for="name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Name</label>
                    <input type="text" wire:model="name" id="name" placeholder="e.g. North Car Park"
                        class="block w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm placeholder-zinc-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" />
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="space-y-2">
                        <label for="capacityFriday"
                            class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Friday capacity</label>
                        <input type="number" wire:model="capacityFriday" id="capacityFriday" placeholder="200" min="1"
                            class="block w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm placeholder-zinc-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" />
                        @error('capacityFriday')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="space-y-2">
                        <label for="capacitySaturday"
                            class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Saturday capacity</label>
                        <input type="number" wire:model="capacitySaturday" id="capacitySaturday" placeholder="200" min="1"
                            class="block w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm placeholder-zinc-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" />
                        @error('capacitySaturday')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="space-y-2">
                        <label for="capacitySunday"
                            class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Sunday capacity</label>
                        <input type="number" wire:model="capacitySunday" id="capacitySunday" placeholder="200" min="1"
                            class="block w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm placeholder-zinc-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" />
                        @error('capacitySunday')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="overflowCapacity"
                        class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Double-park overflow</label>
                    <input type="number" wire:model="overflowCapacity" id="overflowCapacity" placeholder="0" min="0"
                        class="block w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm placeholder-zinc-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" />
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Extra spaces available by double parking. The meter aims for half of this (sky marker). Going past
                        base + overflow shows a red warning.
                    </p>
                    @error('overflowCapacity')
                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label for="location"
                        class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Location</label>
                    <input type="text" wire:model="location" id="location" placeholder="Optional description"
                        class="block w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm placeholder-zinc-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" />
                </div>

                <div class="space-y-2">
                    <label for="color" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Pass
                        Color</label>
                    <div class="flex items-center gap-3">
                        <input type="color" wire:model.live="color" id="color"
                            class="h-10 w-20 cursor-pointer rounded-lg border-2 border-zinc-200 bg-white p-1" />
                        <span class="text-sm font-mono text-zinc-500">{{ $color ?? '#000000' }}</span>
                    </div>
                </div>

                <x-travel-directions-editor id="car-park-travel-directions" />

                <div class="space-y-2">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Parking Map</label>
                    <p class="text-xs text-zinc-500">Shown on the back of printed tickets. JPG or PNG, max 10MB.</p>

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
                    <div wire:loading wire:target="mapImage" class="text-xs text-zinc-500">
                        Uploading map image…
                    </div>
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
