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
        Schema::table('parking_passes', function (Blueprint $table) {
            $table->boolean('elderly_infirm_parking')->default(false)->after('days');
            $table->string('notes')->nullable()->after('elderly_infirm_parking');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parking_passes', function (Blueprint $table) {
            $table->dropColumn(['elderly_infirm_parking', 'notes']);
        });
    }
};
