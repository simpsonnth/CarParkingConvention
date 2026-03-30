<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('congregation_numbers.trash_title') }}</flux:heading>
            <flux:subheading>{{ __('congregation_numbers.trash_subtitle') }}</flux:subheading>
        </div>
        <a href="{{ route('admin.congregation-numbers') }}" wire:navigate class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">
            <flux:icon name="arrow-left" class="size-4" />
            {{ __('congregation_numbers.trash_back') }}
        </a>
    </div>

    @if(count($selectedIds) > 0)
        <div class="flex flex-wrap items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800/50">
            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300 basis-full sm:basis-auto">{{ count($selectedIds) }} {{ __('congregation_numbers.trash_selected') }}</span>
            <flux:button variant="primary" size="sm" wire:click="restoreSelected">
                {{ __('congregation_numbers.trash_restore_selected') }}
            </flux:button>
            <flux:button variant="danger" size="sm" wire:click="forceDeleteSelected" wire:confirm="{{ __('congregation_numbers.trash_confirm_permanent_selected') }}">
                {{ __('congregation_numbers.trash_permanent_delete_selected') }}
            </flux:button>
            <flux:button variant="ghost" size="sm" wire:click="$set('selectedIds', [])">{{ __('congregation_numbers.trash_cancel') }}</flux:button>
        </div>
    @endif

    <flux:separator />

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700 -mx-4 sm:mx-0">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                <tr>
                    <th class="w-10 px-4 py-3">
                        <input type="checkbox" wire:click="toggleSelectAll"
                            {{ count($rows->items()) > 0 && count(array_intersect($selectedIds, $rows->pluck('id')->all())) === count($rows->items()) ? 'checked' : '' }}
                            class="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                    </th>
                    <th class="px-4 py-3">{{ __('congregation_numbers.trash_deleted_at') }}</th>
                    <th class="px-4 py-3">{{ __('congregation_numbers.col_congregation') }}</th>
                    <th class="px-4 py-3">{{ __('congregation_numbers.col_tickets') }}</th>
                    <th class="px-4 py-3 text-end">{{ __('congregation_numbers.col_actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-800/30">
                @forelse($rows as $row)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/80">
                        <td class="w-10 px-4 py-3">
                            <input type="checkbox" wire:model="selectedIds" value="{{ $row->id }}"
                                class="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-zinc-600 dark:text-zinc-400">
                            {{ $row->deleted_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                        </td>
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $row->congregation?->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $row->car_park_tickets_count }}</td>
                        <td class="px-4 py-3 text-end">
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item wire:click="restore({{ $row->id }})" icon="arrow-path">{{ __('congregation_numbers.trash_restore') }}</flux:menu.item>
                                    <flux:menu.item wire:click="forceDelete({{ $row->id }})" wire:confirm="{{ __('congregation_numbers.trash_confirm_permanent_one') }}" icon="trash" variant="danger">{{ __('congregation_numbers.trash_permanent_delete') }}</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-zinc-500 dark:text-zinc-400">
                            <flux:icon name="trash" class="mx-auto mb-2 size-10 text-zinc-300" />
                            {{ __('congregation_numbers.trash_empty') }}
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
