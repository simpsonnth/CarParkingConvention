<?php

namespace App\Livewire\Public;

use App\Models\ToolboxFeedback as ToolboxFeedbackModel;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class ToolboxFeedback extends Component
{
    public string $submitterName = '';

    public string $submitterEmail = '';

    public string $submitterPhone = '';

    public string $feedback = '';

    public bool $submitted = false;

    public function submitAnother(): void
    {
        $this->reset([
            'submitterName',
            'submitterEmail',
            'submitterPhone',
            'feedback',
            'submitted',
        ]);
    }

    public function submit(): void
    {
        $this->validate([
            'submitterName' => 'required|string|max:255',
            'submitterEmail' => 'required|email|max:255',
            'submitterPhone' => 'nullable|string|max:50',
            'feedback' => 'required|string|min:10|max:5000',
        ]);

        ToolboxFeedbackModel::create([
            'submitter_name' => trim($this->submitterName),
            'submitter_email' => trim($this->submitterEmail),
            'submitter_phone' => trim($this->submitterPhone) !== '' ? trim($this->submitterPhone) : null,
            'feedback' => trim($this->feedback),
        ]);

        $this->submitted = true;

        try {
            Flux::toast(__('toolbox_feedback.complete_title'));
        } catch (\Throwable) {
            session()->flash('status', __('toolbox_feedback.complete_title'));
        }
    }

    public function render()
    {
        return view('livewire.public.toolbox-feedback');
    }
}
