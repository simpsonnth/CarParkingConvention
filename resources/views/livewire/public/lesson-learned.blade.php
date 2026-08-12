<div class="min-h-screen flex flex-col items-center justify-center p-4 sm:p-6 bg-zinc-50 dark:bg-zinc-900">
    <div class="w-full max-w-3xl bg-white dark:bg-zinc-800 rounded-3xl shadow-xl p-4 sm:p-8 border border-zinc-100 dark:border-zinc-700">
        <div class="flex flex-wrap justify-end gap-2 mb-6">
            <a href="{{ route('locale.set', 'en') }}" class="text-sm font-medium px-3 py-1.5 rounded-lg {{ app()->getLocale() === 'en' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">English</a>
            <a href="{{ route('locale.set', 'pt') }}" class="text-sm font-medium px-3 py-1.5 rounded-lg {{ app()->getLocale() === 'pt' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">Português</a>
            <a href="{{ route('locale.set', 'es') }}" class="text-sm font-medium px-3 py-1.5 rounded-lg {{ app()->getLocale() === 'es' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}">Español</a>
        </div>

        <div class="text-center mb-8">
            <h1 class="text-3xl font-black text-zinc-900 dark:text-white tracking-tight mb-2">{{ __('lessons_learned.title') }}</h1>
            <p class="text-zinc-500 dark:text-zinc-400">{{ __('lessons_learned.subtitle') }}</p>
        </div>

        @if($submitted)
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-2xl p-6 text-center">
                <h3 class="text-xl font-bold text-green-800 dark:text-green-200 mb-2">{{ __('lessons_learned.complete_title') }}</h3>
                <p class="text-green-700 dark:text-green-300 mb-6">{{ __('lessons_learned.complete_body') }}</p>
                <button type="button" wire:click="submitAnother" class="text-sm font-semibold text-green-800 dark:text-green-200 hover:underline">
                    {{ __('lessons_learned.submit_another') }}
                </button>
            </div>
        @else
            <form wire:submit="submit" class="space-y-5">
                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('lessons_learned.reporter_name') }}</label>
                    <input type="text" wire:model="reporterName"
                        class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4">
                    @error('reporterName') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('lessons_learned.category') }}</label>
                    <select wire:model="category"
                        class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4">
                        <option value="parking">{{ __('lessons_learned.category_parking') }}</option>
                        <option value="registration">{{ __('lessons_learned.category_registration') }}</option>
                        <option value="operations">{{ __('lessons_learned.category_operations') }}</option>
                        <option value="other">{{ __('lessons_learned.category_other') }}</option>
                    </select>
                    @error('category') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('lessons_learned.convention_day') }}</label>
                    <select wire:model="conventionDay"
                        class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4">
                        @foreach(\App\Support\ConventionDay::lessonDayKeys() as $day)
                            <option value="{{ $day }}">{{ \App\Support\ConventionDay::publicLabel($day) }}</option>
                        @endforeach
                    </select>
                    @error('conventionDay') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('lessons_learned.title_field') }}</label>
                    <input type="text" wire:model="title" placeholder="{{ __('lessons_learned.title_placeholder') }}"
                        class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4">
                    @error('title') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('lessons_learned.worked_well') }}</label>
                    <textarea wire:model="workedWell" rows="3" placeholder="{{ __('lessons_learned.worked_well_placeholder') }}"
                        class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4"></textarea>
                    @error('workedWell') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('lessons_learned.didnt_work_well') }}</label>
                    <textarea wire:model="didntWorkWell" rows="3" placeholder="{{ __('lessons_learned.didnt_work_well_placeholder') }}"
                        class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4"></textarea>
                    @error('didntWorkWell') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <x-lesson-learned-uploads
                    :pending-attachments="$attachments"
                    :pending-voice-notes="$voiceNotes"
                />

                <button type="submit"
                    class="w-full py-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-lg shadow-lg shadow-indigo-200 dark:shadow-none transition">
                    {{ __('lessons_learned.submit') }}
                </button>
            </form>
        @endif
    </div>
</div>
