<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('congregation_numbers.admin_title') }}</flux:heading>
            <flux:subheading>{{ __('congregation_numbers.admin_subtitle') }}</flux:subheading>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.congregation-numbers.export') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700"
                download>
                <flux:icon name="arrow-down-tray" class="size-4" />
                {{ __('congregation_numbers.export_all_button') }}
            </a>
            <a href="{{ route('admin.congregation-numbers.export-missing') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700"
                download>
                <flux:icon name="arrow-down-tray" class="size-4" />
                {{ __('congregation_numbers.export_missing_button') }}
            </a>
            <a href="{{ route('admin.congregation-numbers.trash') }}" wire:navigate
                class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">
                <flux:icon name="trash" class="size-4" />
                {{ __('congregation_numbers.trash_link') }}
            </a>
            <select wire:model.live="perPage"
                class="block w-full sm:w-auto min-w-0 rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                <option value="25">25 {{ __('congregation_numbers.per_page') }}</option>
                <option value="50">50 {{ __('congregation_numbers.per_page') }}</option>
                <option value="100">100 {{ __('congregation_numbers.per_page') }}</option>
            </select>
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('congregation_numbers.search') }}" class="w-full min-w-0 sm:min-w-[200px]" />
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900/50 dark:text-zinc-300">
        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('congregation_numbers.missing_stats_title') }}</p>
        <p class="mt-1">
            {{ __('congregation_numbers.missing_stats_body', [
                'submitted' => $congregationsSubmitted,
                'total' => $congregationsTotal,
                'missing' => $congregationsMissing,
            ]) }}
        </p>
        @if($congregationsMissing > 0)
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('congregation_numbers.export_missing_hint') }}</p>
        @endif
    </div>

    <flux:separator />

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700 -mx-4 sm:mx-0">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                <tr>
                    <th class="px-4 py-3">
                        <button type="button" wire:click="setSort('congregation')" class="inline-flex items-center gap-1 font-medium hover:text-zinc-700 dark:hover:text-zinc-300">
                            {{ __('congregation_numbers.col_congregation') }}
                            @if($sortBy === 'congregation')
                                <flux:icon name="{{ $sortDir === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                            @endif
                        </button>
                    </th>
                    <th class="px-4 py-3">
                        <button type="button" wire:click="setSort('car_park_tickets_count')" class="inline-flex items-center gap-1 font-medium hover:text-zinc-700 dark:hover:text-zinc-300">
                            {{ __('congregation_numbers.col_tickets') }}
                            @if($sortBy === 'car_park_tickets_count')
                                <flux:icon name="{{ $sortDir === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                            @endif
                        </button>
                    </th>
                    <th class="px-4 py-3">{{ __('congregation_numbers.col_coach') }}</th>
                    <th class="px-4 py-3">{{ __('congregation_numbers.col_sharing') }}</th>
                    <th class="px-4 py-3">{{ __('congregation_numbers.col_shared_with') }}</th>
                    <th class="px-4 py-3">{{ __('congregation_numbers.col_coach_size') }}</th>
                    <th class="px-4 py-3">{{ __('congregation_numbers.col_disabled') }}</th>
                    <th class="px-4 py-3">{{ __('congregation_numbers.col_disabled_count') }}</th>
                    <th class="px-4 py-3">
                        <button type="button" wire:click="setSort('updated_at')" class="inline-flex items-center gap-1 font-medium hover:text-zinc-700 dark:hover:text-zinc-300">
                            {{ __('congregation_numbers.col_updated') }}
                            @if($sortBy === 'updated_at')
                                <flux:icon name="{{ $sortDir === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                            @endif
                        </button>
                    </th>
                    <th class="px-4 py-3">{{ __('congregation_numbers.col_locale') }}</th>
                    <th class="px-4 py-3 text-end">{{ __('congregation_numbers.col_actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($rows as $row)
                    <tr class="bg-white dark:bg-zinc-800/30 hover:bg-zinc-50 dark:hover:bg-zinc-800/80">
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $row->congregation?->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $row->car_park_tickets_count }}</td>
                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                            {{ $row->organizes_coach ? __('congregation_numbers.yes') : __('congregation_numbers.no') }}
                        </td>
                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                            @if(!$row->organizes_coach)
                                —
                            @elseif($row->sharing_coach_with_others === null)
                                —
                            @else
                                {{ $row->sharing_coach_with_others ? __('congregation_numbers.yes') : __('congregation_numbers.no') }}
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                            @php
                                $sharedNames = collect($row->normalizedSharedCongregationIds())
                                    ->map(fn (int $id) => $sharedCongregationNameById[$id] ?? null)
                                    ->filter();
                            @endphp
                            {{ $sharedNames->isNotEmpty() ? $sharedNames->implode(', ') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                            @if($row->coach_size === 'minibus')
                                {{ __('congregation_numbers.coach_size_minibus') }}
                            @elseif($row->coach_size === 'small_coach')
                                {{ __('congregation_numbers.coach_size_small_coach') }}
                            @elseif($row->coach_size === 'large_coach')
                                {{ __('congregation_numbers.coach_size_large_coach') }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                            {{ $row->disabled_parking_required ? __('congregation_numbers.yes') : __('congregation_numbers.no') }}
                        </td>
                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                            {{ $row->disabled_parking_count ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 whitespace-nowrap">
                            {{ $row->updated_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 uppercase">{{ $row->submitted_locale ?? '—' }}</td>
                        <td class="px-4 py-3 text-end">
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item wire:click="softDeleteResponse({{ $row->id }})" wire:confirm="{{ __('congregation_numbers.delete_confirm') }}" icon="trash" variant="danger">{{ __('congregation_numbers.delete_action') }}</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="px-4 py-12 text-center text-zinc-500 dark:text-zinc-400">
                            {{ __('congregation_numbers.empty') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="px-1">
        {{ $rows->links() }}
    </div>
</div>
