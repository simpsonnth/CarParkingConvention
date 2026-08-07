<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('admin.hotel-guest-parking') }}" wire:navigate
                class="mb-2 inline-flex items-center text-sm font-medium text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200">
                ← {{ __('management.hotel_guest_parking.back_to_list') }}
            </a>
            <flux:heading size="xl">{{ __('management.hotel_guest_parking.detail_title') }}</flux:heading>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('hotel-guest-parking.manage')
                @if ($hotelGuestParkingRequest->isPending())
                    <flux:button variant="primary" wire:click="openApproveModal" icon="check">
                        {{ __('management.hotel_guest_parking.approve') }}
                    </flux:button>
                    <flux:button variant="danger" wire:click="reject"
                        wire:confirm="{{ __('management.hotel_guest_parking.confirm_reject') }}">
                        {{ __('management.hotel_guest_parking.reject') }}
                    </flux:button>
                @endif
            @endcan
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <dl class="space-y-4 text-sm">
                <div>
                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('management.hotel_guest_parking.col_status') }}</dt>
                    <dd class="mt-1">
                        @if ($hotelGuestParkingRequest->isPending())
                            <flux:badge color="amber">{{ __('management.hotel_guest_parking.status_pending') }}</flux:badge>
                        @elseif ($hotelGuestParkingRequest->isApproved())
                            <flux:badge color="green">{{ __('management.hotel_guest_parking.status_approved') }}</flux:badge>
                        @else
                            <flux:badge color="rose">{{ __('management.hotel_guest_parking.status_rejected') }}</flux:badge>
                        @endif
                        @if ($hotelGuestParkingRequest->actioned_at)
                            <span class="ms-2 text-zinc-500">
                                {{ $hotelGuestParkingRequest->actioned_at->timezone(config('app.timezone'))->format('d M Y H:i') }}
                                @if ($hotelGuestParkingRequest->actionedByUser)
                                    · {{ $hotelGuestParkingRequest->actionedByUser->name }}
                                @endif
                            </span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('management.hotel_guest_parking.col_name') }}</dt>
                    <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $hotelGuestParkingRequest->name }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('management.hotel_guest_parking.col_phone') }}</dt>
                    <dd class="mt-1 text-zinc-800 dark:text-zinc-200">{{ $hotelGuestParkingRequest->contact_number }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('management.hotel_guest_parking.col_email') }}</dt>
                    <dd class="mt-1 text-zinc-800 dark:text-zinc-200">{{ $hotelGuestParkingRequest->email }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('management.hotel_guest_parking.col_vehicle') }}</dt>
                    <dd class="mt-1 font-mono text-zinc-800 dark:text-zinc-200">{{ $hotelGuestParkingRequest->vehicle_registration }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('management.hotel_guest_parking.col_days') }}</dt>
                    <dd class="mt-1 flex flex-wrap gap-1">
                        @foreach ($hotelGuestParkingRequest->days ?? [] as $day)
                            <flux:badge size="sm" color="zinc">{{ $day }}</flux:badge>
                        @endforeach
                    </dd>
                </div>
                <div>
                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('management.hotel_guest_parking.col_submitted') }}</dt>
                    <dd class="mt-1 text-zinc-800 dark:text-zinc-200">
                        {{ $hotelGuestParkingRequest->created_at?->timezone(config('app.timezone'))->format('d M Y H:i') }}
                    </dd>
                </div>
                @if ($hotelGuestParkingRequest->carPark)
                    <div>
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('management.hotel_guest_parking.col_car_park') }}</dt>
                        <dd class="mt-1 text-zinc-800 dark:text-zinc-200">{{ $hotelGuestParkingRequest->carPark->name }}</dd>
                    </div>
                @endif
                @if ($hotelGuestParkingRequest->parkingRegistration)
                    <div>
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('management.hotel_guest_parking.col_ticket') }}</dt>
                        <dd class="mt-1 tabular-nums text-zinc-800 dark:text-zinc-200">
                            #{{ $hotelGuestParkingRequest->parkingRegistration->ticketNumber() }}
                            <span class="text-zinc-500">· {{ $hotelGuestParkingRequest->parkingRegistration->congregation }}</span>
                        </dd>
                    </div>
                @endif
            </dl>
        </div>

        @can('hotel-guest-parking.manage')
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg" class="mb-2">{{ __('management.hotel_guest_parking.admin_notes') }}</flux:heading>
                <p class="mb-3 text-xs text-zinc-500 dark:text-zinc-400">{{ __('management.hotel_guest_parking.admin_notes_help') }}</p>
                <flux:textarea wire:model="adminNotes" rows="5"
                    placeholder="{{ __('management.hotel_guest_parking.admin_notes_placeholder') }}" />
                <div class="mt-3">
                    <flux:button variant="ghost" wire:click="saveAdminNotes">
                        {{ __('management.hotel_guest_parking.save_admin_notes') }}
                    </flux:button>
                </div>
                @error('reject')
                    <span class="mt-2 block text-xs text-red-500">{{ $message }}</span>
                @enderror
                @error('approve')
                    <span class="mt-2 block text-xs text-red-500">{{ $message }}</span>
                @enderror
            </div>
        @endcan
    </div>

    <flux:modal wire:model="approveModalOpen" class="w-[calc(100vw-2rem)] max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('management.hotel_guest_parking.approve_title') }}</flux:heading>
                <flux:subheading>{{ __('management.hotel_guest_parking.approve_help') }}</flux:subheading>
            </div>

            <div>
                <label for="approveCarParkId" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('management.hotel_guest_parking.approve_car_park') }}
                    <span class="text-red-500">*</span>
                </label>
                <select wire:model="approveCarParkId" id="approveCarParkId"
                    class="mt-2 block w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    <option value="">{{ __('management.hotel_guest_parking.select_car_park') }}</option>
                    @foreach ($this->carParks as $park)
                        <option value="{{ $park->id }}">{{ $park->name }}</option>
                    @endforeach
                </select>
                @error('approveCarParkId')
                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="closeApproveModal">
                    {{ __('management.hotel_guest_parking.close') }}
                </flux:button>
                <flux:button variant="primary" wire:click="approve"
                    wire:confirm="{{ __('management.hotel_guest_parking.confirm_approve') }}">
                    {{ __('management.hotel_guest_parking.approve_confirm') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
