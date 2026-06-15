<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parking_incident_reports', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->dateTime('occurred_at');
            $table->string('location');
            $table->foreignId('car_park_id')->nullable()->constrained('car_parks')->nullOnDelete();
            $table->text('description');
            $table->text('actions_taken')->nullable();
            $table->boolean('injury_reported')->default(false);
            $table->string('severity')->nullable();
            $table->string('reporter_name');
            $table->string('reporter_email');
            $table->string('reporter_phone');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_incident_reports');
    }
};
