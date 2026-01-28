<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Car Parks</flux:heading>
        <flux:button variant="primary" wire:click="create">Add Car Park</flux:button>
    </div>

    <div class="flex items-center justify-between gap-4">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search car parks..."
            class="max-w-xs" />
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-left text-sm text-zinc-500 dark:text-zinc-400">
            <thead class="bg-zinc-50 text-xs uppercase text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                <tr>
                    <th class="px-6 py-3">Name</th>
                    <th class="px-6 py-3">Capacity</th>
                    <th class="px-6 py-3">Occupancy</th>
                    <th class="px-6 py-3">Utilization</th>
                    <th class="px-6 py-3 text-end">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                @forelse ($carParks as $park)
                    @php
                        $percentage = $park->capacity > 0 ? ($park->current_occupancy / $park->capacity) * 100 : 0;
                        $colorClass = $percentage > 90 ? 'bg-red-500' : ($percentage > 75 ? 'bg-yellow-500' : 'bg-green-500');
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
                        <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">{{ $park->capacity }}</td>
                        <td class="px-6 py-4">
                            <flux:badge color="{{ $percentage > 90 ? 'red' : ($percentage > 75 ? 'yellow' : 'zinc') }}">
                                {{ $park->current_occupancy }} / {{ $park->capacity }}
                            </flux:badge>
                        </td>
                        <td class="px-6 py-4 w-48">
                            <div class="flex items-center gap-2">
                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-700">
                                    <div class="h-full rounded-full transition-all duration-500 {{ $colorClass }}"
                                        style="width: {{ min(100, $percentage) }}%"></div>
                                </div>
                                <span class="text-xs font-medium text-zinc-500">{{ number_format($percentage, 0) }}%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-end">
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />

                                <flux:menu>
                                    <flux:menu.item href="{{ route('admin.car-parks.show', $park) }}" icon="eye">View
                                        Details</flux:menu.item>
                                    <flux:menu.item wire:click="edit({{ $park->id }})" icon="pencil">Edit</flux:menu.item>
                                    <flux:menu.item wire:click="delete({{ $park->id }})" icon="trash" variant="danger">
                                        Delete</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-zinc-500">
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

    <flux:modal wire:model="modalOpen" class="min-w-[400px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $carParkId ? 'Edit Car Park' : 'Create Car Park' }}</flux:heading>
                <flux:subheading>Manage car park details and capacity.</flux:subheading>
            </div>

            <div class="space-y-4">
                <div class="space-y-2">
                    <label for="name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Name</label>
                    <input type="text" wire:model="name" id="name" placeholder="e.g. North Car Park"
                        class="block w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm placeholder-zinc-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" />
                </div>

                <div class="space-y-2">
                    <label for="capacity"
                        class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Capacity</label>
                    <input type="number" wire:model="capacity" id="capacity" placeholder="500"
                        class="block w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm placeholder-zinc-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200" />
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
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('modalOpen', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="save">Save</flux:button>
            </div>
        </div>
    </flux:modal>
</div>