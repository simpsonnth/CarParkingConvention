<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\ToolboxTalks\CopyToolboxTalkFromDate;
use App\Actions\ToolboxTalks\LoadStandardToolboxReminders;
use App\Models\CarPark;
use App\Models\ToolboxTalk;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ToolboxTalks extends Component
{
    public string $talkDate = '';

    /** @var 'core'|string park id as string */
    public string $activeTab = 'core';

    /** @var list<array{id: ?int, title: string, body: string}> */
    public array $slides = [];

    public bool $showYesterday = false;

    public bool $confirmOverwriteCopy = false;

    public bool $confirmOverwriteReminders = false;

    public string $flashMessage = '';

    public function mount(): void
    {
        $this->talkDate = now()->toDateString();
        $this->loadSlides();
    }

    public function updatedTalkDate(): void
    {
        if ($this->talkDate === '' || strtotime($this->talkDate) === false) {
            return;
        }

        $this->flashMessage = '';
        $this->confirmOverwriteCopy = false;
        $this->confirmOverwriteReminders = false;
        $this->loadSlides();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'talkDate' => 'required|date',
            'slides' => 'array',
            'slides.*.title' => 'required|string|max:255',
            'slides.*.body' => 'nullable|string|max:10000',
        ];
    }

    public function updatedActiveTab(): void
    {
        $this->flashMessage = '';
        $this->confirmOverwriteCopy = false;
        $this->confirmOverwriteReminders = false;
        $this->loadSlides();
    }

    public function selectTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->flashMessage = '';
        $this->confirmOverwriteCopy = false;
        $this->confirmOverwriteReminders = false;
        $this->loadSlides();
    }

    public function addSlide(): void
    {
        $this->slides[] = [
            'id' => null,
            'title' => '',
            'body' => '',
        ];
    }

    public function removeSlide(int $index): void
    {
        if (! isset($this->slides[$index])) {
            return;
        }

        unset($this->slides[$index]);
        $this->slides = array_values($this->slides);
    }

    public function moveSlideUp(int $index): void
    {
        if ($index <= 0 || ! isset($this->slides[$index])) {
            return;
        }

        $tmp = $this->slides[$index - 1];
        $this->slides[$index - 1] = $this->slides[$index];
        $this->slides[$index] = $tmp;
        $this->slides = array_values($this->slides);
    }

    public function moveSlideDown(int $index): void
    {
        if (! isset($this->slides[$index], $this->slides[$index + 1])) {
            return;
        }

        $tmp = $this->slides[$index + 1];
        $this->slides[$index + 1] = $this->slides[$index];
        $this->slides[$index] = $tmp;
        $this->slides = array_values($this->slides);
    }

    public function save(): void
    {
        $this->validate();

        $talk = $this->resolveTalk(create: true);
        if ($talk === null) {
            return;
        }

        DB::transaction(function () use ($talk): void {
            $talk->slides()->delete();

            foreach (array_values($this->slides) as $order => $slide) {
                $talk->slides()->create([
                    'sort_order' => $order,
                    'title' => trim((string) $slide['title']),
                    'body' => trim((string) ($slide['body'] ?? '')) !== ''
                        ? trim((string) $slide['body'])
                        : null,
                ]);
            }
        });

        $this->loadSlides();
        $this->notify(__('toolbox_talks.saved_toast'));
    }

    public function copyFromYesterday(CopyToolboxTalkFromDate $copy): void
    {
        $this->validateOnly('talkDate');

        $talk = $this->resolveTalk(create: true);
        if ($talk === null) {
            return;
        }

        $talk->load('slides');
        if ($talk->slides->isNotEmpty() && ! $this->confirmOverwriteCopy) {
            $this->confirmOverwriteCopy = true;

            return;
        }

        $yesterday = Carbon::parse($this->talkDate)->subDay();
        $copied = $copy->handle($talk, $yesterday);
        $this->confirmOverwriteCopy = false;
        $this->loadSlides();

        $this->notify(
            $copied > 0
                ? __('toolbox_talks.copied_toast', ['count' => $copied])
                : __('toolbox_talks.copy_empty')
        );
    }

    public function loadStandardReminders(LoadStandardToolboxReminders $loader): void
    {
        if ($this->activeTab !== 'core') {
            return;
        }

        $this->validateOnly('talkDate');

        $talk = ToolboxTalk::firstOrCreateCore($this->talkDate);
        $talk->load('slides');

        if ($talk->slides->isNotEmpty() && ! $this->confirmOverwriteReminders) {
            $this->confirmOverwriteReminders = true;

            return;
        }

        $count = $loader->handle($this->talkDate, overwrite: true);
        $this->confirmOverwriteReminders = false;
        $this->loadSlides();

        $this->notify(__('toolbox_talks.reminders_loaded_toast', ['count' => $count]));
    }

    public function dismissFlash(): void
    {
        $this->flashMessage = '';
    }

    private function notify(string $message): void
    {
        $this->flashMessage = $message;

        try {
            Flux::toast($message, variant: 'success');
        } catch (\Throwable) {
            // Banner above is the reliable fallback.
        }
    }

    public function cancelOverwritePrompts(): void
    {
        $this->confirmOverwriteCopy = false;
        $this->confirmOverwriteReminders = false;
    }

    public function toggleYesterday(): void
    {
        $this->showYesterday = ! $this->showYesterday;
    }

    /**
     * @return list<array{title: string, body: string}>
     */
    public function yesterdaySlides(): array
    {
        $yesterday = Carbon::parse($this->talkDate)->subDay()->toDateString();
        $deckKey = $this->activeTab === 'core'
            ? ToolboxTalk::deckKeyForCore()
            : ToolboxTalk::deckKeyForPark((int) $this->activeTab);

        $talk = ToolboxTalk::query()
            ->whereDate('talk_date', $yesterday)
            ->where('deck_key', $deckKey)
            ->with('slides')
            ->first();

        if ($talk === null) {
            return [];
        }

        return $talk->slides->map(fn ($slide): array => [
            'title' => $slide->title,
            'body' => (string) ($slide->body ?? ''),
        ])->values()->all();
    }

    public function presentUrl(): ?string
    {
        if ($this->activeTab === 'core') {
            return route('attendant.toolbox-talk.present', ['date' => $this->talkDate]);
        }

        $parkId = (int) $this->activeTab;
        if ($parkId < 1) {
            return null;
        }

        return route('attendant.toolbox-talk.present', [
            'date' => $this->talkDate,
            'carPark' => $parkId,
        ]);
    }

    public function downloadPptxUrl(): ?string
    {
        if ($this->talkDate === '' || strtotime($this->talkDate) === false) {
            return null;
        }

        return route('admin.toolbox-talks.download-pptx', ['date' => $this->talkDate]);
    }

    public function downloadPdfUrl(): ?string
    {
        if ($this->talkDate === '' || strtotime($this->talkDate) === false) {
            return null;
        }

        return route('admin.toolbox-talks.download-pdf', ['date' => $this->talkDate]);
    }

    private function loadSlides(): void
    {
        $talk = $this->resolveTalk(create: false);
        if ($talk === null) {
            $this->slides = [];

            return;
        }

        $talk->load('slides');
        $this->slides = $talk->slides->map(fn ($slide): array => [
            'id' => $slide->id,
            'title' => $slide->title,
            'body' => (string) ($slide->body ?? ''),
        ])->values()->all();
    }

    private function resolveTalk(bool $create): ?ToolboxTalk
    {
        if ($this->activeTab === 'core') {
            return $create
                ? ToolboxTalk::firstOrCreateCore($this->talkDate)
                : ToolboxTalk::findCoreForDate($this->talkDate);
        }

        $parkId = (int) $this->activeTab;
        if ($parkId < 1 || ! CarPark::query()->whereKey($parkId)->exists()) {
            return null;
        }

        return $create
            ? ToolboxTalk::firstOrCreatePark($this->talkDate, $parkId)
            : ToolboxTalk::findParkForDate($this->talkDate, $parkId);
    }

    public function render()
    {
        $parks = CarPark::query()->orderBy('name')->get(['id', 'name']);

        return view('livewire.admin.toolbox-talks', [
            'parks' => $parks,
            'yesterdaySlides' => $this->showYesterday ? $this->yesterdaySlides() : [],
            'presentUrl' => $this->presentUrl(),
            'downloadPptxUrl' => $this->downloadPptxUrl(),
            'downloadPdfUrl' => $this->downloadPdfUrl(),
        ]);
    }
}
