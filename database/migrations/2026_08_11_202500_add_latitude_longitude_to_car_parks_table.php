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
            $table->decimal('latitude', 10, 7)->nullable()->after('location');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });

        $coordinates = [
            'North 2' => [51.4585055937297, -0.3426077211699696],
            'North' => [51.457957634545735, -0.34176550753929336],
            'West' => [51.454889526524305, -0.3434177482924672],
            'Rosebine 1' => [51.45035676435424, -0.348615869363329],
            'Rosebine 2' => [51.44958137563192, -0.3505309665999623],
        ];

        foreach ($coordinates as $name => [$latitude, $longitude]) {
            DB::table('car_parks')
                ->where('name', $name)
                ->update([
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('car_parks', function (Blueprint $table): void {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
