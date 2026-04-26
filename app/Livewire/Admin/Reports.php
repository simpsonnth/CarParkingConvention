<?php

namespace App\Livewire\Admin;

use App\Services\CongregationNumbersReportMetrics;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Reports extends Component
{
    public function render(CongregationNumbersReportMetrics $metrics)
    {
        return view('livewire.admin.reports', [
            'metrics' => $metrics->compute(),
        ]);
    }
}
