<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ToolboxFeedback extends Model
{
    protected $table = 'toolbox_feedback_submissions';

    protected $fillable = [
        'submitter_name',
        'submitter_email',
        'submitter_phone',
        'feedback',
        'added_to_toolbox_talk',
        'toolbox_talk_day',
    ];

    protected $casts = [
        'added_to_toolbox_talk' => 'boolean',
    ];
}
