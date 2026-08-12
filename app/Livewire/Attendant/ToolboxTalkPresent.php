<?php

declare(strict_types=1);

namespace App\Livewire\Attendant;

use App\Actions\ToolboxTalks\BuildToolboxTalkDeck;
use App\Models\CarPark;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class ToolboxTalkPresent extends Component
{
    public string $talkDate = '';

    public ?int $carParkId = null;

    public int $index = 0;

    /** @var list<array{title: string, body: string, section: string, section_label: string}> */
    public array $deck = [];

    public function mount(string $date, BuildToolboxTalkDeck $builder, ?CarPark $carPark = null): void
    {
        $this->talkDate = Carbon::parse($date)->toDateString();
        $this->carParkId = $carPark?->id;
        $this->deck = $builder->handle($this->talkDate, $this->carParkId);
        $this->index = 0;
    }

    public function next(): void
    {
        if ($this->index < count($this->deck) - 1) {
            $this->index++;
        }
    }

    public function previous(): void
    {
        if ($this->index > 0) {
            $this->index--;
        }
    }

    public function goTo(int $index): void
    {
        if ($index >= 0 && $index < count($this->deck)) {
            $this->index = $index;
        }
    }

    public function render()
    {
        $slide = $this->deck[$this->index] ?? null;
        $parkName = $this->carParkId
            ? CarPark::query()->whereKey($this->carParkId)->value('name')
            : null;

        return view('livewire.attendant.toolbox-talk-present', [
            'slide' => $slide,
            'total' => count($this->deck),
            'parkName' => $parkName,
        ]);
    }
}
