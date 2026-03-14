<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('registrations.title') }}</flux:heading>
            <flux:subheading>{{ __('registrations.subtitle') }}</flux:subheading>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.registrations.export') }}" class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700" download>
                <flux:icon name="arrow-down-tray" class="size-4" />
                {{ __('registrations.export_excel') }}
            </a>
            <a href="{{ route('admin.registrations.trash') }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">
                <flux:icon name="trash" class="size-4" />
                {{ __('registrations.recycle_bin') }}
            </a>
            @if(count($selectedIds) > 0)
                <button type="button" wire:click="bulkDelete" wire:confirm="{{ __('registrations.bulk_delete_confirm') }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 shadow-sm hover:bg-red-100 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200 dark:hover:bg-red-900/50">
                    <flux:icon name="trash" class="size-4" />
                    {{ __('registrations.delete_selected') }} ({{ count($selectedIds) }})
                </button>
                <button type="button" wire:click="$set('selectedIds', [])"
                    class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">
                    {{ __('registrations.cancel') }}
                </button>
            @else
                <button type="button" disabled class="inline-flex cursor-not-allowed items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm font-medium text-zinc-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-500">
                    <flux:icon name="trash" class="size-4" />
                    {{ __('registrations.delete_selected') }}
                </button>
            @endif
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('registrations.search') }}" class="w-full min-w-0 sm:min-w-[180px]" />
        </div>
    </div>

    @if(count($selectedIds) > 0)
        <div class="flex flex-wrap items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-800 dark:bg-amber-900/20">
            <span class="text-sm font-medium text-amber-800 dark:text-amber-200 basis-full sm:basis-auto">{{ count($selectedIds) }} {{ __('registrations.selected') }}</span>
            <button type="button" wire:click="bulkDelete" wire:confirm="{{ __('registrations.bulk_delete_confirm') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700">
                <flux:icon name="trash" class="size-4" />
                {{ __('registrations.delete_selected') }}
            </button>
            <button type="button" wire:click="$set('selectedIds', [])"
                class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600">
                {{ __('registrations.cancel') }}
            </button>
        </div>
    @endif

    <flux:separator />

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700 -mx-4 sm:mx-0">
        <table class="w-full min-w-[800px] text-left text-sm">
            <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                <tr>
                    <th class="w-10 px-4 py-3">
                        <input type="checkbox" wire:click="toggleSelectAll"
                            {{ count($registrations->items()) > 0 && count(array_intersect($selectedIds, $registrations->pluck('id')->all())) === count($registrations->items()) ? 'checked' : '' }}
                            class="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                    </th>
                    <th class="px-6 py-3">{{ __('registrations.date') }}</th>
                    <th class="px-6 py-3">{{ __('registrations.name') }}</th>
                    <th class="px-6 py-3">{{ __('registrations.congregation') }}</th>
                    <th class="px-6 py-3">{{ __('registrations.type') }}</th>
                    <th class="px-6 py-3">{{ __('registrations.vehicle_reg') }}</th>
                    <th class="px-6 py-3">{{ __('registrations.contact') }}</th>
                    <th class="px-6 py-3">{{ __('registrations.elderly_infirm') }}</th>
                    <th class="px-6 py-3">{{ __('registrations.days') }}</th>
                    <th class="px-6 py-3 text-end">{{ __('registrations.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                @forelse($registrations as $reg)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
                        <td class="w-10 px-4 py-4">
                            <input type="checkbox" wire:click.prevent="toggleSelect({{ $reg->id }})"
                                {{ in_array($reg->id, $selectedIds) ? 'checked' : '' }}
                                class="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                        </td>
                        <td class="px-6 py-4 text-zinc-500 whitespace-nowrap">
                            {{ $reg->created_at->format('d M H:i') }}
                        </td>
                        <td class="px-6 py-4 font-medium text-zinc-900 dark:text-white">
                            {{ $reg->name }}
                        </td>
                        <td class="px-6 py-4 text-zinc-600 dark:text-zinc-300">
                            {{ $reg->congregation }}
                        </td>
                        <td class="px-6 py-4">
                            <flux:badge color="{{ ($reg->vehicle_type ?? 'car') === 'coach' ? 'purple' : 'zinc' }}">
                                {{ ($reg->vehicle_type ?? 'car') === 'coach' ? __('registrations.coach') : __('registrations.car') }}
                            </flux:badge>
                        </td>
                        <td class="px-6 py-4 font-mono text-zinc-600 dark:text-zinc-300">
                            <span class="bg-zinc-100 px-2 py-1 rounded text-xs dark:bg-zinc-700 font-bold tracking-wider">{{ $reg->vehicle_registration ?? '—' }}</span>
                        </td>
                        <td class="px-6 py-4 text-zinc-500">
                            {{ $reg->contact_number }}
                            <div class="text-xs text-zinc-400">{{ $reg->email }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($reg->elderly_infirm_parking ?? false)
                                <flux:badge color="amber">{{ __('registrations.yes') }}</flux:badge>
                            @else
                                <span class="text-zinc-400 text-xs">{{ __('registrations.no') }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex gap-1 flex-wrap">
                                @foreach($reg->days ?? [] as $day)
                                    <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10 dark:bg-indigo-400/10 dark:text-indigo-400 dark:ring-indigo-400/30">{{ substr($day, 0, 3) }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-6 py-4 text-end">
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />

                                <flux:menu>
                                    <flux:menu.item wire:click="edit({{ $reg->id }})" icon="pencil">{{ __('registrations.edit') }}</flux:menu.item>
                                    <flux:menu.item wire:click="delete({{ $reg->id }})"
                                        wire:confirm="{{ __('registrations.delete_confirm') }}" icon="trash"
                                        variant="danger">{{ __('registrations.delete') }}</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-6 py-12 text-center text-zinc-500">
                            <div class="flex flex-col items-center justify-center">
                                <flux:icon name="clipboard-document-list" class="size-10 text-zinc-300 mb-2" />
                                <p>{{ __('registrations.no_registrations') }}</p>
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
    <flux:modal wire:model="modalOpen" class="w-[calc(100vw-2rem)] max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('registrations.edit_modal_title') }}</flux:heading>
                <flux:subheading>{{ __('registrations.edit_modal_subtitle') }}</flux:subheading>
            </div>

            <flux:input wire:model="name" label="{{ __('registrations.name') }}" placeholder="{{ __('registrations.full_name') }}" />

            <flux:select wire:model="congregation" label="{{ __('registrations.congregation') }}" placeholder="{{ __('registrations.select_congregation') }}">
                @foreach($congregations as $name)
                    <option value="{{ $name }}">{{ $name }}</option>
                @endforeach
            </flux:select>

            <div class="space-y-2">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('registrations.vehicle_type') }}</label>
                <div class="flex gap-2">
                    <button type="button" wire:click="$set('vehicleType', 'car')" @class([
                        'flex-1 px-3 py-2 rounded-lg text-sm font-medium border transition',
                        'bg-indigo-500 text-white border-indigo-600' => $vehicleType === 'car',
                        'bg-white dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700' => $vehicleType !== 'car',
                    ])>{{ __('registrations.car') }}</button>
                    <button type="button" wire:click="$set('vehicleType', 'coach')" @class([
                        'flex-1 px-3 py-2 rounded-lg text-sm font-medium border transition',
                        'bg-indigo-500 text-white border-indigo-600' => $vehicleType === 'coach',
                        'bg-white dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700' => $vehicleType !== 'coach',
                    ])>{{ __('registrations.coach') }}</button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:input wire:model="vehicleReg" label="{{ __('registrations.vehicle_reg') }}" placeholder="{{ __('registrations.registration') }}" />
                <flux:input wire:model="contactNumber" label="{{ __('registrations.contact_number') }}" placeholder="" />
            </div>

            <flux:input wire:model="email" label="{{ __('registrations.contact') }}" type="email" placeholder="{{ __('registrations.email_optional') }}" />

            <div class="space-y-2">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('registrations.elderly_infirm_parking') }}</label>
                <div class="flex gap-2">
                    <button type="button" wire:click="$set('elderlyInfirmParking', '1')" @class([
                        'flex-1 px-3 py-2 rounded-lg text-sm font-medium border transition',
                        'bg-indigo-500 text-white border-indigo-600' => $elderlyInfirmParking === '1' || $elderlyInfirmParking === true,
                        'bg-white dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700' => $elderlyInfirmParking !== '1' && $elderlyInfirmParking !== true,
                    ])>{{ __('registrations.yes') }}</button>
                    <button type="button" wire:click="$set('elderlyInfirmParking', '0')" @class([
                        'flex-1 px-3 py-2 rounded-lg text-sm font-medium border transition',
                        'bg-indigo-500 text-white border-indigo-600' => $elderlyInfirmParking === '0' || $elderlyInfirmParking === false,
                        'bg-white dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700' => $elderlyInfirmParking !== '0' && $elderlyInfirmParking !== false,
                    ])>{{ __('registrations.no') }}</button>
                </div>
            </div>

            <div class="space-y-2">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('registrations.days_attending') }}</label>
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
                <flux:button variant="ghost" wire:click="$set('modalOpen', false)">{{ __('registrations.cancel') }}</flux:button>
                <flux:button variant="primary" wire:click="save">{{ __('registrations.save_changes') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
