<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('management.ticket_change_requests.title') }}</flux:heading>
            <flux:subheading>{{ __('management.ticket_change_requests.subtitle') }}</flux:subheading>
        </div>
        <div class="flex flex-wrap items-center gap-2">
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
        <table class="w-full min-w-[800px] text-left text-sm">
            <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                <tr>
                    <th class="px-4 py-3">{{ __('management.ticket_change_requests.col_status') }}</th>
                    <th class="px-4 py-3">{{ __('management.ticket_change_requests.col_submitted') }}</th>
                    <th class="px-4 py-3">{{ __('management.ticket_change_requests.col_name') }}</th>
                    <th class="px-4 py-3">{{ __('management.ticket_change_requests.col_congregation') }}</th>
                    <th class="px-4 py-3">{{ __('management.ticket_change_requests.col_notes') }}</th>
                    <th class="px-4 py-3 text-end">{{ __('management.ticket_change_requests.col_actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                @forelse ($rows as $row)
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
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">{{ $row->name }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $row->congregation }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300 max-w-xs truncate">{{ $row->notes }}</td>
                        <td class="px-4 py-3 text-end">
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                <flux:button variant="ghost" size="sm" wire:click="openDetail({{ $row->id }})">
                                    {{ __('management.ticket_change_requests.view') }}
                                </flux:button>
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
                        <td colspan="6" class="px-6 py-12 text-center text-zinc-500">
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

    <flux:modal wire:model="detailModalOpen" class="w-[calc(100vw-2rem)] max-w-lg">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('management.ticket_change_requests.detail_title') }}</flux:heading>
                <flux:subheading>
                    @if ($viewing)
                        {{ $viewing->created_at?->timezone(config('app.timezone'))->format('d M Y H:i') }}
                    @endif
                </flux:subheading>
            </div>

            @if ($viewing)
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('management.ticket_change_requests.col_status') }}</dt>
                        <dd class="mt-1">
                            @if ($viewing->isPending())
                                <flux:badge color="amber">{{ __('management.ticket_change_requests.status_pending') }}</flux:badge>
                            @else
                                <flux:badge color="green">{{ __('management.ticket_change_requests.status_completed') }}</flux:badge>
                                @if ($viewing->actioned_at)
                                    <div class="mt-1 text-xs text-zinc-500">
                                        {{ $viewing->actioned_at->timezone(config('app.timezone'))->format('d M Y H:i') }}
                                        @if ($viewing->actionedByUser)
                                            · {{ $viewing->actionedByUser->name }}
                                        @endif
                                    </div>
                                @endif
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('management.ticket_change_requests.col_name') }}</dt>
                        <dd class="mt-1 text-zinc-900 dark:text-white">{{ $viewing->name }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('management.ticket_change_requests.col_congregation') }}</dt>
                        <dd class="mt-1 text-zinc-900 dark:text-white">{{ $viewing->congregation }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('management.ticket_change_requests.col_notes') }}</dt>
                        <dd class="mt-1 whitespace-pre-wrap text-zinc-900 dark:text-white">{{ $viewing->notes }}</dd>
                    </div>
                </dl>
            @endif

            <div class="flex flex-wrap justify-end gap-2">
                <flux:button variant="ghost" wire:click="closeDetail">{{ __('management.ticket_change_requests.close') }}</flux:button>
                @can('ticket-change-requests.manage')
                    @if ($viewing?->isPending())
                        <flux:button variant="primary" wire:click="markCompleted({{ $viewing->id }})"
                            wire:confirm="{{ __('management.ticket_change_requests.confirm_complete') }}">
                            {{ __('management.ticket_change_requests.mark_completed') }}
                        </flux:button>
                    @elseif ($viewing?->isCompleted())
                        <flux:button variant="ghost" wire:click="markPending({{ $viewing->id }})">
                            {{ __('management.ticket_change_requests.mark_pending') }}
                        </flux:button>
                    @endif
                @endcan
            </div>
        </div>
    </flux:modal>
</div>
