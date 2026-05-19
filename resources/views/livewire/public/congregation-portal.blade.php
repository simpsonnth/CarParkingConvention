<div class="min-h-screen flex flex-col items-center justify-center p-4 sm:p-6 bg-zinc-50 dark:bg-zinc-900">
    <div
        class="w-full max-w-3xl bg-white dark:bg-zinc-800 rounded-3xl shadow-xl p-4 sm:p-8 border border-zinc-100 dark:border-zinc-700">

        <div class="text-center mb-8">
            <h1 class="text-3xl font-black text-zinc-900 dark:text-white tracking-tight mb-2">
                {{ __('congregation_portal.title') }}</h1>
            <p class="text-zinc-500 dark:text-zinc-400">{{ __('congregation_portal.subtitle') }}</p>
        </div>

        @if (!$this->isAuthenticated)
            @if (!$this->portalConfigured)
                <div class="rounded-2xl border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20 p-4 text-sm text-amber-900 dark:text-amber-100"
                    role="alert">
                    {{ __('congregation_portal.portal_not_configured') }}
                </div>
            @else
                <form wire:submit="login" class="space-y-6 max-w-md mx-auto">
                    <div>
                        <h2 class="text-lg font-bold text-zinc-900 dark:text-white mb-1">
                            {{ __('congregation_portal.login_title') }}</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">
                            {{ __('congregation_portal.login_subtitle') }}</p>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('congregation_portal.congregation_code') }}</label>
                        <input type="text" wire:model="congregationCode"
                            class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4 font-mono"
                            placeholder="{{ __('congregation_portal.congregation_code_placeholder') }}" autocomplete="off">
                        @error('congregationCode')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label
                            class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('congregation_portal.password') }}</label>
                        <input type="password" wire:model="password"
                            class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4"
                            placeholder="{{ __('congregation_portal.password_placeholder') }}" autocomplete="current-password">
                        @error('password')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <button type="submit"
                        class="w-full py-3 px-4 rounded-xl bg-indigo-600 text-white font-bold hover:bg-indigo-700 transition">
                        {{ __('congregation_portal.sign_in') }}
                    </button>
                </form>
            @endif
        @else
            <div class="flex justify-end mb-4">
                <button type="button" wire:click="logout"
                    class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white">
                    {{ __('congregation_portal.sign_out') }}
                </button>
            </div>

            <p class="text-center text-sm font-medium text-green-600 dark:text-green-400 mb-6">
                {{ $this->congregation->name }}
            </p>

            @php($summary = $this->surveySummary)

            <section class="mb-8 rounded-2xl border border-zinc-200 dark:border-zinc-600 p-4 sm:p-6 space-y-3">
                <h2 class="text-lg font-bold text-zinc-900 dark:text-white">
                    {{ __('congregation_portal.survey_heading') }}</h2>

                @if (!$summary['has_survey'])
                    <p class="text-sm text-amber-700 dark:text-amber-300">
                        {{ __('congregation_portal.survey_not_submitted') }}</p>
                @else
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('congregation_portal.survey_car_tickets') }}
                            </dt>
                            <dd class="font-semibold text-zinc-900 dark:text-white">
                                {{ $summary['filled_cars'] }} {{ __('congregation_portal.of') }}
                                {{ $summary['car_tickets'] }} {{ __('congregation_portal.filled_cars') }}
                            </dd>
                        </div>
                        @if ($summary['disabled_required'])
                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">
                                    {{ __('congregation_portal.survey_disabled_spaces') }}</dt>
                                <dd class="font-semibold text-zinc-900 dark:text-white">
                                    {{ $summary['filled_disabled'] }} {{ __('congregation_portal.of') }}
                                    {{ $summary['disabled_spaces'] }}
                                    {{ __('congregation_portal.filled_disabled') }}
                                </dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('congregation_portal.survey_coach') }}</dt>
                            <dd class="font-semibold text-zinc-900 dark:text-white">
                                {{ $summary['organizes_coach'] ? __('congregation_portal.survey_yes') : __('congregation_portal.survey_no') }}
                                @if ($summary['organizes_coach'])
                                    — {{ $summary['filled_coach'] }} / 1
                                    {{ __('congregation_portal.filled_coach') }}
                                @endif
                            </dd>
                        </div>
                        @if ($summary['coach_size'])
                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">
                                    {{ __('congregation_portal.survey_coach_size') }}</dt>
                                <dd class="font-semibold text-zinc-900 dark:text-white">
                                    {{ __('congregation_numbers.coach_size_' . $summary['coach_size']) }}
                                </dd>
                            </div>
                        @endif
                    </dl>
                @endif
            </section>

            <section>
                <h2 class="text-lg font-bold text-zinc-900 dark:text-white mb-1">
                    {{ __('congregation_portal.registrations_heading') }}</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">
                    {{ __('congregation_portal.registrations_subheading') }}</p>

                @if ($registrations->isEmpty())
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('congregation_portal.no_registrations') }}
                    </p>
                @else
                    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-600">
                        <table class="min-w-full text-sm">
                            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                                <tr>
                                    <th class="px-4 py-3 text-start font-semibold text-zinc-700 dark:text-zinc-300">
                                        {{ __('congregation_portal.col_type') }}</th>
                                    <th class="px-4 py-3 text-start font-semibold text-zinc-700 dark:text-zinc-300">
                                        {{ __('congregation_portal.col_registration') }}</th>
                                    <th class="px-4 py-3 text-start font-semibold text-zinc-700 dark:text-zinc-300">
                                        {{ __('congregation_portal.col_disabled') }}</th>
                                    <th class="px-4 py-3 text-start font-semibold text-zinc-700 dark:text-zinc-300">
                                        {{ __('congregation_portal.col_days') }}</th>
                                    <th class="px-4 py-3 text-end font-semibold text-zinc-700 dark:text-zinc-300">
                                        {{ __('congregation_portal.col_actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-600">
                                @foreach ($registrations as $registration)
                                    <tr wire:key="portal-reg-{{ $registration->id }}">
                                        <td class="px-4 py-3">
                                            {{ $registration->vehicle_type === 'coach' ? __('congregation_portal.type_coach') : __('congregation_portal.type_car') }}
                                        </td>
                                        <td class="px-4 py-3 font-mono">
                                            {{ $registration->vehicle_registration ?: __('congregation_portal.registration_dash') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($registration->vehicle_type === 'car')
                                                {{ $registration->elderly_infirm_parking ? __('congregation_portal.yes') : __('congregation_portal.no') }}
                                            @else
                                                {{ __('congregation_portal.registration_dash') }}
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ implode(', ', $registration->days ?? []) }}
                                        </td>
                                        <td class="px-4 py-3 text-end">
                                            <button type="button" wire:click="openEdit({{ $registration->id }})"
                                                class="text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">
                                                {{ __('congregation_portal.edit') }}
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            @if ($editModalOpen)
                <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" wire:click.self="closeEdit">
                    <div class="w-full max-w-lg bg-white dark:bg-zinc-800 rounded-2xl shadow-xl p-6 space-y-4"
                        role="dialog" aria-modal="true">
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white">
                            {{ __('congregation_portal.edit_registration') }}</h3>

                        <form wire:submit="saveEdit" class="space-y-4">
                            <div>
                                <label
                                    class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('congregation_portal.vehicle_type') }}</label>
                                <div class="flex gap-3">
                                    <button type="button" wire:click="$set('vehicleType', 'car')"
                                        class="flex-1 py-2 rounded-lg border-2 font-semibold text-sm {{ $vehicleType === 'car' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-zinc-200 dark:border-zinc-600' }}">
                                        {{ __('congregation_portal.type_car') }}
                                    </button>
                                    <button type="button" wire:click="$set('vehicleType', 'coach')"
                                        class="flex-1 py-2 rounded-lg border-2 font-semibold text-sm {{ $vehicleType === 'coach' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-zinc-200 dark:border-zinc-600' }}">
                                        {{ __('congregation_portal.type_coach') }}
                                    </button>
                                </div>
                                @error('vehicleType')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>

                            @if ($vehicleType === 'car')
                                <div>
                                    <label
                                        class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('congregation_portal.vehicle_reg') }}</label>
                                    <input type="text" wire:model="vehicleReg"
                                        class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white py-2 px-3 font-mono uppercase">
                                    @error('vehicleReg')
                                        <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <span class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('congregation_portal.elderly_infirm') }}</span>
                                    <div class="flex gap-3">
                                        <button type="button" wire:click="$set('elderlyInfirmParking', '1')"
                                            class="flex-1 py-2 rounded-lg border-2 font-semibold text-sm {{ ($elderlyInfirmParking === true || $elderlyInfirmParking === '1') ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-zinc-200 dark:border-zinc-600' }}">
                                            {{ __('congregation_portal.yes') }}
                                        </button>
                                        <button type="button" wire:click="$set('elderlyInfirmParking', '0')"
                                            class="flex-1 py-2 rounded-lg border-2 font-semibold text-sm {{ ($elderlyInfirmParking === false || $elderlyInfirmParking === '0') ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-zinc-200 dark:border-zinc-600' }}">
                                            {{ __('congregation_portal.no') }}
                                        </button>
                                    </div>
                                    @error('elderlyInfirmParking')
                                        <span class="text-red-500 text-xs block mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            @endif

                            <div>
                                <label
                                    class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('congregation_portal.days') }}</label>
                                <div class="flex flex-wrap gap-2">
                                    @foreach (['Friday', 'Saturday', 'Sunday'] as $day)
                                        <label class="inline-flex items-center gap-1 text-sm">
                                            <input type="checkbox" wire:model="days" value="{{ $day }}"
                                                class="rounded border-zinc-300">
                                            {{ $day }}
                                        </label>
                                    @endforeach
                                </div>
                                @error('days')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="flex gap-3 justify-end pt-2">
                                <button type="button" wire:click="closeEdit"
                                    class="px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 text-sm font-semibold">
                                    {{ __('congregation_portal.cancel') }}
                                </button>
                                <button type="submit"
                                    class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                                    {{ __('congregation_portal.save') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
