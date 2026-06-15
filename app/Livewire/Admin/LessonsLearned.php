<?php

namespace App\Livewire\Admin;

use App\Models\LessonLearned;
use App\Support\ConventionDay;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class LessonsLearned extends Component
{
    use WithPagination;

    public string $search = '';

    public string $categoryFilter = '';

    public string $dayFilter = '';

    public int $perPage = 25;

    public bool $formModalOpen = false;

    public bool $detailModalOpen = false;

    public ?int $editingId = null;

    public ?int $viewingId = null;

    public string $formReporterName = '';

    public string $formCategory = LessonLearned::CATEGORY_PARKING;

    public string $formConventionDay = ConventionDay::ALL_DAYS;

    public string $formTitle = '';

    public string $formWorkedWell = '';

    public string $formDidntWorkWell = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDayFilter(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetFormFields();
        $this->editingId = null;
        $this->formModalOpen = true;
    }

    public function openEdit(int $id): void
    {
        $lesson = LessonLearned::query()->findOrFail($id);
        $this->editingId = $lesson->id;
        $this->formReporterName = $lesson->reporter_name;
        $this->formCategory = $lesson->category;
        $this->formConventionDay = $lesson->convention_day ?? ConventionDay::ALL_DAYS;
        $this->formTitle = (string) ($lesson->title ?? '');
        $this->formWorkedWell = (string) ($lesson->worked_well ?? '');
        $this->formDidntWorkWell = (string) ($lesson->didnt_work_well ?? '');
        $this->resetErrorBag();
        $this->formModalOpen = true;
    }

    public function openDetail(int $id): void
    {
        $this->viewingId = $id;
        $this->detailModalOpen = true;
    }

    public function closeFormModal(): void
    {
        $this->formModalOpen = false;
    }

    public function closeDetail(): void
    {
        $this->detailModalOpen = false;
        $this->viewingId = null;
    }

    public function updatedFormModalOpen(bool $value): void
    {
        if (! $value) {
            $this->resetFormFields();
        }
    }

    public function updatedDetailModalOpen(bool $value): void
    {
        if (! $value) {
            $this->viewingId = null;
        }
    }

    private function resetFormFields(): void
    {
        $this->editingId = null;
        $this->formReporterName = '';
        $this->formCategory = LessonLearned::CATEGORY_PARKING;
        $this->formConventionDay = ConventionDay::ALL_DAYS;
        $this->formTitle = '';
        $this->formWorkedWell = '';
        $this->formDidntWorkWell = '';
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $this->validate([
            'formReporterName' => 'required|string|max:255',
            'formCategory' => 'required|in:'.implode(',', LessonLearned::categoryKeys()),
            'formConventionDay' => 'required|in:'.implode(',', ConventionDay::lessonDayKeys()),
            'formTitle' => 'nullable|string|max:255',
            'formWorkedWell' => 'nullable|string|max:5000',
            'formDidntWorkWell' => 'nullable|string|max:5000',
        ]);

        $worked = trim($this->formWorkedWell);
        $didnt = trim($this->formDidntWorkWell);

        if ($worked === '' && $didnt === '') {
            $this->addError('formWorkedWell', __('management.lessons_learned.validation_lesson_content'));

            return;
        }

        $payload = [
            'reporter_name' => trim($this->formReporterName),
            'category' => $this->formCategory,
            'convention_day' => $this->formConventionDay,
            'title' => trim($this->formTitle) !== '' ? trim($this->formTitle) : null,
            'worked_well' => $worked !== '' ? $worked : null,
            'didnt_work_well' => $didnt !== '' ? $didnt : null,
        ];

        if ($this->editingId !== null) {
            $lesson = LessonLearned::query()->findOrFail($this->editingId);
            $lesson->fill($payload);
            $lesson->save();
        } else {
            LessonLearned::create(array_merge($payload, [
                'source' => LessonLearned::SOURCE_ADMIN,
                'created_by_user_id' => auth()->id(),
            ]));
        }

        $this->closeFormModal();

        try {
            Flux::toast(__('management.lessons_learned.saved_toast'));
        } catch (\Throwable) {
            session()->flash('status', __('management.lessons_learned.saved_toast'));
        }
    }

    public function delete(int $id): void
    {
        LessonLearned::query()->findOrFail($id)->delete();

        try {
            Flux::toast(__('management.lessons_learned.deleted_toast'));
        } catch (\Throwable) {
            session()->flash('status', __('management.lessons_learned.deleted_toast'));
        }
    }

    public function render()
    {
        $query = LessonLearned::query()->with('createdBy');

        if ($this->search !== '') {
            $term = '%'.addcslashes($this->search, '%_\\').'%';
            $query->where(function ($q) use ($term) {
                $q->where('reporter_name', 'like', $term)
                    ->orWhere('title', 'like', $term)
                    ->orWhere('worked_well', 'like', $term)
                    ->orWhere('didnt_work_well', 'like', $term);
            });
        }

        if ($this->categoryFilter !== '') {
            $query->where('category', $this->categoryFilter);
        }

        if ($this->dayFilter !== '') {
            $query->where('convention_day', $this->dayFilter);
        }

        $rows = $query->orderByDesc('created_at')->paginate($this->perPage);
        $total = LessonLearned::query()->count();

        $viewing = $this->viewingId !== null
            ? LessonLearned::query()->with('createdBy')->find($this->viewingId)
            : null;

        return view('livewire.admin.lessons-learned', [
            'rows' => $rows,
            'total' => $total,
            'viewing' => $viewing,
        ]);
    }
}
