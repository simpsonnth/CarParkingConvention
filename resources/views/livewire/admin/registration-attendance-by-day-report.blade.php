@php
    $m = $attendanceByDay;
@endphp

<div class="mx-auto max-w-6xl space-y-6 pb-10">
    <header class="flex flex-col gap-4 border-b border-zinc-200 pb-6 dark:border-zinc-600 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0 space-y-2">
            <flux:heading size="xl">{{ __('registrations.attendance_by_day_title') }}</flux:heading>
            <flux:subheading>{{ __('registrations.attendance_by_day_subtitle') }}</flux:subheading>
            <p class="max-w-3xl text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">{{ __('registrations.attendance_by_day_source_note') }}</p>
            <p>
                <flux:link :href="route('admin.circuit-overseer-parking')" wire:navigate class="text-xs font-semibold">{{ __('registrations.attendance_by_day_link_co_admin_list') }}</flux:link>
            </p>
        </div>
        <flux:button :href="route('admin.registrations')" variant="outline" icon="clipboard-document-list" wire:navigate size="sm">
            {{ __('registrations.attendance_by_day_back') }}
        </flux:button>
    </header>

    <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-4 text-sm text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900/50 dark:text-zinc-300">
        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('registrations.attendance_by_day_total_label') }}</p>
        <p class="mt-1 tabular-nums text-lg font-semibold text-zinc-900 dark:text-white">{{ number_format($m['total_registrations']) }}</p>

        <p class="mt-6 font-medium text-zinc-900 dark:text-zinc-100">{{ __('registrations.attendance_by_day_breakdown_intro') }}</p>
        <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">{{ __('registrations.attendance_by_day_breakdown_hint') }}</p>
        <p class="mt-2 text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ __('registrations.attendance_by_day_sum_rule') }}</p>

        <div class="mt-4 overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-600 dark:bg-zinc-800">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-900/80">
                    <tr class="border-b border-zinc-200 dark:border-zinc-600">
                        <th rowspan="2" scope="col" class="align-bottom px-4 py-3 font-semibold text-zinc-900 dark:text-zinc-100">{{ __('registrations.attendance_by_day_col_day') }}</th>
                        <th colspan="3" scope="colgroup" class="border-s border-zinc-200 px-4 py-2 text-center text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:border-zinc-600 dark:text-zinc-400">{{ __('registrations.attendance_by_day_col_group_vehicles') }}</th>
                        <th colspan="2" scope="colgroup" class="border-s border-zinc-200 px-4 py-2 text-center text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:border-zinc-600 dark:text-zinc-400">{{ __('registrations.attendance_by_day_col_group_overlap') }}</th>
                    </tr>
                    <tr class="border-b border-zinc-200 dark:border-zinc-600">
                        <th scope="col" class="border-s border-zinc-200 px-4 py-3 text-end font-semibold text-zinc-900 dark:border-zinc-600 dark:text-zinc-100">{{ __('registrations.attendance_by_day_col_total_vehicles') }}</th>
                        <th scope="col" class="px-4 py-3 text-end font-semibold text-zinc-900 dark:text-zinc-100">{{ __('registrations.attendance_by_day_col_cars') }}</th>
                        <th scope="col" class="px-4 py-3 text-end font-semibold text-zinc-900 dark:text-zinc-100">{{ __('registrations.attendance_by_day_col_coaches') }}</th>
                        <th scope="col" class="border-s border-zinc-200 px-4 py-3 text-end font-semibold text-zinc-900 dark:border-zinc-600 dark:text-zinc-100">{{ __('registrations.attendance_by_day_col_circuit_overseers') }}</th>
                        <th scope="col" class="px-4 py-3 text-end font-semibold text-zinc-900 dark:text-zinc-100">{{ __('registrations.attendance_by_day_col_disabled') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-600">
                    @foreach ($m['ordered_days'] as $day)
                        <tr class="bg-white dark:bg-zinc-800">
                            <th scope="row" class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ __('registrations.attendance_day_'.$day) }}</th>
                            <td class="border-s border-zinc-100 px-4 py-3 text-end tabular-nums text-zinc-800 dark:border-zinc-700 dark:text-zinc-200">{{ number_format($m['counts_by_day'][$day] ?? 0) }}</td>
                            <td class="px-4 py-3 text-end tabular-nums text-zinc-800 dark:text-zinc-200">{{ number_format($m['cars_by_day'][$day] ?? 0) }}</td>
                            <td class="px-4 py-3 text-end tabular-nums text-zinc-800 dark:text-zinc-200">{{ number_format($m['coaches_by_day'][$day] ?? 0) }}</td>
                            <td class="border-s border-zinc-100 px-4 py-3 text-end tabular-nums text-zinc-800 dark:border-zinc-700 dark:text-zinc-200">{{ number_format($m['circuit_overseers_by_day'][$day] ?? 0) }}</td>
                            <td class="px-4 py-3 text-end tabular-nums text-zinc-800 dark:text-zinc-200">{{ number_format($m['disabled_by_day'][$day] ?? 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($m['missing_days_count'] > 0)
            <p class="mt-4 text-amber-800 dark:text-amber-300">
                {{ __('registrations.attendance_by_day_missing_days', ['count' => number_format($m['missing_days_count'])]) }}
            </p>
        @endif
        <p class="mt-4 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">{{ __('registrations.attendance_by_day_overlap_note') }}</p>
    </div>
</div>
