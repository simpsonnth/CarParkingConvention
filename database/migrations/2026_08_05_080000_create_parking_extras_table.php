<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parking_extras', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('congregation');
            $table->string('contact_number', 20);
            $table->string('email')->nullable();
            $table->string('vehicle_type')->default('car');
            $table->string('vehicle_registration', 20)->nullable();
            $table->json('days')->nullable();
            $table->boolean('elderly_infirm_parking')->default(false);
            $table->text('notes')->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->foreignId('parking_registration_id')
                ->nullable()
                ->constrained('parking_registrations')
                ->nullOnDelete();
            $table->timestamp('actioned_at')->nullable();
            $table->foreignId('actioned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_extras');
    }
};
