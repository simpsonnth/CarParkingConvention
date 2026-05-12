<div class="min-h-screen flex flex-col items-center justify-center p-4 sm:p-6 bg-zinc-50 dark:bg-zinc-900">
    <div
        class="w-full max-w-lg bg-white dark:bg-zinc-800 rounded-3xl shadow-xl p-4 sm:p-8 border border-zinc-100 dark:border-zinc-700">

        {{-- Language (only on this page) --}}
        <div class="flex flex-wrap justify-end gap-2 mb-6">
            <a href="{{ route('locale.set', 'en') }}" class="text-sm font-medium px-3 py-1.5 rounded-lg {{ app()->getLocale() === 'en' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">English</a>
            <a href="{{ route('locale.set', 'pt') }}" class="text-sm font-medium px-3 py-1.5 rounded-lg {{ app()->getLocale() === 'pt' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">Português</a>
            <a href="{{ route('locale.set', 'es') }}" class="text-sm font-medium px-3 py-1.5 rounded-lg {{ app()->getLocale() === 'es' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">Español</a>
        </div>

        <div class="text-center mb-8">
            <h1 class="text-3xl font-black text-zinc-900 dark:text-white tracking-tight mb-2">{{ __('register.title') }}</h1>
            <p class="text-zinc-500 dark:text-zinc-400">{{ __('register.subtitle') }}</p>
        </div>

        @if($registered)
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
                <h3 class="text-xl font-bold text-green-800 dark:text-green-200 mb-2">{{ __('register.registration_complete') }}</h3>
                <p class="text-green-700 dark:text-green-300 mb-6">{{ __('register.thank_you') }}
                </p>
                <button wire:click="$set('registered', false)"
                    class="text-sm font-semibold text-green-800 dark:text-green-200 hover:underline">
                    {{ __('register.register_another') }}
                </button>
            </div>
        @else
            <form wire:submit="register" class="space-y-6">
                {{-- Congregation code first: quota gate drives visibility of the rest of the form --}}
                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('register.congregation_code') }}</label>
                    <input type="text" wire:model.live.debounce.300ms="congregationCode"
                        class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 transition py-3 px-4 font-mono"
                        placeholder="{{ __('register.congregation_code_placeholder') }}">
                    @error('congregationCode')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                    @if($this->resolvedCongregation)
                        <p class="mt-2 text-sm font-medium text-green-600 dark:text-green-400">
                            {{ __('register.congregation_label') }}: <strong>{{ $this->resolvedCongregation->name }}</strong>
                        </p>
                        @if($this->congregationQuotaGate['hide_remaining_fields'])
                            <div class="mt-4 rounded-2xl border p-4 {{ $this->congregationQuotaGate['no_survey'] ? 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20' : 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20' }}"
                                role="status">
                                @if($this->congregationQuotaGate['no_survey'])
                                    <p class="text-sm text-amber-900 dark:text-amber-100">{{ __('register.quota_no_survey') }}</p>
                                @else
                                    <p class="text-sm text-red-900 dark:text-red-100">{{ __('register.quota_preview_allocation_full', ['congregation' => $this->resolvedCongregation->name]) }}</p>
                                @endif
                            </div>
                        @endif
                    @elseif(trim($congregationCode) !== '')
                        <p class="mt-2 text-sm text-amber-600 dark:text-amber-400">{{ __('register.no_congregation_found') }}</p>
                    @endif
                </div>

                @if(!$this->congregationQuotaGate['hide_remaining_fields'])
                {{-- Car or Coach --}}
                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('register.car_or_coach') }}</label>
                    <div class="flex gap-4">
                        <button type="button" wire:click="$set('vehicleType', 'car')"
                            class="flex-1 flex items-center justify-center p-4 rounded-xl border-2 cursor-pointer transition font-semibold
                            {{ $vehicleType === 'car' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300' : 'border-zinc-200 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 text-zinc-700 dark:text-zinc-300' }}">
                            {{ __('register.car') }}
                        </button>
                        <button type="button" wire:click="$set('vehicleType', 'coach')"
                            class="flex-1 flex items-center justify-center p-4 rounded-xl border-2 cursor-pointer transition font-semibold
                            {{ $vehicleType === 'coach' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300' : 'border-zinc-200 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 text-zinc-700 dark:text-zinc-300' }}">
                            {{ __('register.coach') }}
                        </button>
                    </div>
                    @error('vehicleType') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                @if($vehicleType === 'coach')
                    {{-- Coach captain explainer: the parking survey already captured coach size, sharing arrangements, and shared congregations. --}}
                    <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-800 dark:bg-indigo-900/20">
                        <div class="flex items-start gap-3">
                            <div class="shrink-0 rounded-full bg-indigo-100 p-2 dark:bg-indigo-900/60">
                                <svg class="h-5 w-5 text-indigo-600 dark:text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="font-semibold text-indigo-900 dark:text-indigo-100">{{ __('register.coach_captain_intro_title') }}</p>
                                <p class="mt-1 text-sm text-indigo-800 dark:text-indigo-200">
                                    {{ $coachCaptainToBeAssigned ? __('register.coach_captain_intro_body_tba') : __('register.coach_captain_intro_body') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Coach captain TBA toggle: when the congregation has not yet appointed a captain, --}}
                    {{-- collect the congregation secretary's details as a temporary point of contact. --}}
                    <label class="flex items-start gap-3 p-4 rounded-xl border-2 border-zinc-200 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 cursor-pointer transition has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50 dark:has-[:checked]:bg-indigo-900/20">
                        <input type="checkbox" wire:model.live="coachCaptainToBeAssigned"
                            class="mt-0.5 w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500 border-zinc-300 dark:border-zinc-500"
                            aria-describedby="coach-captain-tba-help">
                        <span class="min-w-0">
                            <span class="block text-sm font-bold text-zinc-900 dark:text-white">{{ __('register.coach_captain_to_be_assigned') }}</span>
                            <span id="coach-captain-tba-help" class="block mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('register.coach_captain_to_be_assigned_help') }}</span>
                        </span>
                    </label>
                @endif

                @php
                    // Label resolution: secretary > coach captain > generic full-name fields.
                    $nameLabel = $vehicleType === 'coach'
                        ? ($coachCaptainToBeAssigned ? __('register.secretary_name') : __('register.coach_captain_name'))
                        : __('register.full_name');
                    $contactLabel = $vehicleType === 'coach'
                        ? ($coachCaptainToBeAssigned ? __('register.secretary_contact_number') : __('register.coach_captain_contact_number'))
                        : __('register.contact_number');
                    $emailLabel = $vehicleType === 'coach'
                        ? ($coachCaptainToBeAssigned ? __('register.secretary_email_address') : __('register.coach_captain_email_address'))
                        : __('register.email_address');
                @endphp

                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">
                        {{ $nameLabel }}
                    </label>
                    <input type="text" wire:model="name"
                        class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 transition py-3 px-4"
                        placeholder="{{ __('register.full_name_placeholder') }}">
                    @error('name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">
                            {{ $contactLabel }}
                        </label>
                        <input type="tel" wire:model="contactNumber"
                            class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 transition py-3 px-4"
                            placeholder="{{ __('register.contact_placeholder') }}">
                        @error('contactNumber') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">
                            {{ $emailLabel }}
                        </label>
                        <input type="email" wire:model.live.debounce.300ms="email"
                            class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 transition py-3 px-4"
                            placeholder="{{ __('register.email_placeholder') }}">
                        @error('email') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                @if($this->duplicateEmailExistingRegistration)
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20" role="status">
                        <p class="text-sm font-semibold text-amber-900 dark:text-amber-100">{{ __('register.duplicate_email_warning_title') }}</p>
                        <p class="mt-1 text-sm text-amber-800 dark:text-amber-200">{{ __('register.duplicate_email_warning_body', ['name' => $this->duplicateEmailExistingRegistration->name, 'congregation' => $this->duplicateEmailExistingRegistration->congregation ?: '—']) }}</p>
                    </div>
                @endif

                @if($vehicleType === 'car')
                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('register.vehicle_registration') }} <span class="text-red-500">*</span></label>
                    <input type="text" wire:model.live.debounce.300ms="vehicleReg"
                        class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 transition py-3 px-4 uppercase font-mono tracking-wider"
                        placeholder="{{ __('register.vehicle_reg_placeholder') }}">
                    @error('vehicleReg') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                @if($this->duplicateVehicleRegistrationConflict)
                    <div class="rounded-2xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20" role="alert">
                        <p class="text-sm font-semibold text-red-900 dark:text-red-100">{{ __('register.duplicate_vehicle_registration_live_title') }}</p>
                        <p class="mt-1 text-sm text-red-800 dark:text-red-200">{{ __('register.duplicate_vehicle_registration_live_body', ['name' => $this->duplicateVehicleRegistrationConflict->name, 'congregation' => $this->duplicateVehicleRegistrationConflict->congregation ?: '—']) }}</p>
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('register.elderly_infirm') }} <span class="text-red-500">*</span></label>
                    <div class="flex gap-4">
                        <button type="button" wire:click="$set('elderlyInfirmParking', '1')"
                            class="flex-1 flex items-center justify-center p-3 rounded-xl border-2 cursor-pointer transition font-medium
                            {{ ($elderlyInfirmParking === true || $elderlyInfirmParking === '1') ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300' : 'border-zinc-200 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 text-zinc-700 dark:text-zinc-300' }}">
                            {{ __('register.yes') }}
                        </button>
                        <button type="button" wire:click="$set('elderlyInfirmParking', '0')"
                            class="flex-1 flex items-center justify-center p-3 rounded-xl border-2 cursor-pointer transition font-medium
                            {{ ($elderlyInfirmParking === false || $elderlyInfirmParking === '0') ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300' : 'border-zinc-200 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 text-zinc-700 dark:text-zinc-300' }}">
                            {{ __('register.no') }}
                        </button>
                    </div>
                    @error('elderlyInfirmParking') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                @endif

                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-3">{{ __('register.attending_days') }}</label>
                    <label class="flex items-center p-3 mb-3 border border-indigo-200 dark:border-indigo-700 rounded-xl hover:bg-indigo-50/50 dark:hover:bg-indigo-900/10 cursor-pointer transition">
                        <input type="checkbox" wire:click.prevent="toggleAllDays"
                            {{ count($days) === 3 ? 'checked' : '' }}
                            class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500 border-zinc-300">
                        <span class="ml-3 text-zinc-900 dark:text-white font-medium">{{ __('register.select_all_days') }}</span>
                    </label>
                    <div class="space-y-3">
                        @foreach(['Friday', 'Saturday', 'Sunday'] as $day)
                            <label
                                class="flex items-center p-3 border border-zinc-200 dark:border-zinc-700 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-700/50 cursor-pointer transition">
                                <input type="checkbox" wire:model="days" value="{{ $day }}"
                                    class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500 border-zinc-300">
                                <span class="ml-3 text-zinc-900 dark:text-white font-medium">{{ __('register.' . strtolower($day)) }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('days') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="pt-4">
                    <button type="submit"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 px-6 rounded-xl transition transform active:scale-[0.98] shadow-lg shadow-indigo-500/20">
                        {{ __('register.submit') }}
                    </button>
                    <div wire:loading wire:target="register" class="text-center mt-2 text-zinc-400 text-sm">
                        {{ __('register.processing') }}
                    </div>
                </div>
                @endif
            </form>
        @endif

        <div class="mt-8 text-center">
            <p class="text-xs text-zinc-400">
                &copy; {{ date('Y') }} {{ __('register.footer') }}
            </p>
        </div>
    </div>
</div>
