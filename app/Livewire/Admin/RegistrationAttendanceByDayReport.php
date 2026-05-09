<?php

namespace App\Livewire\Admin;

use App\Services\ParkingRegistrationAttendanceByDayMetrics;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class RegistrationAttendanceByDayReport extends Component
{
    public function render(ParkingRegistrationAttendanceByDayMetrics $metrics)
    {
        return view('livewire.admin.registration-attendance-by-day-report', [
            'attendanceByDay' => $metrics->compute(),
        ]);
    }
}
