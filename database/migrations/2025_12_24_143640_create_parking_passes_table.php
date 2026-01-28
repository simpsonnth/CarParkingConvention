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
        Schema::create('parking_passes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('congregation_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending, parked, used
            $table->string('vehicle_reg')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->foreignId('scanned_by_user_id')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parking_passes');
    }
};
