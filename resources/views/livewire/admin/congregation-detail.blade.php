<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <div class="mb-2">
                <a href="{{ route('admin.congregations') }}"
                    class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 flex items-center gap-1">
                    <flux:icon name="arrow-left" class="size-3" />
                    Back to Congregations
                </a>
            </div>
            <flux:heading size="xl">{{ $congregation->name }}</flux:heading>
            <flux:subheading>
                Assigned to: {{ $congregation->carPark->name ?? 'Unassigned' }}
            </flux:subheading>
        </div>
        <div>
            {{-- Edit action could go here if needed --}}
        </div>
    </div>

    {{-- Active Vehicles List --}}
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <flux:heading size="lg">Active Vehicles</flux:heading>
            <flux:button wire:click="checkoutAll" variant="danger" icon="arrow-right-start-on-rectangle"
                wire:confirm="Are you sure you want to mark ALL vehicles as left? This cannot be undone.">
                Check Out All
            </flux:button>
        </div>

        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-left text-sm text-zinc-500 dark:text-zinc-400">
                <thead class="bg-zinc-50 text-xs uppercase text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                    <tr>
                        <th class="px-6 py-3">Vehicle Reg</th>
                        <th class="px-6 py-3">Contact</th>
                        <th class="px-6 py-3">Time In</th>
                        <th class="px-6 py-3">Attendant</th>
                        <th class="px-6 py-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                    @forelse ($cars as $pass)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                            <td class="px-6 py-4 font-mono font-medium text-zinc-900 dark:text-white">
                                {{ $pass->vehicle_reg ?? '-' }}
                            </td>
                            <td class="px-6 py-4 font-mono text-sm">
                                {{ $pass->contact_number ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $pass->scanned_at->format('H:i') }}
                                <span class="text-xs text-zinc-400 ml-1">({{ $pass->scanned_at->diffForHumans() }})</span>
                            </td>
                            <td class="px-6 py-4">
                                {{ $pass->scannedBy->name ?? 'System' }}
                            </td>
                            <td class="px-6 py-4 text-end">
                                <flux:button wire:click="checkout({{ $pass->id }})" size="sm" variant="danger"
                                    icon="arrow-right-start-on-rectangle">Check Out</flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-zinc-500">
                                No active vehicles found for this congregation.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $cars->links() }}
        </div>
    </div>
</div>