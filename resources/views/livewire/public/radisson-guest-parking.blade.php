<div
    class="min-h-screen flex flex-col items-center justify-center p-4 sm:p-6 bg-zinc-50 dark:bg-zinc-900"
    x-data="{
        stillThereOpen: false,
        idleMs: 5 * 60 * 1000,
        timer: null,
        startTimer() {
            this.clearTimer();
            this.timer = setTimeout(() => { this.stillThereOpen = true }, this.idleMs);
        },
        clearTimer() {
            if (this.timer) {
                clearTimeout(this.timer);
                this.timer = null;
            }
        },
        stayHere() {
            this.stillThereOpen = false;
            this.startTimer();
        }
    }"
    x-init="startTimer()"
>
    <div
        class="w-full max-w-lg bg-white dark:bg-zinc-800 rounded-3xl shadow-xl p-4 sm:p-8 border border-zinc-100 dark:border-zinc-700">

        <div class="flex flex-wrap justify-end gap-2 mb-6">
            <a href="{{ route('locale.set', 'en') }}" class="text-sm font-medium px-3 py-1.5 rounded-lg {{ app()->getLocale() === 'en' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">English</a>
            <a href="{{ route('locale.set', 'pt') }}" class="text-sm font-medium px-3 py-1.5 rounded-lg {{ app()->getLocale() === 'pt' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">Português</a>
            <a href="{{ route('locale.set', 'es') }}" class="text-sm font-medium px-3 py-1.5 rounded-lg {{ app()->getLocale() === 'es' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">Español</a>
        </div>

        <div class="text-center mb-8">
            <h1 class="text-3xl font-black text-zinc-900 dark:text-white tracking-tight mb-2">{{ __('radisson_guest_parking.title') }}</h1>
            <p class="text-zinc-500 dark:text-zinc-400">{{ __('radisson_guest_parking.subtitle') }}</p>
        </div>

        @if($submitted)
            <div
                class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-2xl p-6 text-center">
                <div class="flex justify-center mb-4">
                    <div class="rounded-full bg-green-100 dark:bg-green-900 p-3">
                        <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </div>
                <h3 class="text-xl font-bold text-green-800 dark:text-green-200 mb-2">{{ __('radisson_guest_parking.complete_title') }}</h3>
                <p class="text-green-700 dark:text-green-300 mb-4">{{ __('radisson_guest_parking.complete_body') }}</p>
                <p class="text-sm text-green-800/80 dark:text-green-200/80 mb-6">
                    {{ __('radisson_guest_parking.complete_change_hint', ['code' => \App\Models\HotelGuestParkingRequest::PUBLIC_CODE]) }}
                </p>
                <div class="flex flex-col gap-3">
                    <a href="{{ route('management.radisson-parking-check') }}"
                        class="inline-flex items-center justify-center rounded-xl bg-teal-700 px-4 py-3.5 text-base font-bold text-white hover:bg-teal-600">
                        {{ __('radisson_guest_parking.check_registration') }}
                    </a>
                    <a href="{{ route('management.ticket-change-request', ['guest' => 'radisson']) }}"
                        class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700">
                        {{ __('radisson_guest_parking.complete_change_link') }}
                    </a>
                    <button wire:click="submitAnother"
                        class="text-sm font-semibold text-green-800 dark:text-green-200 hover:underline">
                        {{ __('radisson_guest_parking.submit_another') }}
                    </button>
                </div>
            </div>
        @else
            <form wire:submit="submit" class="space-y-6">
                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">
                        {{ __('radisson_guest_parking.full_name') }}
                    </label>
                    <input type="text" wire:model="name"
                        class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 transition py-3 px-4"
                        placeholder="{{ __('radisson_guest_parking.full_name_placeholder') }}">
                    @error('name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">
                            {{ __('radisson_guest_parking.contact_number') }}
                        </label>
                        <input type="tel" wire:model="contactNumber"
                            class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 transition py-3 px-4"
                            placeholder="{{ __('radisson_guest_parking.contact_placeholder') }}">
                        @error('contactNumber') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">
                            {{ __('radisson_guest_parking.email') }}
                        </label>
                        <input type="email" wire:model.live.debounce.300ms="email"
                            class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 transition py-3 px-4"
                            placeholder="{{ __('radisson_guest_parking.email_placeholder') }}">
                        @error('email') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                @if($this->duplicateEmailExistingRegistration)
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20" role="status">
                        <p class="text-sm font-semibold text-amber-900 dark:text-amber-100">{{ __('register.duplicate_email_warning_title') }}</p>
                        <p class="mt-1 text-sm text-amber-800 dark:text-amber-200">{{ __('register.duplicate_email_warning_body', ['name' => $this->duplicateEmailExistingRegistration->name, 'congregation' => $this->duplicateEmailExistingRegistration->congregation ?: '—']) }}</p>
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('radisson_guest_parking.vehicle_registration') }} <span class="text-red-500">*</span></label>
                    <input type="text" wire:model.live.debounce.300ms="vehicleReg"
                        class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 transition py-3 px-4 uppercase font-mono tracking-wider"
                        placeholder="{{ __('radisson_guest_parking.vehicle_reg_placeholder') }}">
                    @error('vehicleReg') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                @if($this->duplicateVehicleRegistrationConflict)
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20" role="status">
                        <p class="text-sm font-semibold text-amber-900 dark:text-amber-100">{{ __('radisson_guest_parking.existing_ticket_title') }}</p>
                        <p class="mt-1 text-sm text-amber-800 dark:text-amber-200">{{ __('radisson_guest_parking.existing_ticket_body', [
                            'name' => $this->duplicateVehicleRegistrationConflict->name,
                            'congregation' => $this->duplicateVehicleRegistrationConflict->congregation ?: '—',
                        ]) }}</p>
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-3">{{ __('radisson_guest_parking.days_staying') }}</label>
                    <label class="flex items-center p-3 mb-3 border border-indigo-200 dark:border-indigo-700 rounded-xl hover:bg-indigo-50/50 dark:hover:bg-indigo-900/10 cursor-pointer transition">
                        <input type="checkbox" wire:click.prevent="toggleAllDays"
                            {{ count($days) === count(\App\Models\HotelGuestParkingRequest::ALLOWED_DAYS) ? 'checked' : '' }}
                            class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500 border-zinc-300">
                        <span class="ml-3 text-zinc-900 dark:text-white font-medium">{{ __('radisson_guest_parking.select_all_days') }}</span>
                    </label>
                    <div class="space-y-3">
                        @foreach(\App\Models\HotelGuestParkingRequest::ALLOWED_DAYS as $day)
                            <label
                                class="flex items-center p-3 border border-zinc-200 dark:border-zinc-700 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-700/50 cursor-pointer transition">
                                <input type="checkbox" wire:model="days" value="{{ $day }}"
                                    class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500 border-zinc-300">
                                <span class="ml-3 text-zinc-900 dark:text-white font-medium">{{ __('radisson_guest_parking.day_'.strtolower($day)) }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('days') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="pt-4">
                    <button type="submit"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 px-6 rounded-xl transition transform active:scale-[0.98] shadow-lg shadow-indigo-500/20">
                        {{ __('radisson_guest_parking.submit') }}
                    </button>
                    <div wire:loading wire:target="submit" class="text-center mt-2 text-zinc-400 text-sm">
                        {{ __('radisson_guest_parking.processing') }}
                    </div>
                </div>
            </form>
        @endif

        <div class="mt-8 text-center">
            <p class="text-xs text-zinc-400">
                &copy; {{ date('Y') }} {{ __('radisson_guest_parking.footer') }}
            </p>
        </div>
    </div>

    <div
        x-show="stillThereOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/60 p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="radisson-still-there-title"
    >
        <div class="w-full max-w-md rounded-3xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-700 dark:bg-zinc-800">
            <h2 id="radisson-still-there-title" class="text-center text-2xl font-black text-zinc-900 dark:text-white">
                {{ __('radisson_guest_parking.still_there_title') }}
            </h2>
            <p class="mt-3 text-center text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('radisson_guest_parking.still_there_body') }}
            </p>
            <div class="mt-6 flex flex-col gap-3">
                <button
                    type="button"
                    x-on:click="stayHere()"
                    class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-3.5 text-base font-bold text-white hover:bg-indigo-500"
                >
                    {{ __('radisson_guest_parking.still_there_yes') }}
                </button>
                <a
                    href="{{ route('management.radisson-parking-check') }}"
                    class="inline-flex w-full items-center justify-center rounded-xl border border-teal-300 bg-teal-50 px-4 py-3.5 text-base font-bold text-teal-800 hover:bg-teal-100 dark:border-teal-700 dark:bg-teal-950/40 dark:text-teal-100 dark:hover:bg-teal-900/40"
                >
                    {{ __('radisson_guest_parking.check_registration') }}
                </a>
            </div>
        </div>
    </div>
</div>
