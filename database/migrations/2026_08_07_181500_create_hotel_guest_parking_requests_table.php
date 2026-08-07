<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_guest_parking_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('contact_number');
            $table->string('vehicle_registration', 32);
            $table->string('email');
            $table->json('days');
            $table->string('status', 32)->default('pending')->index();
            $table->foreignId('car_park_id')
                ->nullable()
                ->constrained('car_parks')
                ->nullOnDelete();
            $table->foreignId('parking_registration_id')
                ->nullable()
                ->constrained('parking_registrations')
                ->nullOnDelete();
            $table->text('admin_notes')->nullable();
            $table->timestamp('actioned_at')->nullable();
            $table->foreignId('actioned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_guest_parking_requests');
    }
};
