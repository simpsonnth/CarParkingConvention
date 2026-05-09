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
                                    <flux:menu.item wire:click="openEdit({{ $row->id }})" icon="pencil">{{ __('congregation_numbers.edit_action') }}</flux:menu.item>
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

    <flux:modal wire:model="editModalOpen" class="w-[calc(100vw-2rem)] max-w-lg max-h-[90vh] overflow-y-auto">
        <form wire:submit="saveEdit" class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('congregation_numbers.edit_modal_title') }}</flux:heading>
                <flux:subheading>{{ __('congregation_numbers.edit_modal_subtitle', ['name' => $this->editingCongregation?->name ?? '—']) }}</flux:subheading>
            </div>

            <flux:input
                wire:model="editCarParkTicketsCount"
                type="number"
                min="0"
                step="1"
                label="{{ __('congregation_numbers.car_park_tickets') }}"
            />

            <div>
                <span class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('congregation_numbers.organizes_coach') }}</span>
                <div class="flex gap-2">
                    <button type="button" wire:click="$set('editOrganizesCoach', '1')"
                        @class([
                            'flex-1 rounded-lg border-2 px-3 py-2 text-sm font-medium transition',
                            'border-indigo-500 bg-indigo-50 text-indigo-800 dark:border-indigo-500 dark:bg-indigo-900/30 dark:text-indigo-200' => $editOrganizesCoach === '1',
                            'border-zinc-200 text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-700/50' => $editOrganizesCoach !== '1',
                        ])>{{ __('congregation_numbers.yes') }}</button>
                    <button type="button" wire:click="$set('editOrganizesCoach', '0'); $set('editSharingCoachWithOthers', '0'); $set('editSharedWithCongregationIds', []); $set('editShareSearch', ''); $set('editCoachSize', '')"
                        @class([
                            'flex-1 rounded-lg border-2 px-3 py-2 text-sm font-medium transition',
                            'border-indigo-500 bg-indigo-50 text-indigo-800 dark:border-indigo-500 dark:bg-indigo-900/30 dark:text-indigo-200' => $editOrganizesCoach === '0',
                            'border-zinc-200 text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-700/50' => $editOrganizesCoach !== '0',
                        ])>{{ __('congregation_numbers.no') }}</button>
                </div>
            </div>

            @if($editOrganizesCoach === '1')
                <div>
                    <span class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('congregation_numbers.sharing_coach') }}</span>
                    <div class="flex gap-2">
                        <button type="button" wire:click="$set('editSharingCoachWithOthers', '1')"
                            @class([
                                'flex-1 rounded-lg border-2 px-3 py-2 text-sm font-medium transition',
                                'border-indigo-500 bg-indigo-50 text-indigo-800 dark:border-indigo-500 dark:bg-indigo-900/30 dark:text-indigo-200' => $editSharingCoachWithOthers === '1',
                                'border-zinc-200 text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-700/50' => $editSharingCoachWithOthers !== '1',
                            ])>{{ __('congregation_numbers.yes') }}</button>
                        <button type="button" wire:click="$set('editSharingCoachWithOthers', '0'); $set('editSharedWithCongregationIds', []); $set('editShareSearch', ''); $set('editCoachSize', '')"
                            @class([
                                'flex-1 rounded-lg border-2 px-3 py-2 text-sm font-medium transition',
                                'border-indigo-500 bg-indigo-50 text-indigo-800 dark:border-indigo-500 dark:bg-indigo-900/30 dark:text-indigo-200' => $editSharingCoachWithOthers === '0',
                                'border-zinc-200 text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-700/50' => $editSharingCoachWithOthers !== '0',
                            ])>{{ __('congregation_numbers.no') }}</button>
                    </div>
                </div>

                @if($editSharingCoachWithOthers === '1')
                    <div class="space-y-2 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-600 dark:bg-zinc-900/40">
                        <p class="text-xs font-medium text-zinc-600 dark:text-zinc-400">{{ __('congregation_numbers.shared_with_congregations') }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-500">{{ __('congregation_numbers.shared_with_hint') }}</p>

                        @if($this->editSelectedSharedCongregations->isNotEmpty())
                            <div class="flex flex-wrap gap-2">
                                @foreach($this->editSelectedSharedCongregations as $c)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-indigo-100 py-1 ps-3 pe-1 text-xs font-medium text-indigo-900 dark:bg-indigo-900/40 dark:text-indigo-100">
                                        <span class="max-w-[200px] truncate">{{ $c->name }}</span>
                                        <button type="button" wire:click="removeEditSharedCongregation({{ $c->id }})" class="rounded-full p-1 hover:bg-indigo-200/80 dark:hover:bg-indigo-800/80" aria-label="{{ __('congregation_numbers.shared_with_remove') }}">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <flux:input wire:model.live.debounce.300ms="editShareSearch" label="{{ __('congregation_numbers.shared_with_search_label') }}" placeholder="{{ __('congregation_numbers.shared_with_search_placeholder') }}" />

                        <div class="max-h-40 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-600 divide-y divide-zinc-100 dark:divide-zinc-700">
                            @if($this->editShareSearchReady && $this->editShareSearchMatches->isEmpty())
                                <p class="p-3 text-center text-xs text-zinc-500">{{ __('congregation_numbers.shared_with_no_matches') }}</p>
                            @elseif($this->editShareSearchReady)
                                @foreach($this->editShareSearchMatches as $c)
                                    <div class="flex items-center justify-between gap-2 px-3 py-2">
                                        <span class="min-w-0 flex-1 truncate text-sm text-zinc-800 dark:text-zinc-200">{{ $c->name }}</span>
                                        <flux:button type="button" size="sm" wire:click="addEditSharedCongregation({{ $c->id }})">{{ __('congregation_numbers.shared_with_add') }}</flux:button>
                                    </div>
                                @endforeach
                            @else
                                <p class="p-3 text-center text-xs text-zinc-500">{{ __('congregation_numbers.shared_with_type_to_search') }}</p>
                            @endif
                        </div>
                        @error('editSharedWithCongregationIds')<p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <flux:select wire:model="editCoachSize" label="{{ __('congregation_numbers.coach_size') }}">
                        <option value="">{{ __('congregation_numbers.choose_option') }}</option>
                        <option value="minibus">{{ __('congregation_numbers.coach_size_minibus') }}</option>
                        <option value="small_coach">{{ __('congregation_numbers.coach_size_small_coach') }}</option>
                        <option value="large_coach">{{ __('congregation_numbers.coach_size_large_coach') }}</option>
                    </flux:select>
                @endif
            @endif

            <div>
                <span class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('congregation_numbers.disabled_parking') }}</span>
                <div class="flex gap-2">
                    <button type="button" wire:click="$set('editDisabledParkingRequired', '1')"
                        @class([
                            'flex-1 rounded-lg border-2 px-3 py-2 text-sm font-medium transition',
                            'border-indigo-500 bg-indigo-50 text-indigo-800 dark:border-indigo-500 dark:bg-indigo-900/30 dark:text-indigo-200' => $editDisabledParkingRequired === '1',
                            'border-zinc-200 text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-700/50' => $editDisabledParkingRequired !== '1',
                        ])>{{ __('congregation_numbers.yes') }}</button>
                    <button type="button" wire:click="$set('editDisabledParkingRequired', '0'); $set('editDisabledParkingCount', '')"
                        @class([
                            'flex-1 rounded-lg border-2 px-3 py-2 text-sm font-medium transition',
                            'border-indigo-500 bg-indigo-50 text-indigo-800 dark:border-indigo-500 dark:bg-indigo-900/30 dark:text-indigo-200' => $editDisabledParkingRequired === '0',
                            'border-zinc-200 text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-700/50' => $editDisabledParkingRequired !== '0',
                        ])>{{ __('congregation_numbers.no') }}</button>
                </div>
            </div>

            @if($editDisabledParkingRequired === '1')
                <flux:input
                    wire:model="editDisabledParkingCount"
                    type="number"
                    min="1"
                    step="1"
                    label="{{ __('congregation_numbers.disabled_parking_count') }}"
                />
            @endif

            <div class="flex justify-end gap-2 pt-2">
                <flux:button type="button" variant="ghost" wire:click="closeEditModal">{{ __('congregation_numbers.edit_cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('congregation_numbers.edit_save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
