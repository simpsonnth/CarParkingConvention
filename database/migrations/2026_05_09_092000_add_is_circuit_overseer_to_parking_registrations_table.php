<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parking_registrations', function (Blueprint $table) {
            $table->boolean('is_circuit_overseer')->default(false)->after('congregation');
        });
    }

    public function down(): void
    {
        Schema::table('parking_registrations', function (Blueprint $table) {
            $table->dropColumn('is_circuit_overseer');
        });
    }
};
