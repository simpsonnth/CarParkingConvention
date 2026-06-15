<div class="min-h-screen flex flex-col items-center justify-center p-4 sm:p-6 bg-zinc-50 dark:bg-zinc-900">
    <div class="w-full max-w-lg bg-white dark:bg-zinc-800 rounded-3xl shadow-xl p-4 sm:p-8 border border-zinc-100 dark:border-zinc-700">
        <div class="flex flex-wrap justify-end gap-2 mb-6">
            <a href="{{ route('locale.set', 'en') }}" class="text-sm font-medium px-3 py-1.5 rounded-lg {{ app()->getLocale() === 'en' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">English</a>
            <a href="{{ route('locale.set', 'pt') }}" class="text-sm font-medium px-3 py-1.5 rounded-lg {{ app()->getLocale() === 'pt' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">Português</a>
            <a href="{{ route('locale.set', 'es') }}" class="text-sm font-medium px-3 py-1.5 rounded-lg {{ app()->getLocale() === 'es' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">Español</a>
        </div>

        <div class="text-center mb-8">
            <h1 class="text-3xl font-black text-zinc-900 dark:text-white tracking-tight mb-2">{{ __('parking_incidents.title') }}</h1>
            <p class="text-zinc-500 dark:text-zinc-400">{{ __('parking_incidents.subtitle') }}</p>
        </div>

        @if($submitted)
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-2xl p-6 text-center">
                <h3 class="text-xl font-bold text-green-800 dark:text-green-200 mb-2">{{ __('parking_incidents.complete_title') }}</h3>
                <p class="text-green-700 dark:text-green-300 mb-6">{{ __('parking_incidents.complete_body') }}</p>
                <button type="button" wire:click="submitAnother" class="text-sm font-semibold text-green-800 dark:text-green-200 hover:underline">
                    {{ __('parking_incidents.submit_another') }}
                </button>
            </div>
        @else
            <form wire:submit="submit" class="space-y-5">
                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('parking_incidents.type') }}</label>
                    <div class="flex gap-3">
                        <button type="button" wire:click="$set('type', 'near_miss')"
                            class="flex-1 p-3 rounded-xl border-2 font-semibold text-sm transition
                            {{ $type === 'near_miss' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300' : 'border-zinc-200 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300' }}">
                            {{ __('parking_incidents.type_near_miss') }}
                        </button>
                        <button type="button" wire:click="$set('type', 'accident')"
                            class="flex-1 p-3 rounded-xl border-2 font-semibold text-sm transition
                            {{ $type === 'accident' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300' : 'border-zinc-200 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300' }}">
                            {{ __('parking_incidents.type_accident') }}
                        </button>
                    </div>
                    @error('type') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('parking_incidents.occurred_at') }}</label>
                    <input type="datetime-local" wire:model="occurredAt"
                        class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4">
                    @error('occurredAt') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('parking_incidents.location') }}</label>
                    <input type="text" wire:model="location" placeholder="{{ __('parking_incidents.location_placeholder') }}"
                        class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4">
                    @error('location') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('parking_incidents.car_park') }}</label>
                    <select wire:model="carParkId"
                        class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4">
                        <option value="">{{ __('parking_incidents.car_park_none') }}</option>
                        @foreach($this->carParks as $park)
                            <option value="{{ $park->id }}">{{ $park->name }}</option>
                        @endforeach
                    </select>
                    @error('carParkId') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('parking_incidents.description') }}</label>
                    <textarea wire:model="description" rows="4" placeholder="{{ __('parking_incidents.description_placeholder') }}"
                        class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4"></textarea>
                    @error('description') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('parking_incidents.actions_taken') }}</label>
                    <textarea wire:model="actionsTaken" rows="2" placeholder="{{ __('parking_incidents.actions_taken_placeholder') }}"
                        class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4"></textarea>
                    @error('actionsTaken') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('parking_incidents.injury_reported') }}</label>
                    <div class="flex gap-3">
                        <button type="button" wire:click="$set('injuryReported', '0')"
                            class="flex-1 p-3 rounded-xl border-2 font-semibold text-sm transition
                            {{ $injuryReported === '0' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300' : 'border-zinc-200 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300' }}">
                            {{ __('parking_incidents.no') }}
                        </button>
                        <button type="button" wire:click="$set('injuryReported', '1')"
                            class="flex-1 p-3 rounded-xl border-2 font-semibold text-sm transition
                            {{ $injuryReported === '1' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300' : 'border-zinc-200 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300' }}">
                            {{ __('parking_incidents.yes') }}
                        </button>
                    </div>
                </div>

                @if($injuryReported === '1' || $type === 'accident')
                    <div>
                        <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('parking_incidents.severity') }}</label>
                        <select wire:model="severity"
                            class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4">
                            <option value="">{{ __('parking_incidents.severity') }}</option>
                            <option value="low">{{ __('parking_incidents.severity_low') }}</option>
                            <option value="medium">{{ __('parking_incidents.severity_medium') }}</option>
                            <option value="high">{{ __('parking_incidents.severity_high') }}</option>
                        </select>
                        @error('severity') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                @endif

                <div class="pt-2 border-t border-zinc-200 dark:border-zinc-700 space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('parking_incidents.reporter_name') }}</label>
                        <input type="text" wire:model="reporterName"
                            class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4">
                        @error('reporterName') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('parking_incidents.reporter_email') }}</label>
                        <input type="email" wire:model="reporterEmail"
                            class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4">
                        @error('reporterEmail') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('parking_incidents.reporter_phone') }}</label>
                        <input type="tel" wire:model="reporterPhone"
                            class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4">
                        @error('reporterPhone') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <button type="submit"
                    class="w-full py-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-lg shadow-lg shadow-indigo-200 dark:shadow-none transition">
                    {{ __('parking_incidents.submit') }}
                </button>
            </form>
        @endif
    </div>
</div>
