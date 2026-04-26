<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <flux:heading size="xl">Congregations</flux:heading>
        <flux:button variant="primary" wire:click="create" class="w-full sm:w-auto">Add Congregation</flux:button>
    </div>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                placeholder="Search congregations..." class="w-full min-w-0 sm:max-w-xs" />
            <select wire:model.live="filterCarParkId"
                class="block w-full sm:w-auto min-w-0 rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                <option value="">All Car Parks</option>
                <option value="unassigned">Not assigned to a car park</option>
                @foreach($carParks as $park)
                    <option value="{{ $park->id }}">{{ $park->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="perPage"
                class="block w-full sm:w-auto min-w-0 rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                <option value="25">25 per page</option>
                <option value="50">50 per page</option>
                <option value="100">100 per page</option>
            </select>
        </div>
    </div>

    @if(count($selectedIds) > 0)
        <div class="flex flex-wrap items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-800 dark:bg-amber-900/20">
            <span class="text-sm font-medium text-amber-800 dark:text-amber-200 basis-full sm:basis-auto">{{ count($selectedIds) }} selected</span>
            <flux:button variant="primary" size="sm" wire:click="openBulkAssignModal" icon="building-office-2">
                Assign to car park
            </flux:button>
            <button type="button" wire:click="$set('selectedIds', [])"
                class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600">
                Clear selection
            </button>
        </div>
    @endif

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700 -mx-4 sm:mx-0">
        <table class="w-full min-w-[640px] text-left text-sm text-zinc-500 dark:text-zinc-400">
            <thead class="bg-zinc-50 text-xs uppercase text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                <tr>
                    <th class="w-10 px-4 py-3">
                        <input type="checkbox" wire:click="toggleSelectAll"
                            {{ count($congregations->items()) > 0 && count(array_intersect($selectedIds, $congregations->pluck('id')->all())) === count($congregations->items()) ? 'checked' : '' }}
                            class="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                    </th>
                    <th class="px-6 py-3">Name</th>
                    <th class="px-6 py-3">Code</th>
                    <th class="px-6 py-3">Assigned Car Park</th>
                    <th class="px-6 py-3">Active Vehicles</th>
                    <th class="px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                @forelse ($congregations as $congregation)
                    <tr wire:key="congregation-row-{{ $congregation->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                        <td class="w-10 px-4 py-4">
                            <input type="checkbox" wire:model.live="selectedIds" value="{{ $congregation->id }}"
                                class="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                        </td>
                        <td class="px-6 py-4 font-medium text-zinc-900 dark:text-white">{{ $congregation->name }}</td>
                        <td class="px-6 py-4">
                            <code class="text-xs font-mono text-zinc-600 dark:text-zinc-400 bg-zinc-100 dark:bg-zinc-700 px-2 py-1 rounded" title="{{ $congregation->uuid }}">{{ \Illuminate\Support\Str::limit($congregation->uuid, 8) }}</code>
                        </td>
                        <td class="px-6 py-4">
                            @if($congregation->carPark)
                                <flux:badge color="zinc">{{ $congregation->carPark->name }}</flux:badge>
                            @else
                                <span class="text-zinc-400 text-sm">Not assigned</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <flux:badge color="{{ $congregation->parked_count > 0 ? 'green' : 'zinc' }}">
                                {{ $congregation->parked_count }}
                            </flux:badge>
                        </td>
                        <td class="px-6 py-4">
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />

                                <flux:menu>
                                    <flux:menu.item wire:click="edit({{ $congregation->id }})" icon="pencil">Edit
                                    </flux:menu.item>
                                    <flux:menu.item :href="route('admin.congregations.show', $congregation)" icon="truck"
                                        wire:navigate>
                                        Parking & survey
                                    </flux:menu.item>
                                    <flux:menu.item wire:click="openQrModal({{ $congregation->id }})" icon="qr-code">View
                                        Master Pass</flux:menu.item>
                                    <flux:menu.item wire:click="delete({{ $congregation->id }})" icon="trash"
                                        variant="danger">Delete</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-zinc-500">
                            No congregations found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $congregations->links() }}
    </div>

    {{-- Create/Edit Modal --}}
    <flux:modal wire:model="modalOpen" class="w-[calc(100vw-2rem)] max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $congregationId ? 'Edit Congregation' : 'Add Congregation' }}</flux:heading>
                <flux:subheading>Manage congregation details and assignments.</flux:subheading>
            </div>

            <flux:input wire:model="name" label="Name" placeholder="e.g. West London" />

            <flux:input wire:model="code" label="Congregation code" placeholder="e.g. 9d4e2a1b-3c5f-4a6b-8e7d-1f2a3b4c5d6e (unique, used for registration)"
                class="font-mono text-sm" />

            <div class="space-y-2">
                <label for="carParkId" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Assigned Car
                    Park</label>
                <select wire:model="carParkId" id="carParkId"
                    class="block w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm placeholder-zinc-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    <option value="">Select a car park</option>
                    @foreach ($carParks as $park)
                        <option value="{{ $park->id }}">{{ $park->name }} (Cap: {{ $park->capacity }})</option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('modalOpen', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="save">Save</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Bulk assign to car park modal --}}
    <flux:modal wire:model="bulkAssignModalOpen" class="w-[calc(100vw-2rem)] max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Assign to car park</flux:heading>
                <flux:subheading>Assign {{ count($selectedIds) }} selected congregation(s) to a car park. Leave empty to unassign.</flux:subheading>
            </div>
            <div class="space-y-2">
                <label for="bulkAssignCarParkId" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Car park</label>
                <select wire:model="bulkAssignCarParkId" id="bulkAssignCarParkId"
                    class="block w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    <option value="">Unassign (remove from car park)</option>
                    @foreach ($carParks as $park)
                        <option value="{{ $park->id }}">{{ $park->name }} (Cap: {{ $park->capacity }})</option>
                    @endforeach
                </select>
            </div>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('bulkAssignModalOpen', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="bulkAssignCarPark">Assign</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- View QR Modal --}}
    <flux:modal wire:model="qrModalOpen" class="w-[calc(100vw-2rem)] max-w-md">
        @php
            $convName = \App\Models\Setting::get('convention_name', "Convention of Jehovah's Witness");
            $convYear = \App\Models\Setting::get('convention_year', date('Y'));
            $convLoc = \App\Models\Setting::get('convention_location', 'Twickenham');
            $ticketLogo = \App\Models\Setting::get('ticket_logo');
        @endphp

        <div class="print-container space-y-6 text-center">
            {{-- This content is the UI Preview - the ACTUAL print happens on the dedicated page --}}
            <div class="bg-white p-8 rounded-2xl border border-zinc-200 shadow-sm">
                @if($ticketLogo)
                    <div class="mb-4 flex justify-center">
                        <img src="{{ $ticketLogo }}" alt="Logo" class="h-12 w-auto">
                    </div>
                @endif

                <div class="space-y-1 mb-6">
                    <div class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">{{ $convName }}</div>
                    <div class="text-xl font-black text-zinc-900">{{ $convLoc }} {{ $convYear }}</div>
                </div>

                <div class="py-4 border-y-2 border-dashed border-zinc-100 mb-6">
                    <div class="text-[10px] text-zinc-400 uppercase font-black tracking-widest mb-1">CONGREGATION</div>
                    <div class="text-3xl font-black text-indigo-600 tracking-tight">{{ $qrCodeName }}</div>
                </div>

                <div class="flex flex-col items-center justify-center p-4 bg-white mb-4">
                    @if($qrCodeUuid)
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data={{ route('attendant.scan', ['code' => $qrCodeUuid]) }}"
                            alt="QR Code" class="h-auto w-full max-w-[180px]" />
                    @endif
                </div>

                <div class="text-[10px] text-zinc-400 font-mono mb-6">
                    VALID PASS: {{ $qrCodeUuid }}
                </div>

                <div class="text-xs font-bold text-zinc-500 uppercase tracking-widest border border-zinc-200 rounded-lg px-3 py-2 inline-block">
                    DISPLAY ON DASHBOARD
                </div>
            </div>

            <div class="flex justify-center gap-2 no-print">
                <flux:button variant="ghost" wire:click="$set('qrModalOpen', false)">Close</flux:button>
                <flux:button variant="primary"
                    onclick="window.open('{{ $qrCodeCongregationId ? route('admin.congregations.print', $qrCodeCongregationId) : '#' }}', '_blank')"
                    icon="printer">
                    Open Print Page
                </flux:button>
            </div>
        </div>
    </flux:modal>


</div>