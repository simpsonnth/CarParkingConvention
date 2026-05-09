<div class="flex w-full max-w-none flex-col gap-6 pb-8 min-h-0">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between shrink-0">
        <div class="min-w-0 space-y-2">
            <a href="{{ route('admin.dashboard') }}" wire:navigate
                class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                <flux:icon name="arrow-left" class="size-3" />
                {{ __('dashboard_survey_registration.back_command_center') }}
            </a>
            <flux:heading size="xl">{{ __('dashboard_survey_registration.section_title') }}</flux:heading>
            <flux:subheading>{{ __('dashboard_survey_registration.section_subtitle') }}</flux:subheading>
        </div>
        <div class="w-full sm:w-auto sm:min-w-[280px] shrink-0">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="{{ __('dashboard_survey_registration.search_placeholder') }}"
                class="w-full"
            />
        </div>
    </div>

    @if ($searchActive)
        <p class="text-sm text-zinc-600 dark:text-zinc-400 shrink-0">
            {{ __('dashboard_survey_registration.search_results_hint', ['shown' => number_format(count($tableRows)), 'total' => number_format(count($svr['rows']))]) }}
        </p>
    @endif

    @include('livewire.admin.partials.survey-vs-registration-report-body', [
        'svr' => $svr,
        'tableRows' => $tableRows,
        'noMatches' => $noMatches,
        'hideSectionHeading' => true,
        'tableWrapperClass' => 'max-h-[calc(100dvh-13rem)] min-h-[50vh] overflow-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800',
        'tableSortEnabled' => true,
        'sortBy' => $sortBy,
        'sortDir' => $sortDir,
    ])
</div>
