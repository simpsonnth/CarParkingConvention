<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Registrations') }}</flux:heading>
            <flux:subheading>{{ __('Manage specific parking registrations.') }}</flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search..." />
        </div>
    </div>

    <flux:separator />

    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-left text-sm">
            <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                <tr>
                    <th class="px-6 py-3">Date</th>
                    <th class="px-6 py-3">Name</th>
                    <th class="px-6 py-3">Congregation</th>
                    <th class="px-6 py-3">Vehicle Reg</th>
                    <th class="px-6 py-3">Contact</th>
                    <th class="px-6 py-3">Days</th>
                    <th class="px-6 py-3 text-end">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                @forelse($registrations as $reg)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
                        <td class="px-6 py-4 text-zinc-500 whitespace-nowrap">
                            {{ $reg->created_at->format('d M H:i') }}
                        </td>
                        <td class="px-6 py-4 font-medium text-zinc-900 dark:text-white">
                            {{ $reg->name }}
                        </td>
                        <td class="px-6 py-4 text-zinc-600 dark:text-zinc-300">
                            {{ $reg->congregation }}
                        </td>
                        <td class="px-6 py-4 font-mono text-zinc-600 dark:text-zinc-300">
                            <span
                                class="bg-zinc-100 px-2 py-1 rounded text-xs dark:bg-zinc-700 font-bold tracking-wider">{{ $reg->vehicle_registration }}</span>
                        </td>
                        <td class="px-6 py-4 text-zinc-500">
                            {{ $reg->contact_number }}
                            <div class="text-xs text-zinc-400">{{ $reg->email }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex gap-1 flex-wrap">
                                @foreach($reg->days as $day)
                                    <span
                                        class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10 dark:bg-indigo-400/10 dark:text-indigo-400 dark:ring-indigo-400/30">{{ substr($day, 0, 3) }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-6 py-4 text-end">
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />

                                <flux:menu>
                                    <flux:menu.item wire:click="edit({{ $reg->id }})" icon="pencil">Edit</flux:menu.item>
                                    <flux:menu.item wire:click="delete({{ $reg->id }})"
                                        wire:confirm="Are you sure you want to delete this registration?" icon="trash"
                                        variant="danger">Delete</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-zinc-500">
                            <div class="flex flex-col items-center justify-center">
                                <flux:icon name="clipboard-document-list" class="size-10 text-zinc-300 mb-2" />
                                <p>No registrations found.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $registrations->links() }}
    </div>

    {{-- Edit Modal --}}
    <flux:modal wire:model="modalOpen" class="min-w-[400px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Edit Registration</flux:heading>
                <flux:subheading>Update registration details.</flux:subheading>
            </div>

            <flux:input wire:model="name" label="Name" placeholder="Full Name" />

            <flux:select wire:model="congregation" label="Congregation" placeholder="Select Congregation">
                @foreach($congregations as $name)
                    <option value="{{ $name }}">{{ $name }}</option>
                @endforeach
            </flux:select>

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="vehicleReg" label="Vehicle Reg" placeholder="Registration" />
                <flux:input wire:model="contactNumber" label="Contact Number" placeholder="Mobile Number" />
            </div>

            <flux:input wire:model="email" label="Email" type="email" placeholder="Email Address (Optional)" />

            <div class="space-y-2">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Days Attending</label>
                <div class="flex gap-2">
                    @foreach(['Friday', 'Saturday', 'Sunday'] as $day)
                        <button type="button" wire:click="toggleDay('{{ $day }}')" @class([
                            'px-3 py-1.5 rounded-lg text-sm font-medium transition-all border',
                            'bg-indigo-500 text-white border-indigo-600 shadow-sm' => in_array($day, $days),
                            'bg-white dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700' => !in_array($day, $days),
                        ])>
                            {{ $day }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('modalOpen', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="save">Save Changes</flux:button>
            </div>
        </div>
    </flux:modal>
</div>