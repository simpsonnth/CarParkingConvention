<?php

namespace App\Livewire\Admin;

use App\Models\Congregation;
use App\Models\ParkingPass;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class CongregationDetail extends Component
{
    use WithPagination;

    public Congregation $congregation;

    public function mount(Congregation $congregation): void
    {
        $this->congregation = $congregation->load(['carPark', 'numbersResponse']);
    }

    public function checkout(int $passId): void
    {
        $pass = ParkingPass::query()
            ->where('congregation_id', $this->congregation->id)
            ->whereKey($passId)
            ->firstOrFail();

        $pass->update([
            'status' => 'left',
            'left_at' => now(),
        ]);

        Flux::toast('Vehicle checked out.');
    }

    public function checkoutAll(): void
    {
        ParkingPass::where('congregation_id', $this->congregation->id)
            ->where('status', 'parked')
            ->update([
                'status' => 'left',
                'left_at' => now(),
            ]);

        Flux::toast('All vehicles checked out.');
    }

    public function render()
    {
        $this->congregation->loadMissing(['numbersResponse', 'carPark']);

        $numbers = $this->congregation->numbersResponse;

        $expectedTickets = (int) ($numbers?->car_park_tickets_count ?? 0);

        $disabledRequested = 0;
        if ($numbers !== null && $numbers->disabled_parking_required) {
            $disabledRequested = (int) ($numbers->disabled_parking_count ?? 0);
        }

        $parkedCount = (int) ParkingPass::query()
            ->where('congregation_id', $this->congregation->id)
            ->where('status', 'parked')
            ->count();

        $disabledParkedCount = (int) ParkingPass::query()
            ->where('congregation_id', $this->congregation->id)
            ->where('status', 'parked')
            ->where('elderly_infirm_parking', true)
            ->count();

        $remainingVsSurvey = max(0, $expectedTickets - $parkedCount);

        $checkInPercent = $expectedTickets > 0
            ? min(100.0, round(100 * $parkedCount / $expectedTickets, 1))
            : ($parkedCount > 0 ? 100.0 : 0.0);

        $cars = ParkingPass::where('congregation_id', $this->congregation->id)
            ->where('status', 'parked')
            ->with(['scannedBy', 'congregation.carPark'])
            ->latest('scanned_at')
            ->paginate(15);

        return view('livewire.admin.congregation-detail', [
            'cars' => $cars,
            'survey' => [
                'has_response' => $numbers !== null,
                'expected_tickets' => $expectedTickets,
                'disabled_requested' => $disabledRequested,
                'disabled_required' => (bool) ($numbers?->disabled_parking_required ?? false),
            ],
            'parking' => [
                'parked_count' => $parkedCount,
                'expected_tickets' => $expectedTickets,
                'remaining_vs_survey' => $remainingVsSurvey,
                'disabled_parked_count' => $disabledParkedCount,
                'check_in_percent' => $checkInPercent,
            ],
        ]);
    }
}
