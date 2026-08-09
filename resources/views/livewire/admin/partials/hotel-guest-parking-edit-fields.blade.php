<div class="space-y-6">
    <div>
        <flux:heading size="lg">{{ __('management.hotel_guest_parking.edit_title') }}</flux:heading>
        <flux:subheading>{{ __('management.hotel_guest_parking.edit_help') }}</flux:subheading>
    </div>

    <div class="space-y-4">
        <div>
            <flux:input
                wire:model="editName"
                label="{{ __('management.hotel_guest_parking.col_name') }}"
                autocomplete="off"
            />
            @error('editName')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <flux:input
                wire:model="editContactNumber"
                label="{{ __('management.hotel_guest_parking.col_phone') }}"
                autocomplete="off"
            />
            @error('editContactNumber')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <flux:input
                wire:model="editEmail"
                type="text"
                inputmode="email"
                label="{{ __('management.hotel_guest_parking.col_email') }}"
                autocomplete="off"
                data-1p-ignore
                data-lpignore="true"
                data-bwignore
                data-form-type="other"
            />
            @error('editEmail')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <flux:input
                wire:model="editVehicleRegistration"
                label="{{ __('management.hotel_guest_parking.col_vehicle') }}"
                class="font-mono uppercase"
                autocomplete="off"
            />
            @error('editVehicleRegistration')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <p class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                {{ __('management.hotel_guest_parking.col_days') }}
            </p>
            <div class="space-y-2">
                @foreach (\App\Models\HotelGuestParkingRequest::ALLOWED_DAYS as $day)
                    <label
                        class="flex cursor-pointer items-center rounded-lg border border-zinc-200 px-3 py-2 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-900/50">
                        <input type="checkbox" wire:model="editDays" value="{{ $day }}"
                            class="h-4 w-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="ms-3 text-sm text-zinc-900 dark:text-white">
                            {{ __('radisson_guest_parking.day_'.strtolower($day)) }}
                        </span>
                    </label>
                @endforeach
            </div>
            @error('editDays')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
            @error('editDays.*')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="flex justify-end gap-2">
        <flux:button variant="ghost" wire:click="closeEditModal" wire:loading.attr="disabled">
            {{ __('management.hotel_guest_parking.close') }}
        </flux:button>
        <flux:button variant="primary" wire:click="saveEdit" wire:loading.attr="disabled" wire:target="saveEdit">
            <span wire:loading.remove wire:target="saveEdit">{{ __('management.hotel_guest_parking.edit_save') }}</span>
            <span wire:loading wire:target="saveEdit">{{ __('management.hotel_guest_parking.edit_saving') }}</span>
        </flux:button>
    </div>
</div>
