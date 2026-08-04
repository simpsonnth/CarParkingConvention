<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parking_registrations', function (Blueprint $table) {
            $table->string('cancelled_via', 32)->nullable()->after('ticket_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('parking_registrations', function (Blueprint $table) {
            $table->dropColumn('cancelled_via');
        });
    }
};
