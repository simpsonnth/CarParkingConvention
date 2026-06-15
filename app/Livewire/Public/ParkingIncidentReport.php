<?php

namespace App\Livewire\Public;

use App\Models\CarPark;
use App\Models\ParkingIncidentReport as ParkingIncidentReportModel;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class ParkingIncidentReport extends Component
{
    public string $type = ParkingIncidentReportModel::TYPE_NEAR_MISS;

    public string $occurredAt = '';

    public string $location = '';

    public string $carParkId = '';

    public string $description = '';

    public string $actionsTaken = '';

    /** @var '0'|'1' */
    public string $injuryReported = '0';

    public string $severity = '';

    public string $reporterName = '';

    public string $reporterEmail = '';

    public string $reporterPhone = '';

    public bool $submitted = false;

    public function mount(): void
    {
        $this->occurredAt = now()->format('Y-m-d\TH:i');
    }

    #[Computed]
    public function carParks(): Collection
    {
        return CarPark::query()->orderBy('name')->get(['id', 'name']);
    }

    public function submitAnother(): void
    {
        $this->reset([
            'type',
            'location',
            'carParkId',
            'description',
            'actionsTaken',
            'injuryReported',
            'severity',
            'reporterName',
            'reporterEmail',
            'reporterPhone',
            'submitted',
        ]);
        $this->type = ParkingIncidentReportModel::TYPE_NEAR_MISS;
        $this->injuryReported = '0';
        $this->occurredAt = now()->format('Y-m-d\TH:i');
    }

    public function submit(): void
    {
        $rules = [
            'type' => 'required|in:'.implode(',', ParkingIncidentReportModel::typeKeys()),
            'occurredAt' => 'required|date',
            'location' => 'required|string|max:255',
            'carParkId' => 'nullable|integer|exists:car_parks,id',
            'description' => 'required|string|min:10|max:5000',
            'actionsTaken' => 'nullable|string|max:5000',
            'injuryReported' => 'required|in:0,1',
            'reporterName' => 'required|string|max:255',
            'reporterEmail' => 'required|email|max:255',
            'reporterPhone' => 'required|string|max:50',
        ];

        $injury = $this->injuryReported === '1';
        $requiresSeverity = $injury || $this->type === ParkingIncidentReportModel::TYPE_ACCIDENT;

        if ($requiresSeverity) {
            $rules['severity'] = 'required|in:'.implode(',', ParkingIncidentReportModel::severityKeys());
        } else {
            $rules['severity'] = 'nullable|in:'.implode(',', ParkingIncidentReportModel::severityKeys());
        }

        $this->validate($rules);

        ParkingIncidentReportModel::create([
            'type' => $this->type,
            'occurred_at' => $this->occurredAt,
            'location' => trim($this->location),
            'car_park_id' => $this->carParkId !== '' ? (int) $this->carParkId : null,
            'description' => trim($this->description),
            'actions_taken' => trim($this->actionsTaken) !== '' ? trim($this->actionsTaken) : null,
            'injury_reported' => $injury,
            'severity' => $requiresSeverity ? $this->severity : null,
            'reporter_name' => trim($this->reporterName),
            'reporter_email' => trim($this->reporterEmail),
            'reporter_phone' => trim($this->reporterPhone),
        ]);

        $this->submitted = true;

        try {
            Flux::toast(__('parking_incidents.complete_title'));
        } catch (\Throwable) {
            session()->flash('status', __('parking_incidents.complete_title'));
        }
    }

    public function render()
    {
        return view('livewire.public.parking-incident-report');
    }
}
