<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('management.toolbox_feedback.title') }}</flux:heading>
            <flux:subheading>{{ __('management.toolbox_feedback.subtitle') }}</flux:subheading>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <flux:button wire:click="$set('remindersModalOpen', true)" icon="clipboard-document-list">
                {{ __('toolbox_talk_reminders.button') }}
            </flux:button>
            <flux:button wire:click="openCreate" icon="plus">{{ __('management.toolbox_feedback.add') }}</flux:button>
            <select wire:model.live="addedFilter"
                class="block w-full sm:w-auto rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                <option value="">{{ __('management.toolbox_feedback.filter_added_all') }}</option>
                <option value="yes">{{ __('management.toolbox_feedback.filter_added_yes') }}</option>
                <option value="no">{{ __('management.toolbox_feedback.filter_added_no') }}</option>
            </select>
            <select wire:model.live="dayFilter"
                class="block w-full sm:w-auto rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                <option value="">{{ __('management.toolbox_feedback.filter_day_all') }}</option>
                @foreach(\App\Support\ConventionDay::singleDayKeys() as $day)
                    <option value="{{ $day }}">{{ \App\Support\ConventionDay::label($day) }}</option>
                @endforeach
            </select>
            <a href="{{ route('admin.toolbox-feedback.export') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700"
                download>
                <flux:icon name="arrow-down-tray" class="size-4" />
                {{ __('management.toolbox_feedback.export') }}
            </a>
            <select wire:model.live="perPage"
                class="block w-full sm:w-auto rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                <option value="25">25 {{ __('management.toolbox_feedback.per_page') }}</option>
                <option value="50">50 {{ __('management.toolbox_feedback.per_page') }}</option>
                <option value="100">100 {{ __('management.toolbox_feedback.per_page') }}</option>
            </select>
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                placeholder="{{ __('management.toolbox_feedback.search') }}" class="w-full min-w-0 sm:min-w-[220px]" />
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900/50">
        {{ __('management.toolbox_feedback.total', ['count' => $total]) }}
    </div>

    <flux:separator />

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">
                <tr>
                    <th class="px-4 py-3">{{ __('management.toolbox_feedback.col_submitted') }}</th>
                    <th class="px-4 py-3">{{ __('management.toolbox_feedback.col_name') }}</th>
                    <th class="px-4 py-3">{{ __('management.toolbox_feedback.col_added_to_talk') }}</th>
                    <th class="px-4 py-3">{{ __('management.toolbox_feedback.col_toolbox_day') }}</th>
                    <th class="px-4 py-3">{{ __('management.toolbox_feedback.col_preview') }}</th>
                    <th class="px-4 py-3">{{ __('management.toolbox_feedback.col_actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($rows as $row)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td class="px-4 py-3 whitespace-nowrap">{{ $row->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3">{{ $row->submitter_name }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $row->added_to_toolbox_talk ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400' }}">
                                {{ $row->added_to_toolbox_talk ? __('management.parking_incidents.yes') : __('management.parking_incidents.no') }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            {{ $row->toolbox_talk_day ? \App\Support\ConventionDay::label($row->toolbox_talk_day) : '—' }}
                        </td>
                        <td class="px-4 py-3">{{ Str::limit($row->feedback, 50) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                <flux:button size="sm" variant="ghost" wire:click="openDetail({{ $row->id }})">
                                    {{ __('management.toolbox_feedback.view') }}
                                </flux:button>
                                <flux:button size="sm" variant="ghost" wire:click="openEdit({{ $row->id }})">
                                    {{ __('management.toolbox_feedback.edit') }}
                                </flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-zinc-500">{{ __('management.toolbox_feedback.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $rows->links() }}</div>

    <flux:modal wire:model="remindersModalOpen" class="w-[calc(100vw-2rem)] max-w-3xl max-h-[90vh] overflow-y-auto">
        <div id="toolbox-talk-reminders-content" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('toolbox_talk_reminders.modal_title') }}</flux:heading>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ __('toolbox_talk_reminders.modal_intro') }}</p>
            </div>

            @foreach(__('toolbox_talk_reminders.sections') as $section)
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <flux:heading size="md">{{ $section['title'] }}</flux:heading>
                    @if(! empty($section['intro']))
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $section['intro'] }}</p>
                    @endif
                    <ul class="mt-3 list-disc space-y-2 pl-5 text-sm text-zinc-800 dark:text-zinc-200">
                        @foreach($section['items'] as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            <div class="flex justify-end gap-2 pt-2 print:hidden">
                <flux:button type="button" variant="ghost" icon="printer"
                    x-on:click="window.print()">
                    {{ __('toolbox_talk_reminders.print') }}
                </flux:button>
                <flux:button type="button" variant="ghost" wire:click="$set('remindersModalOpen', false)">
                    {{ __('toolbox_talk_reminders.close') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="formModalOpen" class="max-w-2xl">
        <flux:heading size="lg">{{ $editingId ? __('management.toolbox_feedback.modal_edit') : __('management.toolbox_feedback.modal_create') }}</flux:heading>
        <form wire:submit="save" class="mt-4 space-y-4">
            <flux:input wire:model="formSubmitterName" label="{{ __('management.toolbox_feedback.field_name') }}" />
            @error('formSubmitterName') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror

            <flux:input wire:model="formSubmitterEmail" type="email" label="{{ __('management.toolbox_feedback.field_email') }}" />
            @error('formSubmitterEmail') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror

            <flux:input wire:model="formSubmitterPhone" label="{{ __('management.toolbox_feedback.field_phone') }}" />
            @error('formSubmitterPhone') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror

            <div>
                <label class="block text-sm font-medium mb-1">{{ __('management.toolbox_feedback.field_feedback') }}</label>
                <textarea wire:model="formFeedback" rows="4"
                    class="w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"></textarea>
                @error('formFeedback') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">{{ __('management.toolbox_feedback.field_added_to_talk') }}</label>
                <div class="flex gap-3">
                    <button type="button" wire:click="$set('formAddedToToolboxTalk', '0')"
                        class="flex-1 p-2 rounded-lg border-2 text-sm font-semibold {{ $formAddedToToolboxTalk === '0' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-zinc-200 dark:border-zinc-600' }}">
                        {{ __('management.parking_incidents.no') }}
                    </button>
                    <button type="button" wire:click="$set('formAddedToToolboxTalk', '1')"
                        class="flex-1 p-2 rounded-lg border-2 text-sm font-semibold {{ $formAddedToToolboxTalk === '1' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-zinc-200 dark:border-zinc-600' }}">
                        {{ __('management.parking_incidents.yes') }}
                    </button>
                </div>
            </div>

            @if($formAddedToToolboxTalk === '1')
                <div>
                    <label class="block text-sm font-medium mb-1">{{ __('management.toolbox_feedback.field_toolbox_day') }}</label>
                    <select wire:model="formToolboxTalkDay"
                        class="w-full rounded-lg border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                        <option value="">{{ __('management.toolbox_feedback.field_toolbox_day_none') }}</option>
                        @foreach(\App\Support\ConventionDay::singleDayKeys() as $day)
                            <option value="{{ $day }}">{{ \App\Support\ConventionDay::label($day) }}</option>
                        @endforeach
                    </select>
                    @error('formToolboxTalkDay') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            @endif

            <div class="flex justify-end gap-2 pt-2">
                <flux:button type="button" variant="ghost" wire:click="closeFormModal">{{ __('management.toolbox_feedback.cancel') }}</flux:button>
                <flux:button type="submit">{{ __('management.toolbox_feedback.save') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="detailModalOpen" class="max-w-2xl">
        @if($viewing)
            <flux:heading size="lg">{{ __('management.toolbox_feedback.detail_title') }}</flux:heading>
            <div class="mt-4 space-y-3 text-sm">
                <p><span class="font-semibold">{{ __('management.toolbox_feedback.col_name') }}:</span> {{ $viewing->submitter_name }}</p>
                <p><span class="font-semibold">{{ __('management.toolbox_feedback.col_email') }}:</span> {{ $viewing->submitter_email }}</p>
                @if($viewing->submitter_phone)
                    <p><span class="font-semibold">{{ __('management.toolbox_feedback.detail_phone') }}:</span> {{ $viewing->submitter_phone }}</p>
                @endif
                <p><span class="font-semibold">{{ __('management.toolbox_feedback.detail_added_to_talk') }}:</span>
                    {{ $viewing->added_to_toolbox_talk ? __('management.parking_incidents.yes') : __('management.parking_incidents.no') }}</p>
                @if($viewing->toolbox_talk_day)
                    <p><span class="font-semibold">{{ __('management.toolbox_feedback.detail_toolbox_day') }}:</span>
                        {{ \App\Support\ConventionDay::label($viewing->toolbox_talk_day) }}</p>
                @endif
                <p><span class="font-semibold">{{ __('management.toolbox_feedback.detail_feedback') }}:</span></p>
                <p class="whitespace-pre-wrap rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900">{{ $viewing->feedback }}</p>
                <p class="text-zinc-500">{{ __('management.toolbox_feedback.col_submitted') }}: {{ $viewing->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</p>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="openEdit({{ $viewing->id }})">{{ __('management.toolbox_feedback.edit') }}</flux:button>
                <flux:button variant="ghost" wire:click="closeDetail">{{ __('management.toolbox_feedback.close') }}</flux:button>
            </div>
        @endif
    </flux:modal>
</div>
