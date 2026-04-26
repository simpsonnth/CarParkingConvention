<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <flux:heading size="xl">{{ __('reports.co_page_title') }}</flux:heading>
            <flux:subheading>{{ __('reports.co_page_subtitle') }}</flux:subheading>
        </div>
        <flux:button variant="primary" wire:click="create" class="w-full shrink-0 sm:w-auto">{{ __('reports.co_add') }}</flux:button>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('reports.co_search_placeholder')"
        class="w-full min-w-0 sm:max-w-md" />

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700 -mx-4 sm:mx-0">
        <table class="w-full min-w-[520px] text-left text-sm text-zinc-500 dark:text-zinc-400">
            <thead class="bg-zinc-50 text-xs uppercase text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                <tr>
                    <th class="px-4 py-3 sm:px-6">{{ __('reports.co_col_name') }}</th>
                    <th class="px-4 py-3 sm:px-6">{{ __('reports.co_col_tickets') }}</th>
                    <th class="px-4 py-3 sm:px-6">{{ __('reports.co_col_disabled') }}</th>
                    <th class="px-4 py-3 text-end sm:px-6">{{ __('reports.co_col_actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                @forelse ($rows as $row)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                        <td class="px-4 py-4 font-medium text-zinc-900 dark:text-white sm:px-6">{{ $row->first_name }}</td>
                        <td class="px-4 py-4 tabular-nums sm:px-6">{{ $row->car_park_tickets_count }}</td>
                        <td class="px-4 py-4 sm:px-6">
                            @if ($row->disabled_parking_required)
                                <flux:badge color="amber">{{ __('reports.co_yes') }}</flux:badge>
                            @else
                                <flux:badge color="zinc">{{ __('reports.co_no') }}</flux:badge>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-end sm:px-6">
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                                <flux:menu>
                                    <flux:menu.item wire:click="edit({{ $row->id }})" icon="pencil">{{ __('reports.co_menu_edit') }}</flux:menu.item>
                                    <flux:menu.item wire:click="delete({{ $row->id }})" wire:confirm="{{ __('reports.co_delete_confirm') }}" icon="trash" variant="danger">{{ __('reports.co_delete') }}</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-zinc-500">{{ __('reports.co_empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $rows->links() }}
    </div>

    <flux:modal wire:model="modalOpen" class="w-[calc(100vw-2rem)] max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $rowId ? __('reports.co_modal_edit') : __('reports.co_modal_create') }}</flux:heading>
                <flux:subheading>{{ __('reports.co_modal_subtitle') }}</flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:field>
                    <flux:label>{{ __('reports.co_field_first_name') }}</flux:label>
                    <flux:input wire:model="firstName" />
                    <flux:error name="firstName" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('reports.co_field_tickets') }}</flux:label>
                    <flux:input type="number" wire:model="carParkTicketsCount" min="0" />
                    <flux:error name="carParkTicketsCount" />
                </flux:field>

                <flux:field>
                    <flux:checkbox wire:model="disabledParkingRequired" :label="__('reports.co_field_disabled')" />
                    <flux:error name="disabledParkingRequired" />
                </flux:field>
            </div>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('modalOpen', false)">{{ __('reports.co_cancel') }}</flux:button>
                <flux:button variant="primary" wire:click="save">{{ __('reports.co_save') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
