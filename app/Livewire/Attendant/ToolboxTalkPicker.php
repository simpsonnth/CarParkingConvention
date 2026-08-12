<?php

declare(strict_types=1);

namespace App\Livewire\Attendant;

use App\Models\CarPark;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class ToolboxTalkPicker extends Component
{
    public string $talkDate = '';

    public function mount(): void
    {
        $this->talkDate = now()->toDateString();
    }

    public function start(?int $carParkId = null)
    {
        $params = ['date' => $this->talkDate];
        if ($carParkId !== null) {
            $params['carPark'] = $carParkId;
        }

        return $this->redirect(route('attendant.toolbox-talk.present', $params), navigate: true);
    }

    public function render()
    {
        return view('livewire.attendant.toolbox-talk-picker', [
            'parks' => CarPark::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
