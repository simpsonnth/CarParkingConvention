<div class="mx-auto max-w-6xl space-y-6 pb-10">
    <div>
        <a href="{{ route('admin.dashboard') }}" wire:navigate
            class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
            <flux:icon name="arrow-left" class="size-3" />
            {{ __('dashboard_survey_registration.back_command_center') }}
        </a>
    </div>

    @include('livewire.admin.partials.survey-vs-registration-report-body', ['svr' => $svr])
</div>
