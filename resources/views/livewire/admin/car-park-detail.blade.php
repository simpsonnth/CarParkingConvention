<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <div class="mb-2">
                <a href="{{ route('admin.car-parks') }}"
                    class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 flex items-center gap-1">
                    <flux:icon name="arrow-left" class="size-3" />
                    Back to Car Parks
                </a>
            </div>
            <div class="flex items-center gap-3">
                <flux:heading size="xl">{{ $carPark->name }}</flux:heading>
                @if($carPark->color)
                    <div class="w-4 h-4 shrink-0 rounded-full border border-zinc-200 dark:border-zinc-600 shadow-sm"
                        style="background-color: {{ $carPark->color }}"></div>
                @endif
            </div>
            <flux:subheading>{{ $carPark->location ?? 'No location specified' }}</flux:subheading>
        </div>
        @can('car-parks.manage')
            <div class="shrink-0">
                <flux:button variant="ghost" icon="pencil" wire:click="edit" class="w-full sm:w-auto">Edit Details</flux:button>
            </div>
        @endcan
    </div>

    @if ($carPark->map_image_path)
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg" class="mb-4">Parking Map</flux:heading>
            <img src="{{ $carPark->map_image_path }}" alt="Parking map for {{ $carPark->name }}"
                class="max-h-96 w-full rounded-lg border border-zinc-200 object-contain dark:border-zinc-700">
        </div>
    @endif

    {{-- Stats Cards --}}
    @php
        $liveReading = \App\Support\CarParkCapacityReading::make((int) $occupancy, (int) $capacity, $carPark->overflowCapacity());
    @endphp
    <div class="grid gap-6 md:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Current Occupancy</div>
            <div class="mt-2 flex items-baseline gap-2">
                <span @class([
                    'text-3xl font-bold',
                    $liveReading->ratioTextClass(),
                ])>{{ $occupancy }}</span>
                <span class="text-sm text-zinc-500">/ {{ $capacity }}
                    @if ($carPark->overflowCapacity() > 0)
                        <span class="text-zinc-400">(+{{ $carPark->overflowCapacity() }} overflow)</span>
                    @endif
                </span>
            </div>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Today's capacity limit</p>
            @if ($liveReading->statusLabel())
                <p class="mt-1 text-xs font-medium {{ $liveReading->statusTextClass() }}">{{ $liveReading->statusLabel() }}</p>
            @endif
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Utilization</div>
            <div class="mt-2 flex items-baseline gap-2">
                <span
                    class="text-3xl font-bold text-zinc-900 dark:text-white">{{ number_format($liveReading->fillPercent(), 1) }}%</span>
            </div>
            <div class="mt-3">
                <x-car-park-capacity-meter
                    :reading="$liveReading"
                    ok-bar-class="bg-green-500"
                    width-class="w-full"
                    :aria-label="'Live utilization'"
                />
            </div>
            @if ($carPark->overflowCapacity() > 0)
                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                    Meter scale includes overflow. Sky mark = half overflow aim ({{ $liveReading->recommendedLimit() }}).
                </p>
            @endif
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Available Spaces</div>
            <div class="mt-2 text-3xl font-bold text-zinc-900 dark:text-white">
                {{ max(0, $capacity - $occupancy) }}
            </div>
            @if ($carPark->overflowCapacity() > 0)
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ max(0, $liveReading->hardLimit() - $occupancy) }} including overflow
                </p>
            @endif
        </div>
    </div>

    {{-- Per-day planning --}}
    <div class="grid gap-4 sm:grid-cols-3">
        @foreach ([
            'friday' => ['label' => 'Friday', 'capacity' => (int) $carPark->capacity_friday],
            'saturday' => ['label' => 'Saturday', 'capacity' => (int) $carPark->capacity_saturday],
            'sunday' => ['label' => 'Sunday', 'capacity' => (int) $carPark->capacity_sunday],
        ] as $dayKey => $dayMeta)
            @php
                $assigned = (int) ($dayAssigned[$dayKey] ?? 0);
                $dayCapacity = $dayMeta['capacity'];
                $dayReading = \App\Support\CarParkCapacityReading::make($assigned, $dayCapacity, $carPark->overflowCapacity());
                $dropOff = (int) ($dayDropOff[$dayKey] ?? 0);
            @endphp
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ $dayMeta['label'] }} demand</div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span @class([
                        'text-2xl font-bold',
                        $dayReading->ratioTextClass(),
                    ])>{{ $assigned }}</span>
                    <span class="text-sm text-zinc-500">/ {{ $dayCapacity }}
                        @if ($carPark->overflowCapacity() > 0)
                            <span class="text-zinc-400">(+{{ $carPark->overflowCapacity() }})</span>
                        @endif
                    </span>
                </div>
                @if ($dayReading->statusLabel())
                    <p class="mt-1 text-xs font-medium {{ $dayReading->statusTextClass() }}">{{ $dayReading->statusLabel() }}</p>
                @endif
                @if ($dropOff > 0)
                    <p class="mt-1 text-xs font-semibold text-amber-800 dark:text-amber-200">
                        +{{ $dropOff }} drop-off {{ \Illuminate\Support\Str::plural('coach', $dropOff) }} (not counted)
                    </p>
                @endif
                <div class="mt-3">
                    <x-car-park-capacity-meter
                        :reading="$dayReading"
                        width-class="w-full"
                        :aria-label="$dayMeta['label'].' demand'"
                    />
                </div>
            </div>
        @endforeach
    </div>

    <flux:separator />

    {{-- Congregation Breakdown --}}
    <div class="space-y-4">
        <flux:heading size="lg">Current Occupancy Breakdown</flux:heading>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @forelse ($congregationBreakdown as $stat)
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                    <div class="text-xs font-medium text-zinc-500 uppercase tracking-wider">{{ $stat->name }}</div>
                    <div class="mt-1 flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $stat->parked_count }}</span>
                        <span class="text-xs text-zinc-500">vehicles</span>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-4 text-center text-zinc-500 text-sm italic">
                    No congregation specific data available.
                </div>
            @endforelse
        </div>
    </div>

    <flux:separator />

    {{-- Parked Vehicles List --}}
    <div class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <flux:heading size="lg">Parked Vehicles</flux:heading>
            <flux:button wire:click="checkoutAll" variant="danger" icon="arrow-right-start-on-rectangle"
                wire:confirm="Are you sure you want to mark ALL vehicles as left? This cannot be undone."
                class="w-full sm:w-auto">
                Check Out All
            </flux:button>
        </div>

        <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700 -mx-4 sm:mx-0">
            <table class="w-full min-w-[640px] text-left text-sm text-zinc-500 dark:text-zinc-400">
                <thead class="bg-zinc-50 text-xs uppercase text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                    <tr>
                        <th class="px-6 py-3">Vehicle Reg</th>
                        <th class="px-6 py-3">Congregation</th>
                        <th class="px-6 py-3">Time In</th>
                        <th class="px-6 py-3">Contact</th>
                        <th class="px-6 py-3">Attendant</th>
                        <th class="px-6 py-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                    @forelse ($parkedCars as $pass)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 cursor-pointer"
                            wire:click="viewDetails({{ $pass->id }})">
                            <td class="px-6 py-4 font-mono font-medium text-zinc-900 dark:text-white">
                                {{ $pass->vehicle_reg ?? '-' }}
                            </td>
                            <td class="px-6 py-4">{{ $pass->congregation->name }}</td>
                            <td class="px-6 py-4">
                                {{ $pass->scanned_at->format('H:i') }}
                                <span class="text-xs text-zinc-400 ml-1">({{ $pass->scanned_at->diffForHumans() }})</span>
                            </td>
                            <td class="px-6 py-4 font-mono text-sm">
                                {{ $pass->contact_number ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $pass->scannedBy->name ?? 'System' }}
                            </td>
                            <td class="px-6 py-4 text-end">
                                <flux:button wire:click.stop="checkout({{ $pass->id }})" size="sm" variant="danger"
                                    icon="arrow-right-start-on-rectangle">Check Out</flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-zinc-500">
                                No vehicles currently parked here.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $parkedCars->links() }}
        </div>
    </div>

    <flux:separator />

    {{-- History List --}}
    <div class="space-y-4">
        <div class="flex items-center gap-2">
            <flux:icon name="clock" class="size-5 text-zinc-400" />
            <flux:heading size="lg">Recent Checkout History (Today)</flux:heading>
        </div>
        <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700 -mx-4 sm:mx-0">
            <table class="w-full min-w-[480px] text-left text-sm text-zinc-500 dark:text-zinc-400">
                <thead class="bg-zinc-50 text-xs uppercase text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                    <tr>
                        <th class="px-6 py-3">Vehicle Reg</th>
                        <th class="px-6 py-3">Congregation</th>
                        <th class="px-6 py-3">Duration</th>
                        <th class="px-6 py-3">Time Left</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                    @forelse ($history as $pass)
                        <tr class="opacity-75">
                            <td class="px-6 py-4 font-mono font-medium text-zinc-900 dark:text-white">
                                {{ $pass->vehicle_reg ?? '-' }}
                            </td>
                            <td class="px-6 py-4">{{ $pass->congregation->name }}</td>
                            <td class="px-6 py-4">
                                @if($pass->left_at && $pass->scanned_at)
                                    {{ $pass->left_at->diffForHumans($pass->scanned_at, true) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-4 font-mono text-xs">
                                {{ $pass->left_at->format('H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-zinc-500 italic">
                                No checkouts recorded today.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div>
            {{ $history->links() }}
        </div>
    </div>

    {{-- Edit Modal --}}
    <flux:modal wire:model="modalOpen" class="w-[calc(100vw-2rem)] max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Edit Car Park</flux:heading>
                <flux:subheading>Update car park details.</flux:subheading>
            </div>

            <flux:input wire:model="name" label="Name" placeholder="e.g. North Car Park" />

            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="capacityFriday" label="Friday capacity" type="number" placeholder="e.g. 150" />
                <flux:input wire:model="capacitySaturday" label="Saturday capacity" type="number" placeholder="e.g. 150" />
                <flux:input wire:model="capacitySunday" label="Sunday capacity" type="number" placeholder="e.g. 150" />
            </div>

            <div class="space-y-1">
                <flux:input wire:model="overflowCapacity" label="Double-park overflow" type="number" placeholder="0" />
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    Extra spaces by double parking. Aim for half of this value on the meter.
                </p>
                @error('overflowCapacity')
                    <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <flux:input wire:model="location" label="Location" placeholder="e.g. Behind Main Hall" />

            <div class="space-y-2">
                <label for="color" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Pass Color</label>
                <div class="flex items-center gap-3">
                    <input type="color" wire:model="color" id="color"
                        class="h-10 w-20 cursor-pointer rounded-lg border-2 border-zinc-200 bg-white p-1" />
                    <span class="text-sm font-mono text-zinc-500">{{ $color ?? '#000000' }}</span>
                </div>
            </div>

            <x-travel-directions-editor id="car-park-detail-travel-directions" />

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

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('modalOpen', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled" wire:target="mapImage,save">
                    <span wire:loading.remove wire:target="mapImage">Save Changes</span>
                    <span wire:loading wire:target="mapImage">Uploading…</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- View Details Modal --}}
    <flux:modal wire:model="detailsModalOpen" class="w-[calc(100vw-2rem)] max-w-lg">
        @if($viewingPass)
            <div class="space-y-6">
                <div>
                    <div class="mb-1 text-xs font-bold text-indigo-500 uppercase tracking-widest">Vehicle Details</div>
                    <flux:heading size="lg">{{ $viewingPass->vehicle_reg }}</flux:heading>
                    <flux:subheading>{{ $viewingPass->congregation->name }}</flux:subheading>
                </div>

                <div class="space-y-4">
                    <div
                        class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 space-y-3">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-zinc-500">Driver Name</span>
                            <span class="font-medium text-zinc-900 dark:text-white">{{ $viewingPass->name ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-zinc-500">Contact Number</span>
                            <span
                                class="font-medium text-zinc-900 dark:text-white font-mono">{{ $viewingPass->contact_number ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-zinc-500">Email</span>
                            <span class="font-medium text-zinc-900 dark:text-white">{{ $viewingPass->email ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-zinc-500">Attending Days</span>
                            <span class="font-medium text-zinc-900 dark:text-white">
                                @if(!empty($viewingPass->days))
                                    @foreach($viewingPass->days as $day)
                                        <span
                                            class="inline-block px-1.5 py-0.5 bg-white dark:bg-black/20 rounded text-xs border border-zinc-200 dark:border-zinc-700">{{ substr($day, 0, 3) }}</span>
                                    @endforeach
                                @else
                                    -
                                @endif
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-3 rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <div class="text-xs text-zinc-500">Scanned At</div>
                            <div class="font-medium">{{ $viewingPass->scanned_at->format('H:i') }}</div>
                            <div class="text-xs text-zinc-400">{{ $viewingPass->scanned_at->format('d M') }}</div>
                        </div>
                        <div class="p-3 rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <div class="text-xs text-zinc-500">Scanned By</div>
                            <div class="font-medium">{{ $viewingPass->scannedBy->name ?? 'System' }}</div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="$set('detailsModalOpen', false)">Close</flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>