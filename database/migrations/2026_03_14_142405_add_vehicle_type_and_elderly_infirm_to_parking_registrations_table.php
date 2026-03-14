<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('parking_registrations', function (Blueprint $table) {
            $table->string('vehicle_type', 20)->default('car')->after('id'); // 'car' or 'coach'
            $table->boolean('elderly_infirm_parking')->default(false)->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parking_registrations', function (Blueprint $table) {
            $table->dropColumn(['vehicle_type', 'elderly_infirm_parking']);
        });
    }
};
