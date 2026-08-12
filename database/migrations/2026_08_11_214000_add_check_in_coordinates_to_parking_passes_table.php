<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parking_passes', function (Blueprint $table): void {
            $table->decimal('check_in_latitude', 10, 7)->nullable()->after('scanned_by_user_id');
            $table->decimal('check_in_longitude', 10, 7)->nullable()->after('check_in_latitude');
        });
    }

    public function down(): void
    {
        Schema::table('parking_passes', function (Blueprint $table): void {
            $table->dropColumn(['check_in_latitude', 'check_in_longitude']);
        });
    }
};
