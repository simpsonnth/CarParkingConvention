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
            <span class="h-2 w-2 shrink-0 rounded-full bg-red-500" aria-hidden="true"></span>
            Over capacity
        </span>
    </p>

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
                        $parked = (int) $park->current_occupancy;
                        $liveCapacity = $park->capacityForToday();
                        $livePct = $liveCapacity > 0 ? min(100, 100 * $parked / $liveCapacity) : 0;
                        $liveOver = $parked > $liveCapacity;
                        $liveFree = max(0, $liveCapacity - $parked);
                        $liveTooltip = "{$parked} clocked in · {$liveFree} spaces free (today's limit {$liveCapacity})";

                        $dayColumns = [
                            'friday' => ['label' => 'Friday', 'assigned' => (int) $park->assigned_friday, 'capacity' => (int) $park->capacity_friday],
                            'saturday' => ['label' => 'Saturday', 'assigned' => (int) $park->assigned_saturday, 'capacity' => (int) $park->capacity_saturday],
                            'sunday' => ['label' => 'Sunday', 'assigned' => (int) $park->assigned_sunday, 'capacity' => (int) $park->capacity_sunday],
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
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <flux:tooltip :content="$liveTooltip" position="top">
                                <div class="cursor-help space-y-1.5">
                                    <flux:badge color="{{ $liveOver ? 'red' : 'zinc' }}">
                                        {{ $parked }} in / {{ $liveCapacity }}
                                    </flux:badge>
                                    <div class="flex h-1.5 w-28 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-700"
                                        role="progressbar"
                                        aria-valuenow="{{ (int) $livePct }}"
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                        aria-label="{{ $park->name }} live occupancy: {{ $liveTooltip }}">
                                        <div class="h-full bg-green-500 transition-all duration-500"
                                            style="width: {{ $livePct }}%"></div>
                                    </div>
                                </div>
                            </flux:tooltip>
                        </td>
                        @foreach ($dayColumns as $day)
                            @php
                                $assigned = $day['assigned'];
                                $dayCapacity = $day['capacity'];
                                $dayPct = $dayCapacity > 0 ? min(100, 100 * $assigned / $dayCapacity) : 0;
                                $dayOver = $assigned > $dayCapacity;
                                $dayFree = max(0, $dayCapacity - $assigned);
                                $dayTooltip = "{$assigned} registered for {$day['label']} · {$dayFree} spaces free";
                            @endphp
                            <td class="px-4 py-4">
                                <flux:tooltip :content="$dayTooltip" position="top">
                                    <div class="cursor-help space-y-1.5">
                                        <flux:badge color="{{ $dayOver ? 'red' : 'zinc' }}">
                                            {{ $assigned }} / {{ $dayCapacity }}
                                        </flux:badge>
                                        @if ($dayOver)
                                            <span class="block text-xs font-medium text-red-600 dark:text-red-400">Over by {{ $assigned - $dayCapacity }}</span>
                                        @endif
                                        <div class="flex h-1.5 w-28 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-700"
                                            role="progressbar"
                                            aria-valuenow="{{ (int) $dayPct }}"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                            aria-label="{{ $park->name }} {{ $day['label'] }} demand: {{ $dayTooltip }}">
                                            <div class="h-full bg-yellow-400 transition-all duration-500 dark:bg-yellow-500"
                                                style="width: {{ $dayPct }}%"></div>
                                        </div>
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
        </table>
    </div>

    <div>
        {{ $carParks->links() }}
    </div>

    <flux:modal wire:model="modalOpen" class="w-[calc(100vw-2rem)] max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $carParkId ? 'Edit Car Park' : 'Create Car Park' }}</flux:heading>
                <flux:subheading>Manage car park details and per-day capacity.</flux:subheading>
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
