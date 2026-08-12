<?php

declare(strict_types=1);

namespace App\Livewire\Attendant;

use App\Actions\ToolboxTalks\ResolveToolboxTalkCover;
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

    public function render(ResolveToolboxTalkCover $resolveCover)
    {
        $parks = CarPark::query()->orderBy('name')->get(['id', 'name']);

        return view('livewire.attendant.toolbox-talk-picker', [
            'parks' => $parks,
            'parkCovers' => $parks->mapWithKeys(
                fn (CarPark $park): array => [$park->id => $resolveCover->url($park->id)]
            )->all(),
            'defaultCover' => $resolveCover->url(null),
        ]);
    }
}
