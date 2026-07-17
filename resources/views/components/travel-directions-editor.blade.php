@props([
    'id',
    'model' => 'travelDirections',
])

<div
    class="space-y-2"
    x-data="{
        applyHeading(level) {
            const editor = this.$refs.editor;
            const start = editor.selectionStart;
            const end = editor.selectionEnd;
            const selected = editor.value.slice(start, end) || 'Heading';
            const prefix = '#'.repeat(level) + ' ';
            const formatted = selected
                .split('\n')
                .map(line => prefix + line.replace(/^#{1,6}\s+/, ''))
                .join('\n');

            editor.setRangeText(formatted, start, end, 'select');
            editor.dispatchEvent(new Event('input', { bubbles: true }));
            editor.focus();
        },
        applyBold() {
            const editor = this.$refs.editor;
            const start = editor.selectionStart;
            const end = editor.selectionEnd;
            const selected = editor.value.slice(start, end) || 'bold text';
            const formatted = '**' + selected + '**';

            editor.setRangeText(formatted, start, end, 'select');
            editor.dispatchEvent(new Event('input', { bubbles: true }));
            editor.focus();
        }
    }"
>
    <label for="{{ $id }}" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
        Travel Directions
    </label>
    <p class="text-xs text-zinc-500">
        Shown beside the map on printed tickets. Use the toolbar for headings and bold text. Max 2000 characters.
    </p>

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="flex flex-wrap gap-1 border-b border-zinc-200 bg-zinc-50 p-2 dark:border-zinc-700 dark:bg-zinc-900">
            <button type="button" x-on:click="applyHeading(2)"
                class="rounded px-2.5 py-1 text-xs font-semibold text-zinc-700 hover:bg-zinc-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:text-zinc-200 dark:hover:bg-zinc-700"
                title="Heading">
                Heading
            </button>
            <button type="button" x-on:click="applyHeading(3)"
                class="rounded px-2.5 py-1 text-xs font-semibold text-zinc-700 hover:bg-zinc-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:text-zinc-200 dark:hover:bg-zinc-700"
                title="Subheading">
                Subheading
            </button>
            <button type="button" x-on:click="applyBold()"
                class="rounded px-2.5 py-1 text-xs font-bold text-zinc-700 hover:bg-zinc-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:text-zinc-200 dark:hover:bg-zinc-700"
                title="Bold">
                Bold
            </button>
        </div>

        <textarea
            id="{{ $id }}"
            x-ref="editor"
            wire:model="{{ $model }}"
            rows="6"
            maxlength="2000"
            placeholder="Enter via Gate B, then follow the blue signs to Zone West."
            class="block w-full resize-y border-0 bg-white px-3 py-2 text-sm placeholder-zinc-400 focus:outline-none focus:ring-0 dark:bg-zinc-800 dark:text-zinc-200"
        ></textarea>
    </div>

    @error($model)
        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>
