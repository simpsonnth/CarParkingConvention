<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('extras.title') }}</flux:heading>
            <flux:subheading>{{ __('extras.subtitle') }}</flux:subheading>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @can('extras.manage')
                <flux:button variant="primary" wire:click="create" icon="plus">
                    {{ __('extras.add_extra') }}
                </flux:button>
            @endcan
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                placeholder="{{ __('extras.search') }}" class="w-full min-w-0 sm:min-w-[200px]" />
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        <flux:button size="sm" variant="{{ $statusFilter === 'pending' ? 'primary' : 'ghost' }}"
            wire:click="setStatusFilter('pending')">
            {{ __('extras.filter_pending') }}
        </flux:button>
        <flux:button size="sm" variant="{{ $statusFilter === 'actioned' ? 'primary' : 'ghost' }}"
            wire:click="setStatusFilter('actioned')">
            {{ __('extras.filter_actioned') }}
        </flux:button>
        <flux:button size="sm" variant="{{ $statusFilter === 'all' ? 'primary' : 'ghost' }}"
            wire:click="setStatusFilter('all')">
            {{ __('extras.filter_all') }}
        </flux:button>
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700 -mx-4 sm:mx-0">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                <tr>
                    <th class="px-4 py-3">{{ __('extras.col_status') }}</th>
                    <th class="px-4 py-3">{{ __('extras.col_name') }}</th>
                    <th class="px-4 py-3">{{ __('extras.col_congregation') }}</th>
                    <th class="px-4 py-3">{{ __('extras.col_vehicle') }}</th>
                    <th class="px-4 py-3">{{ __('extras.col_days') }}</th>
                    <th class="px-4 py-3">{{ __('extras.col_contact') }}</th>
                    <th class="px-4 py-3 text-end">{{ __('extras.col_actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                @forelse ($extras as $extra)
                    <tr wire:key="extra-{{ $extra->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if ($extra->isPending())
                                <flux:badge color="amber">{{ __('extras.status_pending') }}</flux:badge>
                            @else
                                <flux:badge color="green">{{ __('extras.status_actioned') }}</flux:badge>
                                @if ($extra->parkingRegistration)
                                    <div class="mt-1 text-xs text-zinc-500">
                                        {{ __('extras.ticket_no') }}:
                                        <span class="font-mono">{{ $extra->parkingRegistration->ticketNumber() }}</span>
                                    </div>
                                @endif
                            @endif
                        </td>
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">
                            {{ $extra->name }}
                            @if ($extra->elderly_infirm_parking)
                                <div class="text-xs text-amber-700 dark:text-amber-300">{{ __('extras.elderly_infirm') }}</div>
                            @endif
                            @if ($extra->notes)
                                <div class="mt-1 text-xs text-zinc-500 line-clamp-2">{{ $extra->notes }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $extra->congregation }}</td>
                        <td class="px-4 py-3 font-mono text-zinc-600 dark:text-zinc-300">
                            {{ $extra->vehicle_registration ?: '—' }}
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">
                            {{ implode(', ', $extra->days ?? []) }}
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">
                            <div>{{ $extra->contact_number }}</div>
                            @if ($extra->email)
                                <div class="text-xs text-zinc-500">{{ $extra->email }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-end">
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                @if ($extra->isPending())
                                    @can('extras.manage')
                                        <flux:button variant="primary" size="sm" wire:click="openActionModal({{ $extra->id }})">
                                            {{ __('extras.action') }}
                                        </flux:button>
                                        <flux:button variant="ghost" size="sm" wire:click="edit({{ $extra->id }})">
                                            {{ __('extras.edit') }}
                                        </flux:button>
                                        <flux:button variant="danger" size="sm" wire:click="delete({{ $extra->id }})"
                                            wire:confirm="{{ __('extras.confirm_delete') }}">
                                            {{ __('extras.delete') }}
                                        </flux:button>
                                    @endcan
                                @else
                                    @if ($extra->actioned_at)
                                        <span class="text-xs text-zinc-500">
                                            {{ $extra->actioned_at->format('d M Y H:i') }}
                                            @if ($extra->actionedByUser)
                                                · {{ $extra->actionedByUser->name }}
                                            @endif
                                        </span>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-zinc-500">
                            <div class="flex flex-col items-center justify-center">
                                <flux:icon name="queue-list" class="size-10 text-zinc-300 mb-2" />
                                <p>{{ __('extras.no_extras') }}</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $extras->links() }}
    </div>

    {{-- Create / Edit --}}
    <flux:modal wire:model="modalOpen" class="w-[calc(100vw-2rem)] max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('extras.edit_modal_title') : __('extras.create_modal_title') }}
                </flux:heading>
                <flux:subheading>
                    {{ $editingId ? __('extras.edit_modal_subtitle') : __('extras.create_modal_subtitle') }}
                </flux:subheading>
            </div>

            <flux:input wire:model="name" label="{{ __('extras.name') }}" placeholder="{{ __('extras.full_name') }}" />

            <flux:select wire:model="congregation" label="{{ __('extras.congregation') }}"
                placeholder="{{ __('extras.select_congregation') }}">
                @foreach ($this->congregations as $congName)
                    <option value="{{ $congName }}">{{ $congName }}</option>
                @endforeach
            </flux:select>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:input wire:model="vehicleReg" label="{{ __('extras.vehicle_reg') }}"
                    placeholder="{{ __('extras.registration') }}" />
                <flux:input wire:model="contactNumber" label="{{ __('extras.contact_number') }}" />
            </div>

            <flux:input wire:model="email" label="{{ __('extras.email') }}" type="email"
                placeholder="{{ __('extras.email_optional') }}" />

            <div class="space-y-2">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('extras.elderly_infirm') }}</label>
                <div class="flex gap-2">
                    <button type="button" wire:click="$set('elderlyInfirmParking', '1')" @class([
                        'flex-1 px-3 py-2 rounded-lg text-sm font-medium border transition',
                        'bg-indigo-500 text-white border-indigo-600' => $elderlyInfirmParking === '1',
                        'bg-white dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700' => $elderlyInfirmParking !== '1',
                    ])>{{ __('extras.yes') }}</button>
                    <button type="button" wire:click="$set('elderlyInfirmParking', '0')" @class([
                        'flex-1 px-3 py-2 rounded-lg text-sm font-medium border transition',
                        'bg-indigo-500 text-white border-indigo-600' => $elderlyInfirmParking === '0',
                        'bg-white dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700' => $elderlyInfirmParking !== '0',
                    ])>{{ __('extras.no') }}</button>
                </div>
            </div>

            <div class="space-y-2">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('extras.days_attending') }}</label>
                <div class="flex gap-2">
                    @foreach (['Friday', 'Saturday', 'Sunday'] as $day)
                        <button type="button" wire:click="toggleDay('{{ $day }}')" @class([
                            'px-3 py-1.5 rounded-lg text-sm font-medium transition-all border',
                            'bg-indigo-500 text-white border-indigo-600 shadow-sm' => in_array($day, $days, true),
                            'bg-white dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700' => ! in_array($day, $days, true),
                        ])>
                            {{ $day }}
                        </button>
                    @endforeach
                </div>
                @error('days')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>

            <div class="space-y-2">
                <label for="extraNotes" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('extras.notes') }}</label>
                <textarea wire:model="notes" id="extraNotes" rows="2"
                    class="block w-full rounded-lg border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                    placeholder="{{ __('extras.notes_placeholder') }}"></textarea>
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('modalOpen', false)">{{ __('extras.cancel') }}</flux:button>
                <flux:button variant="primary" wire:click="save">
                    {{ $editingId ? __('extras.save_changes') : __('extras.create_button') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Action: assign car park --}}
    <flux:modal wire:model="actionModalOpen" class="w-[calc(100vw-2rem)] max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('extras.action_modal_title') }}</flux:heading>
                <flux:subheading>{{ __('extras.action_modal_subtitle') }}</flux:subheading>
            </div>

            <div class="space-y-2">
                <label for="actionCarParkId" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('extras.car_park') }} <span class="text-red-500">*</span>
                </label>
                <select wire:model="actionCarParkId" id="actionCarParkId"
                    class="block w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    <option value="">{{ __('extras.select_car_park') }}</option>
                    @foreach ($this->carParks as $park)
                        <option value="{{ $park->id }}">{{ $park->name }}</option>
                    @endforeach
                </select>
                @error('actionCarParkId')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('actionModalOpen', false)">{{ __('extras.cancel') }}</flux:button>
                <flux:button variant="primary" wire:click="confirmAction">{{ __('extras.confirm_action') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
