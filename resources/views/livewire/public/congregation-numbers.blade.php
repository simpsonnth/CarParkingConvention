<div class="min-h-screen flex flex-col items-center justify-center p-4 sm:p-6 bg-zinc-50 dark:bg-zinc-900">
    <div
        class="w-full max-w-lg bg-white dark:bg-zinc-800 rounded-3xl shadow-xl p-4 sm:p-8 border border-zinc-100 dark:border-zinc-700">

        <div class="flex flex-wrap justify-end gap-2 mb-6">
            <a href="{{ route('locale.set', 'en') }}" class="text-sm font-medium px-3 py-1.5 rounded-lg {{ app()->getLocale() === 'en' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">English</a>
            <a href="{{ route('locale.set', 'pt') }}" class="text-sm font-medium px-3 py-1.5 rounded-lg {{ app()->getLocale() === 'pt' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">Português</a>
            <a href="{{ route('locale.set', 'es') }}" class="text-sm font-medium px-3 py-1.5 rounded-lg {{ app()->getLocale() === 'es' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">Español</a>
        </div>

        <div class="text-center mb-8">
            <h1 class="text-3xl font-black text-zinc-900 dark:text-white tracking-tight mb-2">{{ __('congregation_numbers.title') }}</h1>
            <p class="text-zinc-500 dark:text-zinc-400">{{ __('congregation_numbers.subtitle') }}</p>
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
                <h3 class="text-xl font-bold text-green-800 dark:text-green-200 mb-2">{{ __('congregation_numbers.complete_title') }}</h3>
                <p class="text-green-700 dark:text-green-300 mb-6">{{ __('congregation_numbers.thank_you') }}</p>
                <button type="button" wire:click="submitAnother"
                    class="text-sm font-semibold text-green-800 dark:text-green-200 hover:underline">
                    {{ __('congregation_numbers.submit_another') }}
                </button>
            </div>
        @else
            <form wire:submit="submit" class="space-y-6">
                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('congregation_numbers.congregation_code') }}</label>
                    @if($this->resolvedCongregation)
                        <p class="text-sm font-medium text-green-600 dark:text-green-400 mb-3">
                            {{ __('congregation_numbers.congregation_label') }}: <strong>{{ $this->resolvedCongregation->name }}</strong>
                        </p>
                        <button type="button" wire:click="clearCongregationSelection"
                            class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">
                            {{ __('congregation_numbers.congregation_change') }}
                        </button>
                    @else
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3">{{ __('congregation_numbers.congregation_code_placeholder') }}</p>
                        <label class="mb-1 block text-xs font-semibold text-zinc-600 dark:text-zinc-400">{{ __('congregation_numbers.congregation_pick_search_label') }}</label>
                        <input type="search" wire:model.live.debounce.300ms="congregationSearch" autocomplete="off"
                            class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 transition py-3 px-4"
                            placeholder="{{ __('congregation_numbers.shared_with_search_placeholder') }}">
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('congregation_numbers.shared_with_search_min') }}</p>

                        <div class="mt-3 max-h-48 overflow-y-auto rounded-xl border border-zinc-200 dark:border-zinc-600 divide-y divide-zinc-100 dark:divide-zinc-700">
                            @if($this->congregationPickReady && $this->congregationPickMatches->isEmpty())
                                <p class="p-4 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ __('congregation_numbers.shared_with_no_matches') }}</p>
                            @elseif($this->congregationPickReady)
                                @foreach($this->congregationPickMatches as $c)
                                    <div class="flex items-center justify-between gap-3 px-3 py-2.5 hover:bg-zinc-50 dark:hover:bg-zinc-700/40">
                                        <span class="min-w-0 flex-1 text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $c->name }}</span>
                                        <button type="button" wire:click="selectCongregationById({{ $c->id }})"
                                            class="shrink-0 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">
                                            {{ __('congregation_numbers.congregation_pick_select') }}
                                        </button>
                                    </div>
                                @endforeach
                            @else
                                <p class="p-4 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ __('congregation_numbers.shared_with_type_to_search') }}</p>
                            @endif
                        </div>
                    @endif
                    @error('congregationCode')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('congregation_numbers.car_park_tickets') }}</label>
                    <input type="number" wire:model="carParkTicketsCount" min="0" step="1"
                        class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 transition py-3 px-4"
                        placeholder="{{ __('congregation_numbers.car_park_tickets_placeholder') }}">
                    @error('carParkTicketsCount')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('congregation_numbers.organizes_coach') }}</label>
                    <div class="flex gap-4">
                        <button type="button" wire:click="$set('organizesCoach', '1')"
                            class="flex-1 flex items-center justify-center p-3 rounded-xl border-2 cursor-pointer transition font-medium
                            {{ $organizesCoach === '1' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300' : 'border-zinc-200 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 text-zinc-700 dark:text-zinc-300' }}">
                            {{ __('congregation_numbers.yes') }}
                        </button>
                        <button type="button" wire:click="$set('organizesCoach', '0'); $set('sharingCoachWithOthers', '0'); $set('sharedWithCongregationIds', []); $set('shareSearch', ''); $set('coachSize', '')"
                            class="flex-1 flex items-center justify-center p-3 rounded-xl border-2 cursor-pointer transition font-medium
                            {{ $organizesCoach === '0' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300' : 'border-zinc-200 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 text-zinc-700 dark:text-zinc-300' }}">
                            {{ __('congregation_numbers.no') }}
                        </button>
                    </div>
                </div>

                @if($organizesCoach === '1')
                    <div>
                        <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('congregation_numbers.sharing_coach') }}</label>
                        <div class="flex gap-4">
                            <button type="button" wire:click="$set('sharingCoachWithOthers', '1')"
                                class="flex-1 flex items-center justify-center p-3 rounded-xl border-2 cursor-pointer transition font-medium
                                {{ $sharingCoachWithOthers === '1' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300' : 'border-zinc-200 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 text-zinc-700 dark:text-zinc-300' }}">
                                {{ __('congregation_numbers.yes') }}
                            </button>
                            <button type="button" wire:click="$set('sharingCoachWithOthers', '0'); $set('sharedWithCongregationIds', []); $set('shareSearch', ''); $set('coachSize', '')"
                                class="flex-1 flex items-center justify-center p-3 rounded-xl border-2 cursor-pointer transition font-medium
                                {{ $sharingCoachWithOthers === '0' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300' : 'border-zinc-200 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 text-zinc-700 dark:text-zinc-300' }}">
                                {{ __('congregation_numbers.no') }}
                            </button>
                        </div>
                    </div>

                    @if($sharingCoachWithOthers === '1')
                        <div>
                            <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('congregation_numbers.shared_with_congregations') }} <span class="text-red-500">*</span></label>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3">{{ __('congregation_numbers.shared_with_hint') }}</p>

                            @if($this->selectedSharedCongregations->isNotEmpty())
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('congregation_numbers.shared_with_selected_label') }}</p>
                                <div class="mb-4 flex flex-wrap gap-2">
                                    @foreach($this->selectedSharedCongregations as $c)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-indigo-100 py-1 ps-3 pe-1 text-sm font-medium text-indigo-900 dark:bg-indigo-900/40 dark:text-indigo-100">
                                            <span class="max-w-[220px] truncate">{{ $c->name }}</span>
                                            <button type="button" wire:click="removeSharedCongregation({{ $c->id }})"
                                                class="rounded-full p-1 text-indigo-700 hover:bg-indigo-200/80 dark:text-indigo-200 dark:hover:bg-indigo-800/80"
                                                aria-label="{{ __('congregation_numbers.shared_with_remove') }}">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            <label class="mb-1 block text-xs font-semibold text-zinc-600 dark:text-zinc-400">{{ __('congregation_numbers.shared_with_search_label') }}</label>
                            <input type="search" wire:model.live.debounce.300ms="shareSearch" autocomplete="off"
                                class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 transition py-3 px-4"
                                placeholder="{{ __('congregation_numbers.shared_with_search_placeholder') }}">
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('congregation_numbers.shared_with_search_min') }}</p>

                            <div class="mt-3 max-h-48 overflow-y-auto rounded-xl border border-zinc-200 dark:border-zinc-600 divide-y divide-zinc-100 dark:divide-zinc-700">
                                @if($this->shareSearchReady && $this->shareSearchMatches->isEmpty())
                                    <p class="p-4 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ __('congregation_numbers.shared_with_no_matches') }}</p>
                                @elseif($this->shareSearchReady)
                                    @foreach($this->shareSearchMatches as $c)
                                        <div class="flex items-center justify-between gap-3 px-3 py-2.5 hover:bg-zinc-50 dark:hover:bg-zinc-700/40">
                                            <span class="min-w-0 flex-1 text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $c->name }}</span>
                                            <button type="button" wire:click="addSharedCongregation({{ $c->id }})"
                                                class="shrink-0 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">
                                                {{ __('congregation_numbers.shared_with_add') }}
                                            </button>
                                        </div>
                                    @endforeach
                                @else
                                    <p class="p-4 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ __('congregation_numbers.shared_with_type_to_search') }}</p>
                                @endif
                            </div>

                            @error('sharedWithCongregationIds')
                                <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                            @enderror
                            @error('sharedWithCongregationIds.*')
                                <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('congregation_numbers.coach_size') }} <span class="text-red-500">*</span></label>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">{{ __('congregation_numbers.coach_size_note') }}</p>
                            <select wire:model="coachSize"
                                class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 transition py-3 px-4">
                                <option value="">{{ __('congregation_numbers.choose_option') }}</option>
                                <option value="minibus">{{ __('congregation_numbers.coach_size_minibus') }}</option>
                                <option value="small_coach">{{ __('congregation_numbers.coach_size_small_coach') }}</option>
                                <option value="large_coach">{{ __('congregation_numbers.coach_size_large_coach') }}</option>
                            </select>
                            @error('coachSize')
                                <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif
                @endif

                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('congregation_numbers.disabled_parking') }}</label>
                    <div class="flex gap-4">
                        <button type="button" wire:click="$set('disabledParkingRequired', '1')"
                            class="flex-1 flex items-center justify-center p-3 rounded-xl border-2 cursor-pointer transition font-medium
                            {{ $disabledParkingRequired === '1' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300' : 'border-zinc-200 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 text-zinc-700 dark:text-zinc-300' }}">
                            {{ __('congregation_numbers.yes') }}
                        </button>
                        <button type="button" wire:click="$set('disabledParkingRequired', '0'); $set('disabledParkingCount', '')"
                            class="flex-1 flex items-center justify-center p-3 rounded-xl border-2 cursor-pointer transition font-medium
                            {{ $disabledParkingRequired === '0' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300' : 'border-zinc-200 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 text-zinc-700 dark:text-zinc-300' }}">
                            {{ __('congregation_numbers.no') }}
                        </button>
                    </div>
                </div>

                @if($disabledParkingRequired === '1')
                    <div>
                        <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('congregation_numbers.disabled_parking_count') }} <span class="text-red-500">*</span></label>
                        <input type="number" wire:model="disabledParkingCount" min="1" step="1"
                            class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 transition py-3 px-4"
                            placeholder="{{ __('congregation_numbers.disabled_parking_count_placeholder') }}">
                        @error('disabledParkingCount')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                @endif

                <div class="pt-4">
                    <button type="submit"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 px-6 rounded-xl transition transform active:scale-[0.98] shadow-lg shadow-indigo-500/20">
                        {{ __('congregation_numbers.submit') }}
                    </button>
                    <div wire:loading class="text-center mt-2 text-zinc-400 text-sm">
                        {{ __('congregation_numbers.processing') }}
                    </div>
                </div>
            </form>
        @endif

        <div class="mt-8 text-center">
            <p class="text-xs text-zinc-400">
                &copy; {{ date('Y') }} {{ __('congregation_numbers.footer') }}
            </p>
        </div>
    </div>
</div>
