<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('toolbox_talks.admin_title') }}</flux:heading>
            <flux:subheading>{{ __('toolbox_talks.admin_subtitle') }}</flux:subheading>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <flux:input type="date" wire:model.live="talkDate" class="w-auto" />
            @if($presentUrl)
                <a href="{{ $presentUrl }}" target="_blank" rel="noopener"
                    class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">
                    <flux:icon name="presentation-chart-bar" class="size-4" />
                    {{ __('toolbox_talks.open_present') }}
                </a>
            @endif
            @if($downloadPptxUrl)
                <a href="{{ $downloadPptxUrl }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-teal-200 bg-teal-50 px-3 py-2 text-sm font-medium text-teal-900 shadow-sm hover:bg-teal-100 dark:border-teal-800 dark:bg-teal-950/40 dark:text-teal-100 dark:hover:bg-teal-900/40">
                    <flux:icon name="arrow-down-tray" class="size-4" />
                    {{ __('toolbox_talks.download_pptx') }}
                </a>
            @endif
            @if($downloadPdfUrl)
                <a href="{{ $downloadPdfUrl }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-900 shadow-sm hover:bg-rose-100 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-100 dark:hover:bg-rose-900/40">
                    <flux:icon name="document-arrow-down" class="size-4" />
                    {{ __('toolbox_talks.download_pdf') }}
                </a>
            @endif
        </div>
    </div>

    <div class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-900 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-100">
        {{ __('toolbox_talks.consistency_banner') }}
    </div>

    @if($flashMessage !== '')
        <div
            class="flex items-start justify-between gap-3 rounded-xl border border-green-300 bg-green-50 px-4 py-3 text-sm font-semibold text-green-900 dark:border-green-700 dark:bg-green-950/40 dark:text-green-100"
            role="status"
        >
            <span>{{ $flashMessage }}</span>
            <button type="button" wire:click="dismissFlash" class="text-green-700 hover:text-green-900 dark:text-green-300 dark:hover:text-green-100" aria-label="Dismiss">
                ×
            </button>
        </div>
    @endif

    <div class="flex flex-wrap gap-2 border-b border-zinc-200 pb-3 dark:border-zinc-700">
        <button
            type="button"
            wire:click="selectTab('core')"
            @class([
                'rounded-lg px-4 py-2 text-sm font-semibold transition',
                'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => $activeTab === 'core',
                'bg-zinc-100 text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700' => $activeTab !== 'core',
            ])
        >
            {{ __('toolbox_talks.tab_core') }}
        </button>
        @foreach($parks as $park)
            <button
                type="button"
                wire:click="selectTab('{{ $park->id }}')"
                @class([
                    'rounded-lg px-4 py-2 text-sm font-semibold transition',
                    'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => $activeTab === (string) $park->id,
                    'bg-zinc-100 text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700' => $activeTab !== (string) $park->id,
                ])
            >
                {{ $park->name }}
            </button>
        @endforeach
    </div>

    @if($activeTab === 'core')
        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('toolbox_talks.core_help') }}</p>
    @else
        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('toolbox_talks.park_help') }}</p>
    @endif

    <div class="flex flex-wrap gap-2">
        <flux:button type="button" wire:click="addSlide" icon="plus">{{ __('toolbox_talks.add_slide') }}</flux:button>
        <flux:button type="button" wire:click="copyFromYesterday" variant="ghost" icon="document-duplicate">
            {{ __('toolbox_talks.copy_yesterday') }}
        </flux:button>
        <flux:button type="button" wire:click="toggleYesterday" variant="ghost" icon="eye">
            {{ $showYesterday ? __('toolbox_talks.hide_yesterday') : __('toolbox_talks.show_yesterday') }}
        </flux:button>
        @if($activeTab === 'core')
            <flux:button type="button" wire:click="loadStandardReminders" variant="ghost" icon="clipboard-document-list">
                {{ __('toolbox_talks.load_reminders') }}
            </flux:button>
        @endif
        <flux:button type="button" wire:click="save" variant="primary">{{ __('toolbox_talks.save') }}</flux:button>
    </div>

    @if($confirmOverwriteCopy)
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm dark:border-amber-700 dark:bg-amber-950/40">
            <p class="font-semibold text-amber-900 dark:text-amber-100">{{ __('toolbox_talks.confirm_overwrite_copy') }}</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <flux:button type="button" wire:click="copyFromYesterday" variant="danger" size="sm">{{ __('toolbox_talks.confirm_yes') }}</flux:button>
                <flux:button type="button" wire:click="cancelOverwritePrompts" variant="ghost" size="sm">{{ __('toolbox_talks.confirm_no') }}</flux:button>
            </div>
        </div>
    @endif

    @if($confirmOverwriteReminders)
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm dark:border-amber-700 dark:bg-amber-950/40">
            <p class="font-semibold text-amber-900 dark:text-amber-100">{{ __('toolbox_talks.confirm_overwrite_reminders') }}</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <flux:button type="button" wire:click="loadStandardReminders" variant="danger" size="sm">{{ __('toolbox_talks.confirm_yes') }}</flux:button>
                <flux:button type="button" wire:click="cancelOverwritePrompts" variant="ghost" size="sm">{{ __('toolbox_talks.confirm_no') }}</flux:button>
            </div>
        </div>
    @endif

    <div @class(['grid gap-6', 'lg:grid-cols-[minmax(0,1fr)_20rem]' => $showYesterday])>
        <div class="space-y-4">
            @forelse($slides as $index => $slide)
                <div wire:key="slide-{{ $index }}" class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <div class="text-xs font-bold uppercase tracking-widest text-zinc-400">
                            {{ __('toolbox_talks.slide_n', ['n' => $index + 1]) }}
                        </div>
                        <div class="flex flex-wrap gap-1">
                            <flux:button type="button" size="sm" variant="ghost" wire:click="moveSlideUp({{ $index }})" icon="arrow-up" />
                            <flux:button type="button" size="sm" variant="ghost" wire:click="moveSlideDown({{ $index }})" icon="arrow-down" />
                            <flux:button type="button" size="sm" variant="danger" wire:click="removeSlide({{ $index }})" icon="trash" />
                        </div>
                    </div>
                    <div class="space-y-3">
                        <flux:input
                            wire:model="slides.{{ $index }}.title"
                            :label="__('toolbox_talks.slide_title')"
                            maxlength="255"
                        />
                        @error('slides.'.$index.'.title')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                        @enderror
                        <flux:textarea
                            wire:model="slides.{{ $index }}.body"
                            :label="__('toolbox_talks.slide_body')"
                            rows="5"
                        />
                        @error('slides.'.$index.'.body')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500 dark:border-zinc-600">
                    {{ __('toolbox_talks.empty_slides') }}
                </div>
            @endforelse
        </div>

        @if($showYesterday)
            <aside class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                <h3 class="text-sm font-bold uppercase tracking-wider text-zinc-500">
                    {{ __('toolbox_talks.yesterday_heading', ['date' => \Illuminate\Support\Carbon::parse($talkDate)->subDay()->toDateString()]) }}
                </h3>
                <div class="mt-3 space-y-3">
                    @forelse($yesterdaySlides as $ySlide)
                        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
                            <div class="font-semibold text-zinc-900 dark:text-white">{{ $ySlide['title'] }}</div>
                            @if($ySlide['body'] !== '')
                                <p class="mt-1 whitespace-pre-line text-xs text-zinc-600 dark:text-zinc-400">{{ \Illuminate\Support\Str::limit($ySlide['body'], 180) }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">{{ __('toolbox_talks.yesterday_empty') }}</p>
                    @endforelse
                </div>
            </aside>
        @endif
    </div>
</div>
