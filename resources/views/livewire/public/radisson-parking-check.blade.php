<div class="min-h-screen flex flex-col items-center justify-center p-4 sm:p-6 bg-gradient-to-b from-teal-50 via-zinc-50 to-zinc-100 dark:from-zinc-950 dark:via-zinc-900 dark:to-zinc-950">
    <div class="w-full max-w-lg overflow-hidden rounded-3xl border border-teal-100 bg-white shadow-xl dark:border-teal-900 dark:bg-zinc-800">
        <div class="relative h-40 overflow-hidden bg-indigo-950 sm:h-48">
            <img
                src="{{ asset('images/guest-handout-hero.png') }}"
                alt=""
                class="absolute inset-0 h-full w-full object-cover object-center"
            >
            <div class="absolute inset-0 bg-gradient-to-r from-slate-950/75 via-slate-950/45 to-slate-950/20"></div>
            <div class="relative z-10 flex h-full flex-col justify-end p-5 sm:p-6">
                <p class="text-xs font-bold uppercase tracking-widest text-teal-200/90">{{ __('radisson_parking_check.eyebrow') }}</p>
                <h1 class="mt-1 text-2xl font-black leading-tight text-white sm:text-3xl">{{ __('radisson_parking_check.title') }}</h1>
            </div>
        </div>

        <div class="p-4 sm:p-8">
            <div class="mb-6 flex flex-wrap justify-end gap-2">
                <a href="{{ route('locale.set', 'en') }}" class="text-sm font-medium px-3 py-1.5 rounded-lg {{ app()->getLocale() === 'en' ? 'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-200' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">English</a>
                <a href="{{ route('locale.set', 'pt') }}" class="text-sm font-medium px-3 py-1.5 rounded-lg {{ app()->getLocale() === 'pt' ? 'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-200' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">Português</a>
                <a href="{{ route('locale.set', 'es') }}" class="text-sm font-medium px-3 py-1.5 rounded-lg {{ app()->getLocale() === 'es' ? 'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-200' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">Español</a>
            </div>

            <p class="mb-6 text-center text-sm text-zinc-600 dark:text-zinc-400">{{ __('radisson_parking_check.subtitle') }}</p>

            @if($searched)
                @if($found)
                    <div class="rounded-2xl border border-teal-200 bg-teal-50 p-6 text-center dark:border-teal-800 dark:bg-teal-950/30">
                        <p class="text-sm font-bold uppercase tracking-widest text-teal-700 dark:text-teal-300">{{ __('radisson_parking_check.found_label') }}</p>
                        <p class="mt-2 text-base text-teal-900 dark:text-teal-100">{{ __('radisson_parking_check.found_body') }}</p>
                        <div class="mt-5 rounded-2xl bg-white px-4 py-5 shadow-sm dark:bg-zinc-900">
                            <p class="text-xs font-bold uppercase tracking-widest text-zinc-400">{{ __('radisson_parking_check.car_park_label') }}</p>
                            <p class="mt-2 text-3xl font-black text-teal-800 dark:text-teal-200">
                                {{ $carParkName ?: __('radisson_parking_check.car_park_unknown') }}
                            </p>
                        </div>
                        <button type="button" wire:click="checkAnother" class="mt-6 text-sm font-semibold text-teal-800 hover:underline dark:text-teal-200">
                            {{ __('radisson_parking_check.check_another') }}
                        </button>
                    </div>
                @else
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-center dark:border-amber-800 dark:bg-amber-950/30">
                        <p class="text-sm font-bold uppercase tracking-widest text-amber-700 dark:text-amber-300">{{ __('radisson_parking_check.not_found_label') }}</p>
                        <p class="mt-2 text-base text-amber-950 dark:text-amber-100">{{ __('radisson_parking_check.not_found_body') }}</p>
                        <a
                            href="{{ route('management.radisson-guest-parking') }}"
                            class="mt-6 inline-flex w-full items-center justify-center rounded-xl bg-teal-700 px-4 py-3.5 text-base font-bold text-white hover:bg-teal-600"
                        >
                            {{ __('radisson_parking_check.request_parking') }}
                        </a>
                        <button type="button" wire:click="checkAnother" class="mt-4 text-sm font-semibold text-amber-900 hover:underline dark:text-amber-200">
                            {{ __('radisson_parking_check.check_another') }}
                        </button>
                    </div>
                @endif
            @else
                <form wire:submit="check" class="space-y-5">
                    <div>
                        <label class="mb-2 block text-sm font-bold text-zinc-700 dark:text-zinc-300">
                            {{ __('radisson_parking_check.ticket_label') }}
                        </label>
                        <input
                            type="text"
                            autocomplete="off"
                            autocapitalize="characters"
                            wire:model="vehicleRegistration"
                            placeholder="{{ __('radisson_parking_check.ticket_placeholder') }}"
                            class="w-full rounded-xl border-zinc-200 bg-zinc-50 py-3.5 px-4 text-center font-mono text-xl tracking-wider uppercase focus:border-teal-500 focus:ring-teal-500 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white"
                        >
                        @error('vehicleRegistration')
                            <span class="mt-1 block text-xs text-red-500">{{ $message }}</span>
                        @enderror
                        <p class="mt-2 text-center text-xs text-zinc-500 dark:text-zinc-400">{{ __('radisson_parking_check.ticket_help') }}</p>
                    </div>
                    <button
                        type="submit"
                        class="w-full rounded-xl bg-teal-700 py-4 text-lg font-bold text-white shadow-lg shadow-teal-200/60 transition hover:bg-teal-600 dark:shadow-none"
                    >
                        {{ __('radisson_parking_check.submit') }}
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
