<div class="space-y-8">
    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <flux:heading size="xl">Command Center</flux:heading>
            <flux:subheading>Real-time parking attendance monitoring</flux:subheading>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <span class="relative flex h-3 w-3">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
            </span>
            <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Live Updates</span>
        </div>
    </div>

    {{-- Global Stats --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <div class="grid gap-8 md:grid-cols-4">
            {{-- Total Occupancy --}}
            <div class="space-y-1">
                <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Parked</div>
                <div class="text-3xl font-bold text-zinc-900 dark:text-white">{{ number_format($totalOccupancy) }}</div>
            </div>

            {{-- Available --}}
            <div class="space-y-1">
                <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Available</div>
                <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ number_format(max(0, $totalCapacity - $totalOccupancy)) }}</div>
            </div>

            {{-- Total Capacity --}}
            <div class="space-y-1">
                <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Capacity</div>
                <div class="text-3xl font-bold text-zinc-900 dark:text-white">{{ number_format($totalCapacity) }}</div>
            </div>

            {{-- Utilization --}}
            <div class="space-y-2">
                 <div class="flex items-end justify-between">
                    <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Utilization</div>
                    <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($totalPercentage, 1) }}%</div>
                </div>
                <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-700">
                    <div class="h-full rounded-full bg-indigo-600 transition-all duration-500" 
                         style="width: {{ $totalPercentage }}%">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <flux:separator />

    {{-- Individual Parks --}}
    <div>
        <div class="mb-4">
            <flux:heading size="lg">Car Parks</flux:heading>
        </div>
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($carParks as $park)
                @php
                    $percentage = $park->capacity > 0 ? ($park->current_occupancy / $park->capacity) * 100 : 0;
                    $color = $percentage > 90 ? 'red' : ($percentage > 75 ? 'yellow' : 'green');
                    $barColor = match ($color) {
                        'red' => 'bg-red-500',
                        'yellow' => 'bg-yellow-500',
                        'green' => 'bg-green-500',
                        default => 'bg-green-500'
                    };
                @endphp
                <div class="flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm transition hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $park->name }}</div>
                            <div class="flex items-center gap-1 text-xs text-zinc-500 dark:text-zinc-400">
                                <flux:icon name="map-pin" class="size-3" />
                                <span>{{ $park->location ?? 'No location' }}</span>
                            </div>
                        </div>
                        <flux:badge color="{{ $color }}">{{ $park->current_occupancy }} / {{ $park->capacity }}</flux:badge>
                    </div>

                    <div class="space-y-2 pt-2">
                        <div class="flex justify-between text-xs font-medium text-zinc-500 dark:text-zinc-400">
                            <span>Occupancy</span>
                            <span>{{ number_format($percentage, 1) }}%</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-700">
                            <div class="h-full rounded-full transition-all duration-500 {{ $barColor }}"
                                style="width: {{ $percentage }}%">
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end pt-2">
                            <flux:button size="sm" variant="ghost" wire:click="viewCars({{ $park->id }})">View Details</flux:button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <flux:separator />

    {{-- Activity & Stats --}}
    <div class="grid gap-8 lg:grid-cols-3">
        {{-- Recent Activity --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Live Activity Feed</flux:heading>
                <div class="text-xs text-zinc-500">Latest check-ins</div>
            </div>
            
            <div class="rounded-xl border border-zinc-200 bg-white overflow-x-auto dark:border-zinc-700 dark:bg-zinc-800 -mx-4 sm:mx-0">
                @if($recentScans->isEmpty())
                    <div class="p-8 text-center text-zinc-500">
                        No activity recorded yet.
                    </div>
                @else
                    <table class="w-full min-w-[520px] text-left text-sm">
                        <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                            <tr>
                                <th class="px-6 py-3">Time</th>
                                <th class="px-6 py-3">Congregation</th>
                                <th class="px-6 py-3">Vehicle Reg</th>
                                <th class="px-6 py-3">Car Park</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                            @foreach($recentScans as $scan)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                    <td class="px-6 py-3 text-zinc-500 whitespace-nowrap">
                                        {{ $scan->scanned_at->format('H:i:s') }}
                                        <span class="text-xs text-zinc-400 ml-1">{{ $scan->scanned_at->diffForHumans() }}</span>
                                    </td>
                                    <td class="px-6 py-3 font-medium text-zinc-900 dark:text-white">
                                        {{ $scan->congregation->name }}
                                    </td>
                                    <td class="px-6 py-3 font-mono text-zinc-600 dark:text-zinc-300">
                                        @if($scan->vehicle_reg)
                                            <span class="bg-zinc-100 px-2 py-1 rounded text-xs dark:bg-zinc-700">{{ $scan->vehicle_reg }}</span>
                                        @else
                                            <span class="text-zinc-400 opacity-50">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3">
                                        <flux:badge size="sm" color="zinc">{{ $scan->congregation->carPark->name }}</flux:badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        {{-- Top Congregations --}}
        <div class="space-y-4">
            <flux:heading size="lg">Top Congregations</flux:heading>
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                @if($congregationStats->isEmpty())
                        <div class="text-center text-zinc-500 text-sm">No data available</div>
                @else
                    <div class="space-y-4">
                        @foreach($congregationStats as $index => $cong)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-6 w-6 items-center justify-center rounded-full bg-zinc-100 text-xs font-bold text-zinc-500 dark:bg-zinc-700">
                                        {{ $index + 1 }}
                                    </div>
                                    <div class="font-medium text-zinc-700 dark:text-zinc-200">{{ $cong->name }}</div>
                                </div>
                                <div class="font-bold text-zinc-900 dark:text-white">{{ $cong->parked_count }}</div>
                            </div>
                            @if(!$loop->last) <hr class="border-zinc-100 dark:border-zinc-700"> @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <flux:separator />

    {{-- Public Registrations --}}
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <flux:heading size="lg">Pre-Registrations</flux:heading>
            <div class="text-xs text-zinc-500">Recent online registrations</div>
        </div>
        
        <div class="rounded-xl border border-zinc-200 bg-white overflow-x-auto dark:border-zinc-700 dark:bg-zinc-800 -mx-4 sm:mx-0">
             @if($registrations->isEmpty())
                <div class="p-8 text-center text-zinc-500">
                    No registrations yet.
                </div>
            @else
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                        <tr>
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3">Name</th>
                            <th class="px-6 py-3">Congregation</th>
                            <th class="px-6 py-3">Vehicle Reg</th>
                            <th class="px-6 py-3">Contact</th>
                            <th class="px-6 py-3">Days</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach($registrations as $reg)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-6 py-3 text-zinc-500 whitespace-nowrap">
                                    {{ $reg->created_at->format('d M H:i') }}
                                </td>
                                <td class="px-6 py-3 font-medium text-zinc-900 dark:text-white">
                                    {{ $reg->name }}
                                </td>
                                <td class="px-6 py-3 text-zinc-600 dark:text-zinc-300">
                                    {{ $reg->congregation }}
                                </td>
                                <td class="px-6 py-3 font-mono text-zinc-600 dark:text-zinc-300">
                                    <span class="bg-zinc-100 px-2 py-1 rounded text-xs dark:bg-zinc-700">{{ $reg->vehicle_registration }}</span>
                                </td>
                                <td class="px-6 py-3 text-zinc-500">
                                    {{ $reg->contact_number }}
                                </td>
                                <td class="px-6 py-3">
                                    <div class="flex gap-1">
                                        @foreach($reg->days as $day)
                                            <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10 dark:bg-indigo-400/10 dark:text-indigo-400 dark:ring-indigo-400/30">{{ substr($day, 0, 3) }}</span>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- View Cars Modal --}}
    <flux:modal wire:model="viewCarsModal" class="w-[calc(100vw-2rem)] max-w-2xl max-h-[85vh] overflow-y-auto">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $selectedPark->name ?? 'Car Park' }}</flux:heading>
                <flux:subheading>Vehicles currently parked in this location.</flux:subheading>
            </div>

            <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                        <tr>
                            <th class="px-4 py-2">Vehicle Reg</th>
                            <th class="px-4 py-2">Congregation</th>
                            <th class="px-4 py-2">Scanned</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @forelse($parkedCars ?? [] as $pass)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-4 py-2 font-mono font-medium">{{ $pass->vehicle_reg ?? '-' }}</td>
                                <td class="px-4 py-2">{{ $pass->congregation->name }}</td>
                                <td class="px-4 py-2 text-zinc-500 text-xs">{{ $pass->scanned_at->format('H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-zinc-500">No vehicles found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($parkedCars)
                <div class="mt-4">
                    {{ $parkedCars->links() }}
                </div>
            @endif

            <div class="flex justify-end">
                <flux:button variant="ghost" wire:click="$set('viewCarsModal', false)">Close</flux:button>
            </div>
        </div>
    </flux:modal>
</div>