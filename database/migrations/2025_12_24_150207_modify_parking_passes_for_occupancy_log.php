<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('parking_passes', function (Blueprint $table) {
            $table->dropUnique(['uuid']); // Drop index first for SQLite
            $table->dropColumn('uuid'); // No longer needed as we scan the Congregation UUID
            $table->dropColumn('vehicle_reg'); // We validated this isn't needed up front
            // Status defaults to 'parked' now? Or we set it on create.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parking_passes', function (Blueprint $table) {
            //
        });
    }
};
