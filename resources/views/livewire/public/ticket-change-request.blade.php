<div class="min-h-screen flex flex-col items-center justify-center p-4 sm:p-6 bg-zinc-50 dark:bg-zinc-900">
    <div class="w-full max-w-lg bg-white dark:bg-zinc-800 rounded-3xl shadow-xl p-4 sm:p-8 border border-zinc-100 dark:border-zinc-700">
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
                <h3 class="text-xl font-bold text-green-800 dark:text-green-200 mb-2">{{ __('ticket_change_request.complete_title') }}</h3>
                <p class="text-green-700 dark:text-green-300 mb-6">{{ __('ticket_change_request.complete_body') }}</p>
                <button type="button" wire:click="submitAnother" class="text-sm font-semibold text-green-800 dark:text-green-200 hover:underline">
                    {{ __('ticket_change_request.submit_another') }}
                </button>
            </div>
        @else
            <form wire:submit="submit" class="space-y-5">
                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('ticket_change_request.name') }}</label>
                    <input type="text" wire:model="name"
                        class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4"
                        autocomplete="name">
                    @error('name')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('ticket_change_request.congregation') }}</label>
                    <select wire:model="congregation"
                        class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4">
                        <option value="">{{ __('ticket_change_request.select_congregation') }}</option>
                        @foreach ($this->congregations as $congName)
                            <option value="{{ $congName }}">{{ $congName }}</option>
                        @endforeach
                    </select>
                    @error('congregation')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">{{ __('ticket_change_request.notes') }}</label>
                    <textarea wire:model="notes" rows="5" placeholder="{{ __('ticket_change_request.notes_placeholder') }}"
                        class="w-full rounded-xl border-zinc-200 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500 py-3 px-4"></textarea>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('ticket_change_request.notes_help') }}</p>
                    @error('notes')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit"
                    class="w-full py-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-lg shadow-lg shadow-indigo-200 dark:shadow-none transition">
                    {{ __('ticket_change_request.submit') }}
                </button>
            </form>
        @endif
    </div>
</div>
