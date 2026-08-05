<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('management.ticket_change_requests.title') }}</flux:heading>
            <flux:subheading>{{ __('management.ticket_change_requests.subtitle') }}</flux:subheading>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @can('ticket-change-requests.manage')
                <flux:button variant="primary" wire:click="openCreate" icon="plus">
                    {{ __('management.ticket_change_requests.add') }}
                </flux:button>
            @endcan
            <select wire:model.live="perPage"
                class="block w-full sm:w-auto rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                <option value="25">25 {{ __('management.ticket_change_requests.per_page') }}</option>
                <option value="50">50 {{ __('management.ticket_change_requests.per_page') }}</option>
                <option value="100">100 {{ __('management.ticket_change_requests.per_page') }}</option>
            </select>
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                placeholder="{{ __('management.ticket_change_requests.search') }}" class="w-full min-w-0 sm:min-w-[220px]" />
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        <flux:button size="sm" variant="{{ $statusFilter === 'pending' ? 'primary' : 'ghost' }}"
            wire:click="setStatusFilter('pending')">
            {{ __('management.ticket_change_requests.filter_pending') }}
            <span class="ms-1 tabular-nums opacity-80">({{ $pendingCount }})</span>
        </flux:button>
        <flux:button size="sm" variant="{{ $statusFilter === 'completed' ? 'primary' : 'ghost' }}"
            wire:click="setStatusFilter('completed')">
            {{ __('management.ticket_change_requests.filter_completed') }}
            <span class="ms-1 tabular-nums opacity-80">({{ $completedCount }})</span>
        </flux:button>
        <flux:button size="sm" variant="{{ $statusFilter === 'all' ? 'primary' : 'ghost' }}"
            wire:click="setStatusFilter('all')">
            {{ __('management.ticket_change_requests.filter_all') }}
            <span class="ms-1 tabular-nums opacity-80">({{ $total }})</span>
        </flux:button>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900/50">
        @if ($statusFilter === 'pending')
            {{ __('management.ticket_change_requests.total_pending', ['count' => $pendingCount]) }}
        @elseif ($statusFilter === 'completed')
            {{ __('management.ticket_change_requests.total_completed', ['count' => $completedCount]) }}
        @else
            {{ __('management.ticket_change_requests.total', ['count' => $total]) }}
        @endif
    </div>

    <flux:separator />

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                <tr>
                    <th class="px-4 py-3">{{ __('management.ticket_change_requests.col_status') }}</th>
                    <th class="px-4 py-3">{{ __('management.ticket_change_requests.col_submitted') }}</th>
                    <th class="px-4 py-3">{{ __('management.ticket_change_requests.col_name') }}</th>
                    <th class="px-4 py-3">{{ __('management.ticket_change_requests.col_notes') }}</th>
                    <th class="px-4 py-3 text-end">{{ __('management.ticket_change_requests.col_actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                @php($previousCongKey = null)
                @forelse ($rows as $row)
                    @php($congKey = mb_strtolower(trim($row->congregation)))
                    @if ($congKey !== $previousCongKey)
                        @php($stats = $congregationStats[$congKey] ?? null)
                        <tr wire:key="tcr-cong-{{ $congKey }}" class="bg-zinc-100 dark:bg-zinc-900/80">
                            <td colspan="5" class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-semibold text-zinc-900 dark:text-white">
                                        {{ $stats['label'] ?? $row->congregation }}
                                    </span>
                                    @if ($stats)
                                        <flux:badge size="sm" color="zinc">
                                            {{ __('management.ticket_change_requests.congregation_group_badge', [
                                                'people' => $stats['pending_people'] > 0 ? $stats['pending_people'] : $stats['people'],
                                                'pending' => $stats['pending'],
                                            ]) }}
                                        </flux:badge>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @php($previousCongKey = $congKey)
                    @endif
                    <tr wire:key="tcr-{{ $row->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if ($row->isPending())
                                <flux:badge color="amber">{{ __('management.ticket_change_requests.status_pending') }}</flux:badge>
                            @else
                                <flux:badge color="green">{{ __('management.ticket_change_requests.status_completed') }}</flux:badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-zinc-600 dark:text-zinc-300">
                            {{ $row->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                        </td>
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">
                            {{ $row->name }}
                            @php($personKey = mb_strtolower(trim($row->name)).'|'.mb_strtolower(trim($row->congregation)))
                            @if (($personPendingCounts[$personKey] ?? 0) > 1)
                                <div class="mt-1">
                                    <flux:badge size="sm" color="sky">
                                        {{ __('management.ticket_change_requests.related_pending_badge', ['count' => $personPendingCounts[$personKey]]) }}
                                    </flux:badge>
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300 max-w-xs truncate">
                            {{ $row->notes }}
                            @if (filled($row->admin_notes))
                                <div class="mt-1 text-xs text-indigo-600 dark:text-indigo-400 truncate">
                                    {{ __('management.ticket_change_requests.admin_notes_preview') }}: {{ $row->admin_notes }}
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-end">
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                <a href="{{ route('admin.ticket-change-requests.show', $row) }}" wire:navigate
                                    class="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-700">
                                    {{ __('management.ticket_change_requests.view') }}
                                </a>
                                @can('ticket-change-requests.manage')
                                    @if ($row->isPending())
                                        <flux:button variant="primary" size="sm" wire:click="markCompleted({{ $row->id }})"
                                            wire:confirm="{{ __('management.ticket_change_requests.confirm_complete') }}">
                                            {{ __('management.ticket_change_requests.mark_completed') }}
                                        </flux:button>
                                    @else
                                        <flux:button variant="ghost" size="sm" wire:click="markPending({{ $row->id }})">
                                            {{ __('management.ticket_change_requests.mark_pending') }}
                                        </flux:button>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-zinc-500">
                            {{ __('management.ticket_change_requests.empty') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $rows->links() }}
    </div>

    <flux:modal wire:model="createModalOpen" class="w-[calc(100vw-2rem)] max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('management.ticket_change_requests.create_title') }}</flux:heading>
                <flux:subheading>{{ __('management.ticket_change_requests.create_subtitle') }}</flux:subheading>
            </div>

            <flux:input wire:model="createName" label="{{ __('management.ticket_change_requests.col_name') }}" />

            <flux:select wire:model="createCongregation" label="{{ __('management.ticket_change_requests.col_congregation') }}"
                placeholder="{{ __('management.ticket_change_requests.select_congregation') }}">
                @foreach ($this->congregations as $congName)
                    <option value="{{ $congName }}">{{ $congName }}</option>
                @endforeach
            </flux:select>

            <div class="space-y-2">
                <label for="createNotes" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('management.ticket_change_requests.col_notes') }}
                </label>
                <textarea wire:model="createNotes" id="createNotes" rows="4"
                    class="block w-full rounded-lg border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                    placeholder="{{ __('management.ticket_change_requests.create_notes_placeholder') }}"></textarea>
                @error('createNotes')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>

            <div class="space-y-2">
                <label for="createAdminNotes" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('management.ticket_change_requests.admin_notes') }}
                    <span class="font-normal text-zinc-500">({{ __('management.ticket_change_requests.admin_notes_optional') }})</span>
                </label>
                <textarea wire:model="createAdminNotes" id="createAdminNotes" rows="2"
                    class="block w-full rounded-lg border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                    placeholder="{{ __('management.ticket_change_requests.admin_notes_placeholder') }}"></textarea>
                @error('createAdminNotes')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="closeCreate">{{ __('management.ticket_change_requests.close') }}</flux:button>
                <flux:button variant="primary" wire:click="saveCreate">
                    {{ __('management.ticket_change_requests.create_button') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
