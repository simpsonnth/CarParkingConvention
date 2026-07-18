<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('car_parks', function (Blueprint $table) {
            $table->unsignedInteger('capacity_friday')->default(1)->after('capacity');
            $table->unsignedInteger('capacity_saturday')->default(1)->after('capacity_friday');
            $table->unsignedInteger('capacity_sunday')->default(1)->after('capacity_saturday');
        });

        DB::table('car_parks')->update([
            'capacity_friday' => DB::raw('capacity'),
            'capacity_saturday' => DB::raw('capacity'),
            'capacity_sunday' => DB::raw('capacity'),
        ]);
    }

    public function down(): void
    {
        Schema::table('car_parks', function (Blueprint $table) {
            $table->dropColumn(['capacity_friday', 'capacity_saturday', 'capacity_sunday']);
        });
    }
};
