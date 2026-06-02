<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Nullable so existing coach rows stay "not yet decided" until an admin sets yes/no.
     */
    public function up(): void
    {
        Schema::table('parking_registrations', function (Blueprint $table) {
            $table->boolean('coach_staying_on_site')
                ->nullable()
                ->after('coach_captain_to_be_assigned');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parking_registrations', function (Blueprint $table) {
            $table->dropColumn('coach_staying_on_site');
        });
    }
};
