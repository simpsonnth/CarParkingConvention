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
            $table->string('name')->nullable()->after('contact_number');
            $table->string('email')->nullable()->after('name');
            $table->json('days')->nullable()->after('email');
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
