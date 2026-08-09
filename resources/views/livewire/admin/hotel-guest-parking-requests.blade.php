<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('management.hotel_guest_parking.title') }}</flux:heading>
            <flux:subheading>{{ __('management.hotel_guest_parking.subtitle') }}</flux:subheading>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <select wire:model.live="perPage"
                class="block w-full sm:w-auto rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                <option value="25">25 {{ __('management.hotel_guest_parking.per_page') }}</option>
                <option value="50">50 {{ __('management.hotel_guest_parking.per_page') }}</option>
                <option value="100">100 {{ __('management.hotel_guest_parking.per_page') }}</option>
            </select>
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                placeholder="{{ __('management.hotel_guest_parking.search') }}" class="w-full min-w-0 sm:min-w-[220px]" />
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        <flux:button size="sm" variant="{{ $statusFilter === 'pending' ? 'primary' : 'ghost' }}"
            wire:click="setStatusFilter('pending')">
            {{ __('management.hotel_guest_parking.filter_pending') }}
            <span class="ms-1 tabular-nums opacity-80">({{ $pendingCount }})</span>
        </flux:button>
        <flux:button size="sm" variant="{{ $statusFilter === 'approved' ? 'primary' : 'ghost' }}"
            wire:click="setStatusFilter('approved')">
            {{ __('management.hotel_guest_parking.filter_approved') }}
            <span class="ms-1 tabular-nums opacity-80">({{ $approvedCount }})</span>
        </flux:button>
        <flux:button size="sm" variant="{{ $statusFilter === 'rejected' ? 'primary' : 'ghost' }}"
            wire:click="setStatusFilter('rejected')">
            {{ __('management.hotel_guest_parking.filter_rejected') }}
            <span class="ms-1 tabular-nums opacity-80">({{ $rejectedCount }})</span>
        </flux:button>
        <flux:button size="sm" variant="{{ $statusFilter === 'all' ? 'primary' : 'ghost' }}"
            wire:click="setStatusFilter('all')">
            {{ __('management.hotel_guest_parking.filter_all') }}
            <span class="ms-1 tabular-nums opacity-80">({{ $total }})</span>
        </flux:button>
    </div>

    <div class="flex flex-wrap gap-2">
        <flux:button size="sm" variant="{{ $ticketFilter === 'any' ? 'primary' : 'ghost' }}"
            wire:click="setTicketFilter('any')">
            {{ __('management.hotel_guest_parking.filter_ticket_any') }}
        </flux:button>
        <flux:button size="sm" variant="{{ $ticketFilter === 'has_ticket' ? 'primary' : 'ghost' }}"
            wire:click="setTicketFilter('has_ticket')">
            {{ __('management.hotel_guest_parking.filter_has_ticket') }}
            <span class="ms-1 tabular-nums opacity-80">({{ $hasTicketPendingCount }})</span>
        </flux:button>
        <flux:button size="sm" variant="{{ $ticketFilter === 'no_ticket' ? 'primary' : 'ghost' }}"
            wire:click="setTicketFilter('no_ticket')">
            {{ __('management.hotel_guest_parking.filter_no_ticket') }}
            <span class="ms-1 tabular-nums opacity-80">({{ $noTicketPendingCount }})</span>
        </flux:button>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900/50">
        @if ($ticketFilter === 'has_ticket')
            {{ __('management.hotel_guest_parking.total_has_ticket_pending', ['count' => $hasTicketPendingCount]) }}
            @if ($statusFilter !== 'pending' && $statusFilter !== 'all')
                <span class="text-zinc-500">· {{ __('management.hotel_guest_parking.filter_combined_hint') }}</span>
            @endif
        @elseif ($ticketFilter === 'no_ticket')
            {{ __('management.hotel_guest_parking.total_no_ticket_pending', ['count' => $noTicketPendingCount]) }}
            @if ($statusFilter !== 'pending' && $statusFilter !== 'all')
                <span class="text-zinc-500">· {{ __('management.hotel_guest_parking.filter_combined_hint') }}</span>
            @endif
        @elseif ($statusFilter === 'pending')
            {{ __('management.hotel_guest_parking.total_pending', ['count' => $pendingCount]) }}
            <span class="text-zinc-500">· {{ __('management.hotel_guest_parking.total_has_ticket_pending', ['count' => $hasTicketPendingCount]) }}</span>
        @elseif ($statusFilter === 'approved')
            {{ __('management.hotel_guest_parking.total_approved', ['count' => $approvedCount]) }}
        @elseif ($statusFilter === 'rejected')
            {{ __('management.hotel_guest_parking.total_rejected', ['count' => $rejectedCount]) }}
        @else
            {{ __('management.hotel_guest_parking.total', ['count' => $total]) }}
            <span class="text-zinc-500">· {{ __('management.hotel_guest_parking.total_has_ticket_pending', ['count' => $hasTicketPendingCount]) }}</span>
        @endif
    </div>

    @if ($carParkCapacityRows->isNotEmpty())
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-3 flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">
                        {{ __('management.hotel_guest_parking.capacity_heading') }}
                    </h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('management.hotel_guest_parking.capacity_help') }}
                    </p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="text-xs uppercase text-zinc-500 dark:text-zinc-400">
                        <tr>
                            <th class="pb-2 pe-4 font-medium">{{ __('management.hotel_guest_parking.col_car_park') }}</th>
                            <th class="pb-2 pe-4 font-medium">{{ __('management.convention_day.friday') }}</th>
                            <th class="pb-2 pe-4 font-medium">{{ __('management.convention_day.saturday') }}</th>
                            <th class="pb-2 font-medium">{{ __('management.convention_day.sunday') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/80">
                        @php
                            $capacityOverTotals = ['friday' => 0, 'saturday' => 0, 'sunday' => 0];
                        @endphp
                        @foreach ($carParkCapacityRows as $park)
                            @php
                                $dayColumns = [
                                    'friday' => ['assigned' => (int) $park->assigned_friday, 'capacity' => (int) $park->capacity_friday],
                                    'saturday' => ['assigned' => (int) $park->assigned_saturday, 'capacity' => (int) $park->capacity_saturday],
                                    'sunday' => ['assigned' => (int) $park->assigned_sunday, 'capacity' => (int) $park->capacity_sunday],
                                ];
                                foreach ($dayColumns as $dayKey => $day) {
                                    $capacityOverTotals[$dayKey] += max(0, $day['assigned'] - $day['capacity']);
                                }
                            @endphp
                            <tr wire:key="hgp-capacity-{{ $park->id }}">
                                <td class="py-2.5 pe-4 font-medium text-zinc-900 dark:text-white">
                                    <span class="inline-flex items-center gap-2">
                                        <span class="h-2.5 w-2.5 shrink-0 rounded-full border border-zinc-200 dark:border-zinc-600"
                                            style="background-color: {{ $park->color }}"></span>
                                        {{ $park->name }}
                                    </span>
                                </td>
                                @foreach ($dayColumns as $day)
                                    @php
                                        $assigned = $day['assigned'];
                                        $capacity = $day['capacity'];
                                        $overBy = max(0, $assigned - $capacity);
                                    @endphp
                                    <td class="py-2.5 pe-4 last:pe-0">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <flux:badge size="sm" color="{{ $overBy > 0 ? 'red' : 'zinc' }}">
                                                {{ $assigned }} / {{ $capacity }}
                                            </flux:badge>
                                            @if ($overBy > 0)
                                                <span class="text-xs font-semibold text-red-600 dark:text-red-400">
                                                    {{ __('management.hotel_guest_parking.capacity_over_by', ['count' => $overBy]) }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-zinc-200 dark:border-zinc-600">
                        <tr>
                            <td class="pt-3 pe-4 text-sm font-semibold text-zinc-900 dark:text-white">
                                {{ __('management.hotel_guest_parking.capacity_total_over') }}
                            </td>
                            @foreach (['friday', 'saturday', 'sunday'] as $dayKey)
                                <td class="pt-3 pe-4 last:pe-0">
                                    @if ($capacityOverTotals[$dayKey] > 0)
                                        <span class="text-sm font-semibold text-red-600 dark:text-red-400">
                                            {{ __('management.hotel_guest_parking.capacity_over_by', ['count' => $capacityOverTotals[$dayKey]]) }}
                                        </span>
                                    @else
                                        <span class="text-sm text-zinc-400">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif

    <flux:separator />

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                <tr>
                    <th class="px-4 py-3">{{ __('management.hotel_guest_parking.col_status') }}</th>
                    <th class="px-4 py-3">{{ __('management.hotel_guest_parking.col_submitted') }}</th>
                    <th class="px-4 py-3">
                        <button type="button" wire:click="setSort('name')" class="inline-flex items-center gap-1 font-medium uppercase hover:text-zinc-700 dark:hover:text-zinc-300">
                            {{ __('management.hotel_guest_parking.col_name') }}
                            @if ($sortBy === 'name')
                                <flux:icon name="{{ $sortDir === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                            @endif
                        </button>
                    </th>
                    <th class="px-4 py-3">
                        <button type="button" wire:click="setSort('vehicle_registration')" class="inline-flex items-center gap-1 font-medium uppercase hover:text-zinc-700 dark:hover:text-zinc-300">
                            {{ __('management.hotel_guest_parking.col_vehicle') }}
                            @if ($sortBy === 'vehicle_registration')
                                <flux:icon name="{{ $sortDir === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                            @endif
                        </button>
                    </th>
                    <th class="px-4 py-3">{{ __('management.hotel_guest_parking.col_days') }}</th>
                    <th class="px-4 py-3">{{ __('management.hotel_guest_parking.col_actioned_by') }}</th>
                    <th class="px-4 py-3 text-end">{{ __('management.hotel_guest_parking.col_actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                @forelse ($rows as $row)
                    <tr wire:key="hgp-{{ $row->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if ($row->isPending())
                                <flux:badge color="amber">{{ __('management.hotel_guest_parking.status_pending') }}</flux:badge>
                            @elseif ($row->isApproved())
                                <flux:badge color="green">{{ __('management.hotel_guest_parking.status_approved') }}</flux:badge>
                            @else
                                <flux:badge color="rose">{{ __('management.hotel_guest_parking.status_rejected') }}</flux:badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-zinc-600 dark:text-zinc-300">
                            {{ $row->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                        </td>
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">
                            {{ $row->name }}
                            @if (! empty($duplicateVehicleRegs[$row->vehicle_registration]))
                                <flux:badge size="sm" color="amber" class="ms-1 align-middle">
                                    {{ __('management.hotel_guest_parking.duplicate_badge') }}
                                </flux:badge>
                            @endif
                            <div class="mt-1 text-xs font-normal text-zinc-500 truncate max-w-[200px]">{{ $row->email }}</div>
                            <div class="mt-0.5 text-xs font-normal text-zinc-500">{{ $row->contact_number }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap font-mono text-zinc-700 dark:text-zinc-200">
                            {{ $row->vehicle_registration }}
                            @php
                                $existingTicket = $existingTicketsByVehicleReg[$row->vehicle_registration] ?? null;
                            @endphp
                            @if ($row->isPending() && $existingTicket)
                                @php
                                    $existingCarPark = $existingTicketCarParkByVehicleReg[$row->vehicle_registration] ?? null;
                                @endphp
                                <div class="mt-1.5 max-w-[240px] space-y-1 font-sans normal-case tracking-normal">
                                    <flux:badge size="sm" color="sky">
                                        {{ __('management.hotel_guest_parking.has_ticket_badge') }}
                                    </flux:badge>
                                    <div class="text-xs text-sky-800 dark:text-sky-200">
                                        {{ __('management.hotel_guest_parking.has_ticket_detail', [
                                            'ticket' => $existingTicket->ticketNumber(),
                                            'congregation' => $existingTicket->congregation ?: '—',
                                        ]) }}
                                    </div>
                                    <div class="text-xs font-semibold text-zinc-800 dark:text-zinc-100">
                                        {{ __('management.hotel_guest_parking.has_ticket_car_park') }}:
                                        @if ($existingCarPark)
                                            <x-car-park-badge :park="$existingCarPark" class="ms-1 align-middle" />
                                        @else
                                            <span class="font-normal text-zinc-500">—</span>
                                        @endif
                                    </div>
                                    <div class="text-xs font-semibold">
                                        @if ($existingTicket->elderly_infirm_parking)
                                            <flux:badge size="sm" color="amber">
                                                {{ __('management.hotel_guest_parking.has_ticket_elderly_yes') }}
                                            </flux:badge>
                                        @else
                                            <flux:badge size="sm" color="zinc">
                                                {{ __('management.hotel_guest_parking.has_ticket_elderly_no') }}
                                            </flux:badge>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">
                            <div class="flex flex-wrap gap-1">
                                @foreach ($row->days ?? [] as $day)
                                    <flux:badge size="sm" color="zinc">{{ $day }}</flux:badge>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-zinc-600 dark:text-zinc-300">
                            @if (! $row->isPending() && $row->actionedByUser)
                                {{ $row->actionedByUser->name }}
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-end">
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                <a href="{{ route('admin.hotel-guest-parking.show', $row) }}" wire:navigate
                                    class="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-700">
                                    {{ __('management.hotel_guest_parking.view') }}
                                </a>
                                @can('hotel-guest-parking.manage')
                                    <button type="button" wire:click="openEditModal({{ $row->id }})"
                                        class="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-700">
                                        {{ __('management.hotel_guest_parking.edit') }}
                                    </button>
                                    @if ($row->isPending())
                                        <a href="{{ route('admin.hotel-guest-parking.show', $row) }}" wire:navigate
                                            class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700">
                                            {{ __('management.hotel_guest_parking.approve') }}
                                        </a>
                                        <button type="button" wire:click="decline({{ $row->id }})"
                                            wire:confirm="{{ __('management.hotel_guest_parking.confirm_decline') }}"
                                            class="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium text-rose-700 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-950/40">
                                            {{ __('management.hotel_guest_parking.decline') }}
                                        </button>
                                    @elseif ($row->isApproved() && $row->parking_registration_id)
                                        <button type="button" wire:click="openResendModal({{ $row->id }})"
                                            class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700">
                                            {{ __('management.hotel_guest_parking.resend') }}
                                        </button>
                                    @endif
                                    <button type="button" wire:click="delete({{ $row->id }})"
                                        wire:confirm="{{ $row->parking_registration_id ? __('management.hotel_guest_parking.confirm_delete_with_registration') : __('management.hotel_guest_parking.confirm_delete') }}"
                                        class="inline-flex items-center rounded-lg bg-rose-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-rose-700">
                                        {{ __('management.hotel_guest_parking.delete') }}
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-zinc-500 dark:text-zinc-400">
                            {{ __('management.hotel_guest_parking.empty') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $rows->links() }}
    </div>

    <flux:modal wire:model="editModalOpen" class="w-[calc(100vw-2rem)] max-w-lg">
        @include('livewire.admin.partials.hotel-guest-parking-edit-fields')
    </flux:modal>

    <flux:modal wire:model="resendModalOpen" class="w-[calc(100vw-2rem)] max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('management.hotel_guest_parking.resend_title') }}</flux:heading>
                <flux:subheading>
                    {{ __('management.hotel_guest_parking.resend_help', ['name' => $resendGuestName !== '' ? $resendGuestName : '—']) }}
                </flux:subheading>
            </div>

            @if ($resendOriginalEmail !== '')
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900/50">
                    <p class="text-zinc-600 dark:text-zinc-300">
                        {{ __('management.hotel_guest_parking.resend_original_label') }}:
                        <span class="font-medium text-zinc-900 dark:text-white">{{ $resendOriginalEmail }}</span>
                    </p>
                    <button type="button" wire:click="useOriginalResendEmail"
                        class="mt-2 text-sm font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">
                        {{ __('management.hotel_guest_parking.resend_use_original') }}
                    </button>
                </div>
            @endif

            <div class="space-y-2">
                <flux:input
                    wire:model="resendEmailTo"
                    id="hotelGuestResendEmailTo"
                    type="text"
                    inputmode="email"
                    label="{{ __('management.hotel_guest_parking.resend_email_label') }}"
                    placeholder="guest@example.com"
                    autocomplete="off"
                    data-1p-ignore
                    data-lpignore="true"
                    data-bwignore
                    data-form-type="other"
                />
                @error('resendEmailTo')
                    <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                @if (count($this->ticketEmailCcs) > 0)
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('management.hotel_guest_parking.resend_cc_label') }}:
                        <span class="font-medium text-zinc-700 dark:text-zinc-200">{{ implode(', ', $this->ticketEmailCcs) }}</span>
                    </p>
                @endif
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="closeResendModal" wire:loading.attr="disabled">
                    {{ __('management.hotel_guest_parking.close') }}
                </flux:button>
                <flux:button variant="primary" wire:click="resendTicket" wire:loading.attr="disabled" wire:target="resendTicket">
                    <span wire:loading.remove wire:target="resendTicket">{{ __('management.hotel_guest_parking.resend_send') }}</span>
                    <span wire:loading wire:target="resendTicket">{{ __('management.hotel_guest_parking.resend_sending') }}</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
