<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('car_parks', function (Blueprint $table) {
            $table->string('map_image_path')->nullable()->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('car_parks', function (Blueprint $table) {
            $table->dropColumn('map_image_path');
        });
    }
};
