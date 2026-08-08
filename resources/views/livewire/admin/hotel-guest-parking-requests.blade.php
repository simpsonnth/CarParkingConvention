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

    <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900/50">
        @if ($statusFilter === 'pending')
            {{ __('management.hotel_guest_parking.total_pending', ['count' => $pendingCount]) }}
        @elseif ($statusFilter === 'approved')
            {{ __('management.hotel_guest_parking.total_approved', ['count' => $approvedCount]) }}
        @elseif ($statusFilter === 'rejected')
            {{ __('management.hotel_guest_parking.total_rejected', ['count' => $rejectedCount]) }}
        @else
            {{ __('management.hotel_guest_parking.total', ['count' => $total]) }}
        @endif
    </div>

    <flux:separator />

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                <tr>
                    <th class="px-4 py-3">{{ __('management.hotel_guest_parking.col_status') }}</th>
                    <th class="px-4 py-3">{{ __('management.hotel_guest_parking.col_submitted') }}</th>
                    <th class="px-4 py-3">{{ __('management.hotel_guest_parking.col_name') }}</th>
                    <th class="px-4 py-3">{{ __('management.hotel_guest_parking.col_vehicle') }}</th>
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
                                <div class="mt-1.5 max-w-[220px] space-y-0.5 font-sans normal-case tracking-normal">
                                    <flux:badge size="sm" color="sky">
                                        {{ __('management.hotel_guest_parking.has_ticket_badge') }}
                                    </flux:badge>
                                    <div class="text-xs text-sky-800 dark:text-sky-200">
                                        {{ __('management.hotel_guest_parking.has_ticket_detail', [
                                            'ticket' => $existingTicket->ticketNumber(),
                                            'congregation' => $existingTicket->congregation ?: '—',
                                            'car_park' => $existingTicket->carPark?->name ?: '—',
                                        ]) }}
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
</div>
