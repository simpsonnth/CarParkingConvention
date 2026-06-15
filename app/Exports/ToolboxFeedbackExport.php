<?php

namespace App\Exports;

use App\Models\ToolboxFeedback;
use App\Support\ConventionDay;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ToolboxFeedbackExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return ToolboxFeedback::query()
            ->orderByDesc('created_at')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Submitted',
            'Name',
            'Email',
            'Phone',
            'Feedback',
            'Added to toolbox talk',
            'Toolbox talk day',
        ];
    }

    /**
     * @param  ToolboxFeedback  $row
     */
    public function map($row): array
    {
        return [
            $row->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') ?? '',
            $row->submitter_name,
            $row->submitter_email,
            $row->submitter_phone ?? '',
            $row->feedback,
            $row->added_to_toolbox_talk ? 'Yes' : 'No',
            $row->toolbox_talk_day ? ConventionDay::label($row->toolbox_talk_day) : '',
        ];
    }
}
