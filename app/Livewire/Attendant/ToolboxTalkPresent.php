<?php

declare(strict_types=1);

namespace App\Livewire\Attendant;

use App\Actions\ToolboxTalks\BuildToolboxTalkDeck;
use App\Actions\ToolboxTalks\ResolveToolboxTalkCover;
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

    /** @var list<array{type?: string, title: string, body: string, section: string, section_label: string, car_park_id?: int|null}> */
    public array $deck = [];

    public string $coverUrl = '';

    public function mount(
        string $date,
        BuildToolboxTalkDeck $builder,
        ResolveToolboxTalkCover $resolveCover,
        ?CarPark $carPark = null,
    ): void {
        $this->talkDate = Carbon::parse($date)->toDateString();
        $this->carParkId = $carPark?->id;
        $this->deck = $builder->handle($this->talkDate, $this->carParkId);
        $this->coverUrl = $resolveCover->url($this->carParkId);
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

        $slideCoverUrl = $this->coverUrl;
        if (is_array($slide) && ($slide['type'] ?? '') === 'cover') {
            $slideParkId = isset($slide['car_park_id']) ? (int) $slide['car_park_id'] : $this->carParkId;
            $slideCoverUrl = app(ResolveToolboxTalkCover::class)->url($slideParkId);
        }

        return view('livewire.attendant.toolbox-talk-present', [
            'slide' => $slide,
            'total' => count($this->deck),
            'parkName' => $parkName,
            'coverUrl' => $slideCoverUrl,
            'isCoverSlide' => is_array($slide) && ($slide['type'] ?? '') === 'cover',
        ]);
    }
}
