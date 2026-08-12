<?php

declare(strict_types=1);

namespace App\Livewire\Public;

use App\Actions\LessonsLearned\StoreLessonLearnedAttachments;
use App\Livewire\Concerns\HandlesLessonLearnedUploads;
use App\Models\LessonLearned as LessonLearnedModel;
use App\Support\ConventionDay;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.public')]
class LessonLearned extends Component
{
    use HandlesLessonLearnedUploads;
    use WithFileUploads;

    public string $reporterName = '';

    public string $category = LessonLearnedModel::CATEGORY_PARKING;

    public string $conventionDay = ConventionDay::ALL_DAYS;

    public string $title = '';

    public string $workedWell = '';

    public string $didntWorkWell = '';

    public bool $submitted = false;

    public function submitAnother(): void
    {
        $this->reset([
            'reporterName',
            'title',
            'workedWell',
            'didntWorkWell',
            'submitted',
            'attachments',
            'voiceNotes',
        ]);
        $this->category = LessonLearnedModel::CATEGORY_PARKING;
        $this->conventionDay = ConventionDay::ALL_DAYS;
    }

    public function removeAttachment(int $index): void
    {
        unset($this->attachments[$index]);
        $this->attachments = array_values($this->attachments);
    }

    public function removeVoiceNote(int $index): void
    {
        unset($this->voiceNotes[$index]);
        $this->voiceNotes = array_values($this->voiceNotes);
    }

    public function submit(StoreLessonLearnedAttachments $storeAttachments): void
    {
        $this->validate(array_merge([
            'reporterName' => 'required|string|max:255',
            'category' => 'required|in:'.implode(',', LessonLearnedModel::categoryKeys()),
            'conventionDay' => 'required|in:'.implode(',', ConventionDay::lessonDayKeys()),
            'title' => 'nullable|string|max:255',
            'workedWell' => 'nullable|string|max:5000',
            'didntWorkWell' => 'nullable|string|max:5000',
        ], $this->lessonUploadValidationRules()));

        $worked = trim($this->workedWell);
        $didnt = trim($this->didntWorkWell);

        if ($worked === '' && $didnt === '' && $this->attachments === [] && $this->voiceNotes === []) {
            $this->addError('workedWell', __('lessons_learned.validation_lesson_content'));

            return;
        }

        $lesson = LessonLearnedModel::query()->create([
            'source' => LessonLearnedModel::SOURCE_PUBLIC,
            'created_by_user_id' => null,
            'reporter_name' => trim($this->reporterName),
            'category' => $this->category,
            'convention_day' => $this->conventionDay,
            'title' => trim($this->title) !== '' ? trim($this->title) : null,
            'worked_well' => $worked !== '' ? $worked : null,
            'didnt_work_well' => $didnt !== '' ? $didnt : null,
        ]);

        $this->storeLessonUploads($lesson, $this->attachments, $this->voiceNotes, $storeAttachments);
        $this->resetLessonUploads();

        $this->submitted = true;

        try {
            Flux::toast(__('lessons_learned.complete_title'));
        } catch (\Throwable) {
            session()->flash('status', __('lessons_learned.complete_title'));
        }
    }

    public function render()
    {
        return view('livewire.public.lesson-learned');
    }
}
