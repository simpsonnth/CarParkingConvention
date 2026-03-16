<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parking_registrations', function (Blueprint $table) {
            $table->boolean('sharing_with_other_congregations')->default(false)->after('vehicle_type');
            $table->text('sharing_congregations_notes')->nullable()->after('sharing_with_other_congregations');
        });
    }

    public function down(): void
    {
        Schema::table('parking_registrations', function (Blueprint $table) {
            $table->dropColumn(['sharing_with_other_congregations', 'sharing_congregations_notes']);
        });
    }
};
