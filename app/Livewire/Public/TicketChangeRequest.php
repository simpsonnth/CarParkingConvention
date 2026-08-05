<?php

declare(strict_types=1);

namespace App\Livewire\Public;

use App\Models\Congregation;
use App\Models\TicketChangeRequest as TicketChangeRequestModel;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class TicketChangeRequest extends Component
{
    public string $name = '';

    public string $congregation = '';

    public string $notes = '';

    public bool $submitted = false;

    public function submitAnother(): void
    {
        $this->reset(['name', 'congregation', 'notes', 'submitted']);
    }

    public function submit(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'congregation' => 'required|string|exists:congregations,name',
            'notes' => 'required|string|min:10|max:5000',
        ]);

        TicketChangeRequestModel::query()->create([
            'name' => trim($this->name),
            'congregation' => trim($this->congregation),
            'notes' => trim($this->notes),
        ]);

        $this->submitted = true;

        try {
            Flux::toast(__('ticket_change_request.complete_title'));
        } catch (\Throwable) {
            session()->flash('status', __('ticket_change_request.complete_title'));
        }
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function congregations(): array
    {
        return Congregation::query()->orderBy('name')->pluck('name')->all();
    }

    public function render()
    {
        return view('livewire.public.ticket-change-request');
    }
}
