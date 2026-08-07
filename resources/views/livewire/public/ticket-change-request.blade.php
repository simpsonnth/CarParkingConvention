<div class="min-h-screen flex flex-col items-center justify-center p-4 sm:p-6 bg-zinc-50 dark:bg-zinc-900">
    <div class="w-full max-w-xl bg-white dark:bg-zinc-800 rounded-3xl shadow-xl p-4 sm:p-8 border border-zinc-100 dark:border-zinc-700">
        <div class="flex flex-wrap justify-end gap-2 mb-6">
            <a href="{{ route('locale.set', 'en') }}" class="text-sm font-medium px-3 py-1.5 rounded-lg {{ app()->getLocale() === 'en' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">English</a>
            <a href="{{ route('locale.set', 'pt') }}" class="text-sm font-medium px-3 py-1.5 rounded-lg {{ app()->getLocale() === 'pt' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">Português</a>
            <a href="{{ route('locale.set', 'es') }}" class="text-sm font-medium px-3 py-1.5 rounded-lg {{ app()->getLocale() === 'es' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">Español</a>
        </div>

        <div class="text-center mb-8">
            <h1 class="text-3xl font-black text-zinc-900 dark:text-white tracking-tight mb-2">{{ __('ticket_change_request.title') }}</h1>
            <p class="text-zinc-500 dark:text-zinc-400">{{ __('ticket_change_request.subtitle') }}</p>
        </div>

        @if ($submitted)
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-2xl p-6 text-center">
                <h3 class="text-xl font-bold text-green-800 dark:text-green-200 mb-2">
                    {{ $submittedAutoApplied ? __('ticket_change_request.complete_auto_title') : __('ticket_change_request.complete_title') }}
                </h3>
                <p class="text-green-700 dark:text-green-300 mb-6">
                    {{ $submittedAutoApplied ? __('ticket_change_request.complete_auto_body') : __('ticket_change_request.complete_pending_body') }}
                </p>
                <button type="button" wire:click="submitAnother" class="text-sm font-semibold text-green-800 dark:text-green-200 hover:underline">
                    {{ __('ticket_change_request.submit_another') }}
                </button>
            </div>
        @else
            <form wire:submit="submit" class="space-y-5">
                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('ticket_change_request.congregation_code') }}</label>
                    <input type="text" wire:model.live.debounce.400ms="congregationCode"
                        class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4 font-mono text-sm"
                        autocomplete="off" placeholder="{{ __('ticket_change_request.congregation_code_placeholder') }}">
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('ticket_change_request.congregation_code_help') }}</p>
                    @if ($this->resolvedCongregation)
                        <p class="mt-2 text-sm font-medium text-emerald-700 dark:text-emerald-400">
                            {{ __('ticket_change_request.congregation_resolved', ['name' => $this->resolvedCongregation->name]) }}
                        </p>
                    @elseif (trim($congregationCode) !== '')
                        <p class="mt-2 text-sm text-amber-600 dark:text-amber-400">{{ __('ticket_change_request.validation.invalid_congregation_code') }}</p>
                    @endif
                    @error('congregationCode')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('ticket_change_request.request_type') }}</label>
                    <div class="space-y-2">
                        @foreach ([
                            'field_update' => __('ticket_change_request.type_field_update'),
                            'car_park_change' => __('ticket_change_request.type_car_park_change'),
                            'cancellation' => __('ticket_change_request.type_cancellation'),
                            'addition' => __('ticket_change_request.type_addition'),
                            'email_request' => __('ticket_change_request.type_email_request'),
                        ] as $value => $label)
                            <label class="flex items-start gap-3 rounded-xl border border-zinc-200 dark:border-zinc-600 px-4 py-3 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-900/40">
                                <input type="radio" wire:model.live="requestType" value="{{ $value }}" class="mt-1 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-zinc-800 dark:text-zinc-200">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('requestType')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                @if ($requestType !== '' && ! in_array($requestType, ['addition', 'email_request'], true))
                    <div>
                        <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('ticket_change_request.registration') }}</label>
                        <select wire:model.live="parkingRegistrationId"
                            class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4"
                            @disabled($this->resolvedCongregation === null)>
                            <option value="">{{ __('ticket_change_request.select_registration') }}</option>
                            @foreach ($this->registrations as $registration)
                                <option value="{{ $registration['id'] }}">{{ $registration['label'] }}</option>
                            @endforeach
                        </select>
                        @if ($this->resolvedCongregation && count($this->registrations) === 0)
                            <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">{{ __('ticket_change_request.no_registrations') }}</p>
                        @elseif ($this->resolvedCongregation === null)
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('ticket_change_request.enter_code_first') }}</p>
                        @endif
                        @error('parkingRegistrationId')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    @if ($parkingRegistrationId !== '')
                        <div>
                            @php($selected = $this->selectedRegistration)
                            @if ($selected && ($selected['vehicle_type'] ?? 'car') === 'coach')
                                <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('ticket_change_request.confirm_ticket') }}</label>
                                <input type="text" wire:model="confirmOwnership"
                                    class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4"
                                    autocomplete="off" placeholder="{{ __('ticket_change_request.confirm_ticket_placeholder') }}">
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('ticket_change_request.confirm_ticket_help') }}</p>
                            @else
                                <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('ticket_change_request.confirm_vrn') }}</label>
                                <input type="text" wire:model="confirmOwnership"
                                    class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4"
                                    autocomplete="off" placeholder="{{ __('ticket_change_request.confirm_vrn_placeholder') }}">
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('ticket_change_request.confirm_vrn_help') }}</p>
                            @endif
                            @error('confirmOwnership')
                                <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif
                @endif

                @if ($requestType === 'field_update' && $parkingRegistrationId !== '')
                    <div class="rounded-2xl border border-zinc-200 dark:border-zinc-600 p-4 space-y-4">
                        <p class="text-sm font-bold text-zinc-800 dark:text-zinc-200">{{ __('ticket_change_request.changes_heading') }}</p>

                        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                            <input type="checkbox" wire:model.live="changeName" class="rounded text-indigo-600 focus:ring-indigo-500">
                            {{ __('ticket_change_request.field_name') }}
                        </label>
                        @if ($changeName)
                            <input type="text" wire:model="newName"
                                class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4">
                            @error('newName') <span class="text-red-500 text-xs block">{{ $message }}</span> @enderror
                        @endif

                        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                            <input type="checkbox" wire:model.live="changeVehicleRegistration" class="rounded text-indigo-600 focus:ring-indigo-500">
                            {{ __('ticket_change_request.field_vehicle_registration') }}
                        </label>
                        @if ($changeVehicleRegistration)
                            <input type="text" wire:model="newVehicleRegistration"
                                class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4">
                        @endif

                        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                            <input type="checkbox" wire:model.live="changeEmail" class="rounded text-indigo-600 focus:ring-indigo-500">
                            {{ __('ticket_change_request.field_email') }}
                        </label>
                        @if ($changeEmail)
                            <input type="email" wire:model="newEmail"
                                class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4">
                        @endif

                        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                            <input type="checkbox" wire:model.live="changeContactNumber" class="rounded text-indigo-600 focus:ring-indigo-500">
                            {{ __('ticket_change_request.field_contact_number') }}
                        </label>
                        @if ($changeContactNumber)
                            <input type="text" wire:model="newContactNumber"
                                class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4">
                        @endif

                        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                            <input type="checkbox" wire:model.live="changeVehicleType" class="rounded text-indigo-600 focus:ring-indigo-500">
                            {{ __('ticket_change_request.field_vehicle_type') }}
                        </label>
                        @if ($changeVehicleType)
                            <select wire:model="newVehicleType"
                                class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4">
                                <option value="car">{{ __('ticket_change_request.vehicle_car') }}</option>
                                <option value="coach">{{ __('ticket_change_request.vehicle_coach') }}</option>
                            </select>
                        @endif

                        @error('changeName')
                            <span class="text-red-500 text-xs block">{{ $message }}</span>
                        @enderror
                    </div>
                @endif

                @if ($requestType === 'addition')
                    <div class="rounded-2xl border border-zinc-200 dark:border-zinc-600 p-4 space-y-4">
                        <p class="text-sm font-bold text-zinc-800 dark:text-zinc-200">{{ __('ticket_change_request.addition_heading') }}</p>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('ticket_change_request.field_name') }}</label>
                            <input type="text" wire:model="additionName"
                                class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4">
                            @error('additionName') <span class="text-red-500 text-xs block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('ticket_change_request.field_contact_number') }}</label>
                            <input type="text" wire:model="additionContactNumber"
                                class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4">
                            @error('additionContactNumber') <span class="text-red-500 text-xs block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('ticket_change_request.field_email') }}</label>
                            <input type="email" wire:model="additionEmail"
                                class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4">
                            @error('additionEmail') <span class="text-red-500 text-xs block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('ticket_change_request.field_vehicle_type') }}</label>
                            <select wire:model.live="additionVehicleType"
                                class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4">
                                <option value="car">{{ __('ticket_change_request.vehicle_car') }}</option>
                                <option value="coach">{{ __('ticket_change_request.vehicle_coach') }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('ticket_change_request.field_vehicle_registration') }}</label>
                            <input type="text" wire:model="additionVehicleRegistration"
                                class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4">
                            @error('additionVehicleRegistration') <span class="text-red-500 text-xs block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <p class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">{{ __('ticket_change_request.field_days') }}</p>
                            <div class="flex flex-wrap gap-3">
                                @foreach (['Friday', 'Saturday', 'Sunday'] as $day)
                                    <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                                        <input type="checkbox" wire:model="additionDays" value="{{ $day }}" class="rounded text-indigo-600 focus:ring-indigo-500">
                                        {{ __('management.convention_day.'.strtolower($day)) }}
                                    </label>
                                @endforeach
                            </div>
                            @error('additionDays') <span class="text-red-500 text-xs block">{{ $message }}</span> @enderror
                        </div>

                        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                            <input type="checkbox" wire:model="additionElderlyInfirm" class="rounded text-indigo-600 focus:ring-indigo-500">
                            {{ __('ticket_change_request.field_elderly_infirm') }}
                        </label>
                    </div>
                @endif

                @if ($requestType !== '')
                    <div>
                        <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">
                            {{ $requestType === 'email_request' ? __('ticket_change_request.email_request_notes') : __('ticket_change_request.notes') }}
                            @if (in_array($requestType, ['car_park_change', 'email_request'], true))
                                <span class="font-normal text-zinc-500">*</span>
                            @else
                                <span class="font-normal text-zinc-500">({{ __('ticket_change_request.optional') }})</span>
                            @endif
                        </label>
                        <textarea wire:model="notes" rows="{{ $requestType === 'email_request' ? 8 : 4 }}"
                            placeholder="{{ match ($requestType) {
                                'car_park_change' => __('ticket_change_request.car_park_notes_placeholder'),
                                'email_request' => __('ticket_change_request.email_request_notes_placeholder'),
                                default => __('ticket_change_request.notes_placeholder'),
                            } }}"
                            class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4"></textarea>
                        @if ($requestType === 'car_park_change')
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('ticket_change_request.car_park_notes_help') }}</p>
                        @elseif ($requestType === 'email_request')
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('ticket_change_request.email_request_notes_help') }}</p>
                        @endif
                        @error('notes')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">
                            {{ $requestType === 'email_request'
                                ? __('ticket_change_request.from_email')
                                : __('ticket_change_request.notification_email') }}
                        </label>
                        <input type="email" wire:model="notificationEmail"
                            class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4"
                            autocomplete="email">
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $requestType === 'email_request'
                                ? __('ticket_change_request.from_email_help')
                                : __('ticket_change_request.notification_email_help') }}
                        </p>
                        @error('notificationEmail')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    @if ($requestType !== 'email_request')
                        <div>
                            <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('ticket_change_request.notification_email_confirmation') }}</label>
                            <input type="email" wire:model="notificationEmailConfirmation"
                                class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4"
                                autocomplete="email">
                            @error('notificationEmailConfirmation')
                                <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif
                @endif

                <button type="submit"
                    class="w-full py-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-lg shadow-lg shadow-indigo-200 dark:shadow-none transition disabled:opacity-60"
                    @disabled($requestType === '')
                    wire:loading.attr="disabled"
                    wire:target="submit">
                    <span wire:loading.remove wire:target="submit">{{ __('ticket_change_request.submit') }}</span>
                    <span wire:loading wire:target="submit">{{ __('ticket_change_request.submitting') }}</span>
                </button>
            </form>
        @endif
    </div>
</div>
