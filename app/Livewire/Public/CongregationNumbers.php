<?php

namespace App\Livewire\Public;

use App\Models\Congregation;
use App\Models\CongregationNumbersResponse;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class CongregationNumbers extends Component
{
    public string $congregationCode = '';

    public $carParkTicketsCount = 0;

    /** @var '0'|'1' */
    public string $organizesCoach = '0';

    /** @var '0'|'1' */
    public string $sharingCoachWithOthers = '0';

    /** @var list<int|string> */
    public array $sharedWithCongregationIds = [];

    /** Search text to find congregations to add when sharing a coach */
    public string $shareSearch = '';

    public string $coachSize = '';

    /** @var '0'|'1' */
    public string $disabledParkingRequired = '0';

    public $disabledParkingCount = '';

    public bool $submitted = false;

    #[Layout('components.layouts.public')]
    #[Computed]
    public function resolvedCongregation(): ?Congregation
    {
        $code = trim($this->congregationCode);
        if ($code === '') {
            return null;
        }

        return Congregation::where('uuid', $code)->first();
    }

    /** Congregations already chosen for shared coach (for chip labels). */
    #[Computed]
    public function selectedSharedCongregations(): Collection
    {
        $ids = array_values(array_unique(array_map('intval', $this->sharedWithCongregationIds)));
        if ($ids === []) {
            return collect();
        }

        return Congregation::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function shareSearchReady(): bool
    {
        return mb_strlen(trim($this->shareSearch)) >= 2;
    }

    /** Matches for the current search (excludes self and already-added). */
    #[Computed]
    public function shareSearchMatches(): Collection
    {
        $term = trim($this->shareSearch);
        if (mb_strlen($term) < 2) {
            return collect();
        }

        $excludeId = $this->resolvedCongregation?->id;
        $selected = array_values(array_unique(array_map('intval', $this->sharedWithCongregationIds)));

        $query = Congregation::query()
            ->orderBy('name')
            ->where('name', 'like', '%'.addcslashes($term, '%_\\').'%');

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }
        if ($selected !== []) {
            $query->whereNotIn('id', $selected);
        }

        return $query->limit(30)->get(['id', 'name']);
    }

    public function addSharedCongregation(int $id): void
    {
        $self = $this->resolvedCongregation;
        if ($self !== null && $id === $self->id) {
            return;
        }

        $ids = array_map('intval', $this->sharedWithCongregationIds);
        if (! in_array($id, $ids, true)) {
            $this->sharedWithCongregationIds[] = $id;
        }

        $this->shareSearch = '';
    }

    public function removeSharedCongregation(int $id): void
    {
        $this->sharedWithCongregationIds = array_values(array_filter(
            array_map('intval', $this->sharedWithCongregationIds),
            fn (int $x): bool => $x !== $id
        ));
    }

    public function render()
    {
        return view('livewire.public.congregation-numbers');
    }

    public function submitAnother(): void
    {
        $this->reset([
            'congregationCode',
            'carParkTicketsCount',
            'organizesCoach',
            'sharingCoachWithOthers',
            'sharedWithCongregationIds',
            'shareSearch',
            'coachSize',
            'disabledParkingRequired',
            'disabledParkingCount',
            'submitted',
        ]);
        $this->carParkTicketsCount = 0;
        $this->organizesCoach = '0';
        $this->sharingCoachWithOthers = '0';
        $this->sharedWithCongregationIds = [];
        $this->shareSearch = '';
        $this->disabledParkingRequired = '0';
        $this->submitted = false;
    }

    public function submit(): void
    {
        $rules = [
            'congregationCode' => 'required|string',
            'carParkTicketsCount' => 'required|integer|min:0',
            'organizesCoach' => 'required|in:0,1',
            'disabledParkingRequired' => 'required|in:0,1',
        ];

        if ($this->organizesCoach === '1') {
            $rules['sharingCoachWithOthers'] = 'required|in:0,1';
            if ($this->sharingCoachWithOthers === '1') {
                $rules['sharedWithCongregationIds'] = 'required|array|min:1';
                $rules['sharedWithCongregationIds.*'] = 'integer|distinct|exists:congregations,id';
                $rules['coachSize'] = 'required|string|in:minibus,small_coach,large_coach';
            }
        }

        if ($this->disabledParkingRequired === '1') {
            $rules['disabledParkingCount'] = 'required|integer|min:1';
        }

        $this->validate($rules);

        $congregation = $this->resolvedCongregation;
        if (! $congregation) {
            $this->addError('congregationCode', __('congregation_numbers.invalid_congregation_code'));

            return;
        }

        $organizes = $this->organizesCoach === '1';
        $sharing = $organizes && $this->sharingCoachWithOthers === '1';
        $disabledReq = $this->disabledParkingRequired === '1';

        $sharedIds = $sharing
            ? array_values(array_unique(array_map('intval', $this->sharedWithCongregationIds)))
            : [];

        if ($sharing && in_array($congregation->id, $sharedIds, true)) {
            $this->addError('sharedWithCongregationIds', __('congregation_numbers.cannot_share_with_self'));

            return;
        }

        $payload = [
            'car_park_tickets_count' => (int) $this->carParkTicketsCount,
            'organizes_coach' => $organizes,
            'sharing_coach_with_others' => $organizes ? ($this->sharingCoachWithOthers === '1') : null,
            'shared_with_congregation_ids' => $sharing ? $sharedIds : null,
            'coach_size' => $sharing ? $this->coachSize : null,
            'disabled_parking_required' => $disabledReq,
            'disabled_parking_count' => $disabledReq ? (int) $this->disabledParkingCount : null,
            'submitted_locale' => app()->getLocale(),
        ];

        $existing = CongregationNumbersResponse::withTrashed()
            ->where('congregation_id', $congregation->id)
            ->first();

        if ($existing === null) {
            CongregationNumbersResponse::create(array_merge($payload, [
                'congregation_id' => $congregation->id,
            ]));
        } else {
            if ($existing->trashed()) {
                $existing->restore();
            }
            $existing->fill($payload);
            $existing->save();
        }

        $this->submitted = true;

        try {
            Flux::toast(__('congregation_numbers.complete_title'));
        } catch (\Throwable) {
            session()->flash('status', __('congregation_numbers.complete_title'));
        }
    }
}
