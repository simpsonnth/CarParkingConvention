<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('congregation_numbers_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('congregation_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('car_park_tickets_count');
            $table->boolean('organizes_coach');
            $table->boolean('sharing_coach_with_others')->nullable();
            $table->foreignId('shared_with_congregation_id')->nullable()->constrained('congregations')->nullOnDelete();
            $table->string('coach_size', 32)->nullable();
            $table->boolean('disabled_parking_required');
            $table->unsignedInteger('disabled_parking_count')->nullable();
            $table->string('submitted_locale', 8)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('congregation_numbers_responses');
    }
};
