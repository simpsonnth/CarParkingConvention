<?php

namespace App\Livewire\Admin;

use App\Services\SurveyVsRegistrationMetrics;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class SurveyVsRegistrationReport extends Component
{
    public function render(SurveyVsRegistrationMetrics $metrics)
    {
        return view('livewire.admin.survey-vs-registration-report', [
            'svr' => $metrics->compute(),
        ]);
    }
}
