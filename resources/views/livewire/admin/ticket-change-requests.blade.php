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

    <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900/50">
        {{ __('management.ticket_change_requests.total', ['count' => $total]) }}
    </div>

    <flux:separator />

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                <tr>
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
                        <td class="px-4 py-3 whitespace-nowrap text-zinc-600 dark:text-zinc-300">
                            {{ $row->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                        </td>
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">{{ $row->name }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $row->congregation }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300 max-w-xs truncate">{{ $row->notes }}</td>
                        <td class="px-4 py-3 text-end">
                            <flux:button variant="ghost" size="sm" wire:click="openDetail({{ $row->id }})">
                                {{ __('management.ticket_change_requests.view') }}
                            </flux:button>
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

            <div class="flex justify-end">
                <flux:button variant="ghost" wire:click="closeDetail">{{ __('management.ticket_change_requests.close') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
