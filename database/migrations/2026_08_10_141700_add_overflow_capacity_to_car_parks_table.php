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
        Schema::table('car_parks', function (Blueprint $table): void {
            $table->unsignedInteger('overflow_capacity')->default(0)->after('capacity_sunday');
        });

        foreach ([
            'North' => 5,
            'North 2' => 20,
            'Rosebine 1' => 147,
            'Rosebine 2' => 56,
            'West' => 60,
        ] as $name => $overflow) {
            DB::table('car_parks')
                ->where('name', $name)
                ->update(['overflow_capacity' => $overflow]);
        }
    }

    public function down(): void
    {
        Schema::table('car_parks', function (Blueprint $table): void {
            $table->dropColumn('overflow_capacity');
        });
    }
};
