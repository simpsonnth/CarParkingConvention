<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a flag indicating that, at the time of a coach registration, the
     * congregation had not yet appointed a coach captain and that the contact
     * details on this row therefore belong to the congregation secretary acting
     * as a temporary point of contact. Default false preserves the meaning of
     * existing rows (captain explicitly named).
     */
    public function up(): void
    {
        Schema::table('parking_registrations', function (Blueprint $table) {
            $table->boolean('coach_captain_to_be_assigned')
                ->default(false)
                ->after('elderly_infirm_parking');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parking_registrations', function (Blueprint $table) {
            $table->dropColumn('coach_captain_to_be_assigned');
        });
    }
};
