<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('circuit_overseer_parking_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->unsignedInteger('car_park_tickets_count');
            $table->boolean('disabled_parking_required')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('circuit_overseer_parking_requirements');
    }
};
