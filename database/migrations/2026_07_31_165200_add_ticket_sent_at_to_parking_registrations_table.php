<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tracks when master-pass / parking tickets were marked as sent for a
     * registration. Null means not yet sent; a timestamp means sent.
     */
    public function up(): void
    {
        Schema::table('parking_registrations', function (Blueprint $table) {
            $table->timestamp('ticket_sent_at')->nullable()->after('coach_staying_on_site');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parking_registrations', function (Blueprint $table) {
            $table->dropColumn('ticket_sent_at');
        });
    }
};
