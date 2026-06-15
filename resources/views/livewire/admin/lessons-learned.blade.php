<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('management.lessons_learned.title') }}</flux:heading>
            <flux:subheading>{{ __('management.lessons_learned.subtitle') }}</flux:subheading>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <flux:button wire:click="openCreate" icon="plus">{{ __('management.lessons_learned.add') }}</flux:button>
            <select wire:model.live="categoryFilter"
                class="block w-full sm:w-auto rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                <option value="">{{ __('management.lessons_learned.filter_all') }}</option>
                @foreach(['parking', 'registration', 'operations', 'other'] as $cat)
                    <option value="{{ $cat }}">{{ __('management.lessons_learned.category_'.$cat) }}</option>
                @endforeach
            </select>
            <select wire:model.live="dayFilter"
                class="block w-full sm:w-auto rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                <option value="">{{ __('management.lessons_learned.filter_day_all') }}</option>
                @foreach(\App\Support\ConventionDay::lessonDayKeys() as $day)
                    <option value="{{ $day }}">{{ \App\Support\ConventionDay::label($day) }}</option>
                @endforeach
            </select>
            <select wire:model.live="perPage"
                class="block w-full sm:w-auto rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                <option value="25">25 {{ __('management.lessons_learned.per_page') }}</option>
                <option value="50">50 {{ __('management.lessons_learned.per_page') }}</option>
                <option value="100">100 {{ __('management.lessons_learned.per_page') }}</option>
            </select>
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                placeholder="{{ __('management.lessons_learned.search') }}" class="w-full min-w-0 sm:min-w-[220px]" />
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900/50">
        {{ __('management.lessons_learned.total', ['count' => $total]) }}
    </div>

    <flux:separator />

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full min-w-[800px] text-left text-sm">
            <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                <tr>
                    <th class="px-4 py-3">{{ __('management.lessons_learned.col_submitted') }}</th>
                    <th class="px-4 py-3">{{ __('management.lessons_learned.col_source') }}</th>
                    <th class="px-4 py-3">{{ __('management.lessons_learned.col_category') }}</th>
                    <th class="px-4 py-3">{{ __('management.lessons_learned.col_day') }}</th>
                    <th class="px-4 py-3">{{ __('management.lessons_learned.col_title') }}</th>
                    <th class="px-4 py-3">{{ __('management.lessons_learned.col_reporter') }}</th>
                    <th class="px-4 py-3">{{ __('management.lessons_learned.col_actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($rows as $row)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="px-4 py-3 whitespace-nowrap">{{ $row->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $row->source === 'admin' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' }}">
                                {{ $row->source === 'admin' ? __('management.lessons_learned.source_admin') : __('management.lessons_learned.source_public') }}
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ __('management.lessons_learned.category_'.$row->category) }}</td>
                        <td class="px-4 py-3">{{ \App\Support\ConventionDay::label($row->convention_day ?? 'all_days') }}</td>
                        <td class="px-4 py-3">{{ $row->title ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $row->reporter_name }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                <flux:button size="sm" variant="ghost" wire:click="openDetail({{ $row->id }})">
                                    {{ __('management.lessons_learned.view') }}
                                </flux:button>
                                <flux:button size="sm" variant="ghost" wire:click="openEdit({{ $row->id }})">
                                    {{ __('management.lessons_learned.edit') }}
                                </flux:button>
                                <flux:button size="sm" variant="danger" wire:click="delete({{ $row->id }})"
                                    wire:confirm="{{ __('management.lessons_learned.delete_confirm') }}">
                                    {{ __('management.lessons_learned.delete') }}
                                </flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-zinc-500">{{ __('management.lessons_learned.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $rows->links() }}</div>

    <flux:modal wire:model="formModalOpen" class="max-w-2xl">
        <flux:heading size="lg">{{ $editingId ? __('management.lessons_learned.modal_edit') : __('management.lessons_learned.modal_create') }}</flux:heading>
        <form wire:submit="save" class="mt-4 space-y-4">
            <flux:input wire:model="formReporterName" label="{{ __('management.lessons_learned.field_reporter') }}" />
            @error('formReporterName') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror

            <div>
                <label class="block text-sm font-medium mb-1">{{ __('management.lessons_learned.field_category') }}</label>
                <select wire:model="formCategory"
                    class="w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    @foreach(['parking', 'registration', 'operations', 'other'] as $cat)
                        <option value="{{ $cat }}">{{ __('management.lessons_learned.category_'.$cat) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">{{ __('management.lessons_learned.field_convention_day') }}</label>
                <select wire:model="formConventionDay"
                    class="w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    @foreach(\App\Support\ConventionDay::lessonDayKeys() as $day)
                        <option value="{{ $day }}">{{ \App\Support\ConventionDay::label($day) }}</option>
                    @endforeach
                </select>
                @error('formConventionDay') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <flux:input wire:model="formTitle" label="{{ __('management.lessons_learned.field_title') }}" />

            <div>
                <label class="block text-sm font-medium mb-1">{{ __('management.lessons_learned.field_worked_well') }}</label>
                <textarea wire:model="formWorkedWell" rows="3"
                    class="w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"></textarea>
                @error('formWorkedWell') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">{{ __('management.lessons_learned.field_didnt_work_well') }}</label>
                <textarea wire:model="formDidntWorkWell" rows="3"
                    class="w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"></textarea>
                @error('formDidntWorkWell') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:button type="button" variant="ghost" wire:click="closeFormModal">{{ __('management.lessons_learned.cancel') }}</flux:button>
                <flux:button type="submit">{{ __('management.lessons_learned.save') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="detailModalOpen" class="max-w-2xl">
        @if($viewing)
            <flux:heading size="lg">{{ __('management.lessons_learned.modal_view') }}</flux:heading>
            <div class="mt-4 space-y-3 text-sm">
                <p><span class="font-semibold">{{ __('management.lessons_learned.col_source') }}:</span>
                    {{ $viewing->source === 'admin' ? __('management.lessons_learned.source_admin') : __('management.lessons_learned.source_public') }}</p>
                <p><span class="font-semibold">{{ __('management.lessons_learned.col_category') }}:</span>
                    {{ __('management.lessons_learned.category_'.$viewing->category) }}</p>
                <p><span class="font-semibold">{{ __('management.lessons_learned.col_day') }}:</span>
                    {{ \App\Support\ConventionDay::label($viewing->convention_day ?? 'all_days') }}</p>
                @if($viewing->title)
                    <p><span class="font-semibold">{{ __('management.lessons_learned.col_title') }}:</span> {{ $viewing->title }}</p>
                @endif
                <p><span class="font-semibold">{{ __('management.lessons_learned.col_reporter') }}:</span> {{ $viewing->reporter_name }}</p>
                @if($viewing->worked_well)
                    <p><span class="font-semibold">{{ __('management.lessons_learned.field_worked_well') }}:</span></p>
                    <p class="whitespace-pre-wrap rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900">{{ $viewing->worked_well }}</p>
                @endif
                @if($viewing->didnt_work_well)
                    <p><span class="font-semibold">{{ __('management.lessons_learned.field_didnt_work_well') }}:</span></p>
                    <p class="whitespace-pre-wrap rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900">{{ $viewing->didnt_work_well }}</p>
                @endif
            </div>
            <div class="mt-6 flex justify-end">
                <flux:button variant="ghost" wire:click="closeDetail">{{ __('management.lessons_learned.close') }}</flux:button>
            </div>
        @endif
    </flux:modal>
</div>
