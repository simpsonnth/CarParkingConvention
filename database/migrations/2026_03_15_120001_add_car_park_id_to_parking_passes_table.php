<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parking_passes', function (Blueprint $table) {
            $table->foreignId('car_park_id')->nullable()->after('congregation_id')->constrained('car_parks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('parking_passes', function (Blueprint $table) {
            $table->dropForeign(['car_park_id']);
        });
    }
};
