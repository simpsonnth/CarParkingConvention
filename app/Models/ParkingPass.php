<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ParkingPass extends Model
{
    protected $fillable = [
        'congregation_id',
        'status',
        'vehicle_reg',
        'contact_number',
        'scanned_at',
        'left_at',
        'scanned_by_user_id',
        'name',
        'email',
        'days',
        'elderly_infirm_parking',
        'notes',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'left_at' => 'datetime',
        'days' => 'array',
        'elderly_infirm_parking' => 'boolean',
    ];

    public function congregation(): BelongsTo
    {
        return $this->belongsTo(Congregation::class);
    }

    public function scannedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by_user_id');
    }
}