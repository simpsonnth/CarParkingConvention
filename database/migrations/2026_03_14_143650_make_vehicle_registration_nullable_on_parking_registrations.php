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
            $table->string('vehicle_registration')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parking_registrations', function (Blueprint $table) {
            $table->string('vehicle_registration')->nullable(false)->change();
        });
    }
};
