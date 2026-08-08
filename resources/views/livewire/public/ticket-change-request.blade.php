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

                    <div class="mt-4 rounded-2xl border-2 border-indigo-300 bg-indigo-50 p-4 shadow-sm dark:border-indigo-700 dark:bg-indigo-950/50">
                        <p class="text-sm font-bold text-indigo-950 dark:text-indigo-100">
                            {{ __('ticket_change_request.radisson_prompt') }}
                        </p>
                        <button type="button"
                            wire:click="useRadissonHotelGuest"
                            class="mt-3 flex w-full min-h-14 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-3.5 text-base font-bold text-white shadow-md shadow-indigo-600/25 transition hover:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:bg-indigo-500 dark:hover:bg-indigo-400 dark:shadow-none">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5 shrink-0" aria-hidden="true">
                                <path fill-rule="evenodd" d="M4.5 2.25a.75.75 0 0 0-.75.75v18c0 .414.336.75.75.75h15a.75.75 0 0 0 .75-.75V3a.75.75 0 0 0-.75-.75h-15ZM6 5.25A.75.75 0 0 1 6.75 4.5h2.5a.75.75 0 0 1 0 1.5h-2.5A.75.75 0 0 1 6 5.25Zm.75 2.25a.75.75 0 0 0 0 1.5h2.5a.75.75 0 0 0 0-1.5h-2.5ZM6 11.25a.75.75 0 0 1 .75-.75h2.5a.75.75 0 0 1 0 1.5h-2.5a.75.75 0 0 1-.75-.75Zm.75 2.25a.75.75 0 0 0 0 1.5h2.5a.75.75 0 0 0 0-1.5h-2.5ZM12.75 5.25a.75.75 0 0 1 .75-.75h2.5a.75.75 0 0 1 0 1.5h-2.5a.75.75 0 0 1-.75-.75Zm.75 2.25a.75.75 0 0 0 0 1.5h2.5a.75.75 0 0 0 0-1.5h-2.5Zm-.75 3a.75.75 0 0 1 .75-.75h2.5a.75.75 0 0 1 0 1.5h-2.5a.75.75 0 0 1-.75-.75Zm.75 2.25a.75.75 0 0 0 0 1.5h2.5a.75.75 0 0 0 0-1.5h-2.5ZM8.25 18a.75.75 0 0 1 .75-.75h6a.75.75 0 0 1 0 1.5H9a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ __('ticket_change_request.radisson_shortcut') }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5 shrink-0" aria-hidden="true">
                                <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <p class="mt-2 text-xs text-indigo-800/80 dark:text-indigo-200/80">{{ __('ticket_change_request.radisson_shortcut_help', ['code' => \App\Models\HotelGuestParkingRequest::PUBLIC_CODE]) }}</p>
                    </div>
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
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('ticket_change_request.registration_search') }}</label>
                            <input type="text" wire:model.live.debounce.300ms="registrationSearch"
                                class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4"
                                autocomplete="off"
                                @disabled($this->resolvedCongregation === null)
                                placeholder="{{ __('ticket_change_request.registration_search_placeholder') }}">
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('ticket_change_request.registration_search_help') }}</p>
                            @if ($this->resolvedCongregation === null)
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('ticket_change_request.enter_code_first') }}</p>
                            @endif
                            @error('parkingRegistrationId')
                                <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        @if ($parkingRegistrationId !== '' && $this->selectedRegistration)
                            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 dark:border-emerald-800 dark:bg-emerald-950/30">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">{{ __('ticket_change_request.registration_selected') }}</p>
                                        <p class="mt-1 text-sm font-medium text-emerald-900 dark:text-emerald-100">{{ $this->selectedRegistration['label'] }}</p>
                                    </div>
                                    <button type="button" wire:click="clearRegistrationSelection"
                                        class="text-sm font-semibold text-emerald-800 underline hover:no-underline dark:text-emerald-200">
                                        {{ __('ticket_change_request.registration_change') }}
                                    </button>
                                </div>
                            </div>
                        @elseif ($this->resolvedCongregation && mb_strlen(trim($registrationSearch)) >= 2)
                            @if (count($this->registrationSearchResults) === 0)
                                <p class="text-sm text-amber-600 dark:text-amber-400">{{ __('ticket_change_request.registration_search_empty') }}</p>
                            @else
                                <ul class="divide-y divide-zinc-200 overflow-hidden rounded-xl border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-600">
                                    @foreach ($this->registrationSearchResults as $result)
                                        <li>
                                            <button type="button" wire:click="selectRegistration({{ $result['id'] }})"
                                                class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left text-sm hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $result['label'] }}</span>
                                                <span class="shrink-0 text-xs font-semibold text-indigo-600 dark:text-indigo-400">{{ __('ticket_change_request.registration_select') }}</span>
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        @elseif ($this->resolvedCongregation && trim($registrationSearch) !== '')
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('ticket_change_request.registration_search_min') }}</p>
                        @endif
                    </div>

                    @if ($parkingRegistrationId !== '' && ! $ownershipVerified)
                        <div>
                            @php($selected = $this->selectedRegistration)
                            @if ($selected && ($selected['vehicle_type'] ?? 'car') === 'coach')
                                <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('ticket_change_request.confirm_ticket') }}</label>
                                <input type="text" wire:model.live.debounce.300ms="confirmOwnership"
                                    class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4"
                                    autocomplete="off" placeholder="{{ __('ticket_change_request.confirm_ticket_placeholder') }}">
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('ticket_change_request.confirm_ticket_help') }}</p>
                            @else
                                <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('ticket_change_request.confirm_vrn') }}</label>
                                <input type="text" wire:model.live.debounce.300ms="confirmOwnership"
                                    class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4"
                                    autocomplete="off" placeholder="{{ __('ticket_change_request.confirm_vrn_placeholder') }}">
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('ticket_change_request.confirm_vrn_help') }}</p>
                            @endif
                            @error('confirmOwnership')
                                <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>
                    @elseif ($ownershipVerified)
                        <p class="text-sm font-medium text-emerald-700 dark:text-emerald-400">{{ __('ticket_change_request.ownership_verified') }}</p>
                    @endif
                @endif

                @if ($requestType === 'field_update' && $parkingRegistrationId !== '' && $ownershipVerified)
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
