@props([
    'attachmentsModel' => 'attachments',
    'voiceNotesModel' => 'voiceNotes',
    'removeAttachmentMethod' => 'removeAttachment',
    'removeVoiceNoteMethod' => 'removeVoiceNote',
    'labelPrefix' => 'lessons_learned',
    'pendingAttachments' => [],
    'pendingVoiceNotes' => [],
])

<div class="space-y-5">
    <div>
        <label class="block text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-2">
            {{ __($labelPrefix.'.attachments') }}
        </label>
        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">{{ __($labelPrefix.'.attachments_help') }}</p>
        <input
            type="file"
            wire:model="{{ $attachmentsModel }}"
            multiple
            class="block w-full text-sm text-zinc-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100 dark:text-zinc-300 dark:file:bg-indigo-900/40 dark:file:text-indigo-200"
        />
        <div wire:loading wire:target="{{ $attachmentsModel }}" class="mt-2 text-xs text-indigo-600 dark:text-indigo-300">
            {{ __($labelPrefix.'.uploading') }}
        </div>
        @error($attachmentsModel.'.*') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
        @error($attachmentsModel) <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror

        @if(is_array($pendingAttachments) && count($pendingAttachments) > 0)
            <ul class="mt-3 space-y-2">
                @foreach($pendingAttachments as $index => $file)
                    <li class="flex items-center justify-between gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900/50">
                        <span class="truncate">{{ method_exists($file, 'getClientOriginalName') ? $file->getClientOriginalName() : 'file' }}</span>
                        <button type="button" wire:click="{{ $removeAttachmentMethod }}({{ $index }})" class="text-xs font-semibold text-red-600 hover:underline">
                            {{ __($labelPrefix.'.remove') }}
                        </button>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div
        x-data="{
            propertyName: @js($voiceNotesModel),
            recording: false,
            uploading: false,
            error: '',
            mediaRecorder: null,
            chunks: [],
            startedAt: null,
            timerLabel: '0:00',
            timerId: null,
            async start() {
                this.error = '';
                if (!navigator.mediaDevices?.getUserMedia || typeof MediaRecorder === 'undefined') {
                    this.error = 'Voice recording is not supported in this browser.';
                    return;
                }
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    const mimeType = MediaRecorder.isTypeSupported('audio/webm')
                        ? 'audio/webm'
                        : (MediaRecorder.isTypeSupported('audio/mp4') ? 'audio/mp4' : '');
                    this.chunks = [];
                    this.mediaRecorder = mimeType ? new MediaRecorder(stream, { mimeType }) : new MediaRecorder(stream);
                    this.mediaRecorder.ondataavailable = (event) => {
                        if (event.data.size > 0) this.chunks.push(event.data);
                    };
                    this.mediaRecorder.onstop = () => {
                        stream.getTracks().forEach((track) => track.stop());
                        this.finishRecording();
                    };
                    this.mediaRecorder.start();
                    this.recording = true;
                    this.startedAt = Date.now();
                    this.timerLabel = '0:00';
                    this.timerId = setInterval(() => {
                        const seconds = Math.floor((Date.now() - this.startedAt) / 1000);
                        const mins = Math.floor(seconds / 60);
                        const secs = String(seconds % 60).padStart(2, '0');
                        this.timerLabel = mins + ':' + secs;
                    }, 250);
                } catch (e) {
                    this.error = 'Microphone permission denied or unavailable.';
                }
            },
            stop() {
                if (this.mediaRecorder && this.recording) this.mediaRecorder.stop();
                this.recording = false;
                if (this.timerId) {
                    clearInterval(this.timerId);
                    this.timerId = null;
                }
            },
            finishRecording() {
                const mimeType = this.mediaRecorder?.mimeType || 'audio/webm';
                const extension = mimeType.includes('mp4') ? 'm4a' : 'webm';
                const blob = new Blob(this.chunks, { type: mimeType });
                const file = new File([blob], 'voice-note-' + Date.now() + '.' + extension, { type: mimeType });
                const current = this.$wire.get(this.propertyName);
                const index = Array.isArray(current) ? current.length : 0;
                this.uploading = true;
                this.$wire.upload(
                    this.propertyName + '.' + index,
                    file,
                    () => { this.uploading = false; },
                    () => { this.uploading = false; this.error = 'Could not upload the voice note.'; },
                    () => {}
                );
            }
        }"
        class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/40 space-y-3"
    >
        <div>
            <div class="text-sm font-bold text-zinc-700 dark:text-zinc-300">{{ __($labelPrefix.'.voice_note') }}</div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">{{ __($labelPrefix.'.voice_note_help') }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                x-show="!recording"
                @click="start()"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
            >
                {{ __($labelPrefix.'.start_recording') }}
            </button>
            <button
                type="button"
                x-show="recording"
                x-cloak
                @click="stop()"
                class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-500"
            >
                {{ __($labelPrefix.'.stop_recording') }}
            </button>
            <span x-show="recording" x-cloak class="text-xs font-semibold text-red-600 dark:text-red-300" x-text="timerLabel"></span>
            <span x-show="uploading" x-cloak class="text-xs text-indigo-600 dark:text-indigo-300">{{ __($labelPrefix.'.uploading') }}</span>
        </div>

        <p x-show="error" x-cloak class="text-xs text-red-600" x-text="error"></p>

        @error($voiceNotesModel.'.*') <span class="text-red-500 text-xs block">{{ $message }}</span> @enderror
        @error($voiceNotesModel) <span class="text-red-500 text-xs block">{{ $message }}</span> @enderror

        @if(is_array($pendingVoiceNotes) && count($pendingVoiceNotes) > 0)
            <ul class="space-y-2">
                @foreach($pendingVoiceNotes as $index => $file)
                    <li class="flex items-center justify-between gap-3 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                        <span class="truncate">{{ method_exists($file, 'getClientOriginalName') ? $file->getClientOriginalName() : __('lessons_learned.voice_note') }}</span>
                        <button type="button" wire:click="{{ $removeVoiceNoteMethod }}({{ $index }})" class="text-xs font-semibold text-red-600 hover:underline">
                            {{ __($labelPrefix.'.remove') }}
                        </button>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
