@php
    /** @var array $svr SurveyVsRegistrationMetrics::compute() */
    $co = $svr['circuit_overseer'];
@endphp

<div class="space-y-4">
    <div>
        <flux:heading size="lg">{{ __('dashboard_survey_registration.section_title') }}</flux:heading>
        <flux:subheading>{{ __('dashboard_survey_registration.section_subtitle') }}</flux:subheading>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900/50 dark:text-zinc-300">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('dashboard_survey_registration.stat_survey_total') }}</p>
                <p class="mt-1 text-xl font-bold tabular-nums text-zinc-900 dark:text-white">{{ number_format($svr['total_survey_tickets_congregations']) }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('dashboard_survey_registration.stat_registrations_matched') }}</p>
                <p class="mt-1 text-xl font-bold tabular-nums text-zinc-900 dark:text-white">{{ number_format($svr['congregation_registration_subtotal']) }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('dashboard_survey_registration.stat_co_expected') }}</p>
                <p class="mt-1 text-xl font-bold tabular-nums text-zinc-900 dark:text-white">{{ number_format($co['expected_tickets']) }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('dashboard_survey_registration.stat_co_registered') }}</p>
                <p class="mt-1 text-xl font-bold tabular-nums text-zinc-900 dark:text-white">{{ number_format($co['registration_count']) }}</p>
            </div>
        </div>
        <p class="mt-3 text-xs text-zinc-600 dark:text-zinc-400">{{ __('dashboard_survey_registration.co_comparison', ['registered' => number_format($co['registration_count']), 'expected' => number_format($co['expected_tickets'])]) }}</p>
        <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-500">{{ __('dashboard_survey_registration.difference_hint') }}</p>
    </div>

    @if (($svr['unmatched_registrations'] ?? 0) > 0)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-100">
            <p>{{ __('dashboard_survey_registration.unmatched_alert', ['count' => number_format($svr['unmatched_registrations'])]) }}</p>
            <p class="mt-2">
                <a href="{{ route('admin.registrations') }}" wire:navigate class="font-semibold text-amber-900 underline underline-offset-2 hover:text-amber-800 dark:text-amber-200 dark:hover:text-amber-50">{{ __('dashboard_survey_registration.link_registrations') }}</a>
            </p>
        </div>
    @endif

    <div class="max-h-[min(28rem,70vh)] overflow-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 -mx-4 sm:mx-0">
        <table class="w-full min-w-[640px] text-left text-sm">
            <thead class="sticky top-0 z-10 bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                <tr>
                    <th class="px-4 py-3">{{ __('dashboard_survey_registration.table_col_congregation') }}</th>
                    <th class="px-4 py-3 text-end">{{ __('dashboard_survey_registration.table_col_survey') }}</th>
                    <th class="px-4 py-3 text-end">{{ __('dashboard_survey_registration.table_col_registered') }}</th>
                    <th class="px-4 py-3 text-end">{{ __('dashboard_survey_registration.table_col_difference') }}</th>
                    <th class="min-w-[140px] px-4 py-3">{{ __('dashboard_survey_registration.table_col_progress') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @foreach ($svr['rows'] as $row)
                    @php
                        $congUrl = $row['uuid'] ? route('admin.congregations.show', $row['uuid']) : null;
                    @endphp
                    <tr class="bg-white hover:bg-zinc-50 dark:bg-zinc-800/30 dark:hover:bg-zinc-800/80">
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">
                            @if ($congUrl)
                                <a href="{{ $congUrl }}" wire:navigate class="font-semibold text-indigo-700 underline-offset-2 hover:text-indigo-900 hover:underline dark:text-indigo-300 dark:hover:text-indigo-200">{{ $row['name'] }}</a>
                            @else
                                {{ $row['name'] }}
                            @endif
                        </td>
                        <td class="px-4 py-3 text-end tabular-nums text-zinc-700 dark:text-zinc-300">{{ number_format($row['survey_tickets']) }}</td>
                        <td class="px-4 py-3 text-end tabular-nums text-zinc-700 dark:text-zinc-300">{{ number_format($row['registration_count']) }}</td>
                        <td class="px-4 py-3 text-end tabular-nums font-medium {{ $row['difference'] > 0 ? 'text-amber-700 dark:text-amber-300' : ($row['difference'] < 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-zinc-600 dark:text-zinc-400') }}">
                            {{ number_format($row['difference']) }}
                        </td>
                        <td class="px-4 py-3">
                            @if ($row['survey_tickets'] > 0)
                                <div class="flex flex-col gap-1">
                                    <div class="flex justify-between text-[10px] font-medium text-zinc-500 dark:text-zinc-400">
                                        <span>{{ __('dashboard_survey_registration.progress_label') }}</span>
                                        <span class="tabular-nums">{{ number_format($row['progress_percent'], 1) }}%</span>
                                    </div>
                                    <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                        <div
                                            class="h-full rounded-full bg-indigo-600 dark:bg-indigo-500"
                                            style="width: {{ min(100, $row['progress_percent']) }}%"
                                            role="progressbar"
                                            aria-valuenow="{{ (int) round($row['progress_percent']) }}"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                        ></div>
                                    </div>
                                </div>
                            @elseif ($row['registration_count'] > 0)
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ number_format($row['progress_percent'], 0) }}%</span>
                            @else
                                <span class="text-xs text-zinc-400">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
