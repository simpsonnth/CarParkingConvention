<?php

declare(strict_types=1);

use App\Models\HotelGuestParkingRequest;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        HotelGuestParkingRequest::ensureCongregation();
    }

    public function down(): void
    {
        // Keep the Radisson congregation — guests may already use the RADISSON code.
    }
};
