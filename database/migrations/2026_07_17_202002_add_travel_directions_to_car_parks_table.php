<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('car_parks', function (Blueprint $table) {
            $table->text('travel_directions')->nullable()->after('map_image_path');
        });
    }

    public function down(): void
    {
        Schema::table('car_parks', function (Blueprint $table) {
            $table->dropColumn('travel_directions');
        });
    }
};
