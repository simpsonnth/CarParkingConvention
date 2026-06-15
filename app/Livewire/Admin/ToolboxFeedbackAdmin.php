<?php

namespace App\Livewire\Admin;

use App\Models\ToolboxFeedback;
use App\Support\ConventionDay;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class ToolboxFeedbackAdmin extends Component
{
    use WithPagination;

    public string $search = '';

    public string $addedFilter = '';

    public string $dayFilter = '';

    public int $perPage = 25;

    public bool $detailModalOpen = false;

    public bool $formModalOpen = false;

    public bool $remindersModalOpen = false;

    public ?int $viewingId = null;

    public ?int $editingId = null;

    public string $formSubmitterName = '';

    public string $formSubmitterEmail = '';

    public string $formSubmitterPhone = '';

    public string $formFeedback = '';

    /** @var '0'|'1' */
    public string $formAddedToToolboxTalk = '0';

    public string $formToolboxTalkDay = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedAddedFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDayFilter(): void
    {
        $this->resetPage();
    }

    public function openDetail(int $id): void
    {
        $this->viewingId = $id;
        $this->detailModalOpen = true;
    }

    public function closeDetail(): void
    {
        $this->detailModalOpen = false;
        $this->viewingId = null;
    }

    public function updatedDetailModalOpen(bool $value): void
    {
        if (! $value) {
            $this->viewingId = null;
        }
    }

    public function openCreate(): void
    {
        $this->resetFormFields();
        $this->editingId = null;
        $this->formModalOpen = true;
    }

    public function openEdit(int $id): void
    {
        $row = ToolboxFeedback::query()->findOrFail($id);
        $this->editingId = $row->id;
        $this->formSubmitterName = $row->submitter_name;
        $this->formSubmitterEmail = $row->submitter_email;
        $this->formSubmitterPhone = (string) ($row->submitter_phone ?? '');
        $this->formFeedback = $row->feedback;
        $this->formAddedToToolboxTalk = $row->added_to_toolbox_talk ? '1' : '0';
        $this->formToolboxTalkDay = (string) ($row->toolbox_talk_day ?? '');
        $this->resetErrorBag();
        $this->formModalOpen = true;
    }

    public function closeFormModal(): void
    {
        $this->formModalOpen = false;
    }

    public function updatedFormModalOpen(bool $value): void
    {
        if (! $value) {
            $this->resetFormFields();
        }
    }

    private function resetFormFields(): void
    {
        $this->editingId = null;
        $this->formSubmitterName = '';
        $this->formSubmitterEmail = '';
        $this->formSubmitterPhone = '';
        $this->formFeedback = '';
        $this->formAddedToToolboxTalk = '0';
        $this->formToolboxTalkDay = '';
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $rules = [
            'formSubmitterName' => 'required|string|max:255',
            'formSubmitterEmail' => 'required|email|max:255',
            'formSubmitterPhone' => 'nullable|string|max:50',
            'formFeedback' => 'required|string|min:10|max:5000',
            'formAddedToToolboxTalk' => 'required|in:0,1',
        ];

        $added = $this->formAddedToToolboxTalk === '1';
        if ($added) {
            $rules['formToolboxTalkDay'] = 'required|in:'.implode(',', ConventionDay::singleDayKeys());
        } else {
            $rules['formToolboxTalkDay'] = 'nullable|in:'.implode(',', ConventionDay::singleDayKeys());
        }

        $this->validate($rules);

        $payload = [
            'submitter_name' => trim($this->formSubmitterName),
            'submitter_email' => trim($this->formSubmitterEmail),
            'submitter_phone' => trim($this->formSubmitterPhone) !== '' ? trim($this->formSubmitterPhone) : null,
            'feedback' => trim($this->formFeedback),
            'added_to_toolbox_talk' => $added,
            'toolbox_talk_day' => $added ? $this->formToolboxTalkDay : null,
        ];

        if ($this->editingId !== null) {
            $row = ToolboxFeedback::query()->findOrFail($this->editingId);
            $row->fill($payload);
            $row->save();
        } else {
            ToolboxFeedback::create($payload);
        }

        $this->closeFormModal();

        try {
            Flux::toast(__('management.toolbox_feedback.saved_toast'));
        } catch (\Throwable) {
            session()->flash('status', __('management.toolbox_feedback.saved_toast'));
        }
    }

    public function render()
    {
        $query = ToolboxFeedback::query();

        if ($this->search !== '') {
            $term = '%'.addcslashes($this->search, '%_\\').'%';
            $query->where(function ($q) use ($term) {
                $q->where('submitter_name', 'like', $term)
                    ->orWhere('submitter_email', 'like', $term)
                    ->orWhere('feedback', 'like', $term);
            });
        }

        if ($this->addedFilter === 'yes') {
            $query->where('added_to_toolbox_talk', true);
        } elseif ($this->addedFilter === 'no') {
            $query->where('added_to_toolbox_talk', false);
        }

        if ($this->dayFilter !== '') {
            $query->where('toolbox_talk_day', $this->dayFilter);
        }

        $rows = $query->orderByDesc('created_at')->paginate($this->perPage);
        $total = ToolboxFeedback::query()->count();

        $viewing = $this->viewingId !== null
            ? ToolboxFeedback::query()->find($this->viewingId)
            : null;

        return view('livewire.admin.toolbox-feedback', [
            'rows' => $rows,
            'total' => $total,
            'viewing' => $viewing,
        ]);
    }
}
